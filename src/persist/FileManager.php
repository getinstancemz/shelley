<?php

namespace getinstance\utils\aichat\persist;

use getinstance\utils\aichat\ai\Comms;
use getinstance\utils\aichat\ai\assistants\CommsManager;
use getinstance\utils\aichat\ai\Message;

class FileManager
{
    public function __construct(private CommsManager $commsmgr, private ConvoSaver $saver)
    {
    }

    public function sync(): void
    {
        print "# syncing\n";
        $files = $this->saver->getFiles();
        foreach ($files as $fileData) {
            if (file_exists($fileData['path'])) {
                list($contents, $filehash) = $this->jsonify($fileData['path']);
		        if ($fileData['filehash'] !== $filehash) {
                    // findme
                    print "# sync: {$fileData['path']} changed -- marking for upload\n";
                    $size = strlen($contents);
                    $this->saver->addOrUpdateFile($fileData['path'], $filehash, $size, $fileData['batchid']);
                    print "# updating batch file row\n";
                    $this->saver->markBatchFileForUpload($fileData['batchid'], $size);
                } else {
                    print "# sync: {$fileData['path']} unchanged -- no action\n";
                }
            } else {
                print "# sync: {$fileData['path']} not found -- marking for deletion\n";
                $this->removeFile($fileData['path']);
            }
        }
    }

    public function accept(FileManagerListener $fml) {
        $this->listeners[] = $fml;
    }

    public function removeFile(string $path) {
        $dbfile = $this->saver->getFileByPath($path);        
        print "# removing file {$path}\n";
        if (is_null($dbfile)) {
            print "# unable to find db row. No action\n";
            // no action
            return;
        }
        print "# removing row from db (afile table)\n";
        $this->saver->removeFile($dbfile['id']);
        print "# marking batch file dirty (id {$dbfile['batchid']})\n";
        $this->saver->markBatchFileForUpload($dbfile['batchid'], ($dbfile['size'] * -1));
        print "# no action here -- uploadBatchFiles() will need to be called to action the change\n";
    }

    public function addDir(string $dir): bool
    {
        if (! is_dir($dir)) {
            throw new \Exception("'{$path}' is not a directory");
        }
        $iterator = new \DirectoryIterator($dir);
        foreach ($iterator as $item) {
            if ($item->isDot() || $item->isLink()) {
                print "# ignoring dot or link: ".$item->getpathname()."\n";
                continue;
            }
            if ($item->isDir()) {
                print "# adding directory ".$item->getpathname()."\n";
                $this->addDir($item->getpathname());
            }

            if (! in_array($item->getExtension(), $this->getExtensions())) {
                print "# ignoring extension: ".$item->getpathname()."\n";
                continue;
            }
            print "# adding file '".$item->getpathname()."' ...\n";
            $this->doSaveFile($item->getpathname());
        }
        //$this->uploadBatchFiles(); 
        return true;
    }

    public function getNextBatchFile(string $path) {
        return $this->saver->getNextBatchFile($path);
    }

    public function saveFile(string $path): bool
    {
        $this->doSaveFile($path);
        return true;
    }

    private function doSaveFile(string $path): bool
    {
        if (!file_exists($path)) {
            throw new \Exception("File at {$path} does not exist.");
        }
        // by using a full path we mitigate against chdir risks
        // $path = realpath($path);
        list($contents, $filehash) = $this->jsonify($path);
        $size = strlen($contents);

        print "# saving $path\n";
        // Get file data from the database
        $fileData = $this->saver->getFileByPath($path);

		// it's new - guard clause for add
        if (is_null($fileData)) {
            print "# new file (no record in db)\n";
            $batch = $this->getNextBatchFile($path);
            print "# acquired batch to write to: {$batch['name']}\n";
            print "# adding to db\n";
			$this->saver->addOrUpdateFile($path, $filehash, $size, $batch['id']);
            print "# updating batch file row\n";
            $this->saver->markBatchFileForUpload($batch['id'], $size);
			return true;
		}
        // findme
		// it's old but changed // guard clause for update
		if ($fileData['filehash'] !== $filehash) {
            print "# old file but changed (hash does not match)\n";
            print "# already associated with batch: id {$fileData['batchid']}\n";
            print "# updating afile row in db ({$fileData['id']})\n";
			$this->saver->addOrUpdateFile($path,  $filehash, $size, $fileData['batchid']);
            print "# updating batch file row\n";
            $this->saver->markBatchFileForUpload($fileData['batchid'], $size);
			return true;
		}

		// it's old but not changed - no action
        //print "old file not changed: $path\n";
        print "# file known / and unchanged\n";

		return true;
    }

    public function uploadBatchFiles(): bool
    {
        $batches = $this->saver->getBatchFilesToUpload();
        print "# got batch files to upload...\n";
        foreach ($batches as $batch) {
            list($fullpath, $files) = $this->compileBatchFile($batch);

            print "# {$fullpath}\n";
            foreach ($files as $myfile) {
                print "#      - {$myfile['id']} {$myfile['path']}\n";
            }

            if (empty($files)) {
                print "#      no files - removing batchfile on fs\n";
                if (file_exists($fullpath)) {
                    unlink($fullpath);
                }
                print "#      no files - deleting batchfile row ({$batch['id']})\n";
                $this->saver->deleteBatchFile($batch['id']);
            }
            if (! empty($batch['remoteid'])) {
                print "#       deleting previous remote batch at {$batch['remoteid']}\n";
			    $this->commsmgr->deleteAssistantFile($batch['remoteid']);	
            }


            if (empty($files)) {
                // nothing to upload
                print "#       no files so no upload\n";
                continue;
            }
            print "#       uploading {$fullpath}\n";
            if (file_exists($fullpath)) {
                $resp = $this->commsmgr->uploadAssistantFile($fullpath);
            }
            print "#       storing batch info (batch id: {$batch['id']} remote: {$resp['id']})\n";
            $this->saver->markBatchFileWritten($batch['id'], $resp['id']);
        }



        return true;
        /*
         * 1. get files that need changing
         * 2. compile them.
         * 3. upload them
         * 4. mark them status written
         */
    }
   
    private function compileBatchFile(array $batch): array {
        $files = $this->saver->getFilesByBatch($batch['id']);
        $subdir = "convo_".$batch['conversation_id'];
        $fulldir = $this->saver->getDataDir()."/{$subdir}";
        $filename = $batch['name'].".jsonl";
        $fullpath = "{$fulldir}/{$filename}";

        print "# recompiling batch file {$subdir}/{$filename}\n";
        if (file_exists($fullpath)) {
            print "# previous exists - deleting\n";
            unlink($fullpath);
        }

        if (! file_exists($fulldir)) {
            mkdir($fulldir, 0755, true);
        }
        
        foreach ($files as $afile) {
            print "#     adding to batch: {$afile['path']}\n";
            list($jsonentry, $filehash) = $this->jsonify($afile['path']);
            file_put_contents($fullpath, "{$jsonentry}\n", \FILE_APPEND);
        }
        return [$fullpath, $files];
    }

    private function jsonify(string $path): array
    {
        $contents = file_get_contents($path);
        $filehash = md5($contents);
        $entry = [
            "path" => $path,
            "contents" => $contents, 
        ];
        if (strlen($contents) < 1027) {
            $entry['buffer'] = str_repeat('0', (1027 - strlen($contents)));
        }
        $line = json_encode($entry);
        return [$line, $filehash];
    }

    public function getExtensions(): array
    {
        return ['c', 'cpp', 'csv', 'docx', 'html', 'java', 'json', 'md', 'pdf', 'php', 'pptx', 'py', 'rb', 'tex', 'txt', 'css', 'jpeg', 'jpg', 'js', 'gif', 'png', 'tar', 'ts', 'xlsx', 'xml', 'zip']; 
    }
}
