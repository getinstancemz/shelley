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

    public function accept(FileManagerListener $fml) {
        $this->listeners[] = $fml;
    }

    public function removeFileByRemote(string $remoteid): bool
	{
        $dbfile = $this->saver->getFileByRemoteId($remoteid);
		if (! is_null($dbfile)) {
        	$dbfile = $this->saver->removeFile($dbfile['id']);
		}
        $resp = $this->commsmgr->deleteAssistantFile($remoteid);
		return true;
	}

    public function addDir(string $dir): bool
    {
        if (! is_dir($dir)) {
            throw new \Exception("'{$path}' is not a directory");
        }
        $iterator = new \DirectoryIterator($dir);
        foreach ($iterator as $item) {
            if ($item->isDot() || $item->isLink()) {
                print "ignoring dot or link: ".$item->getpathname()."\n";
                continue;
            }
            if ($item->isDir()) {
                print "adding directory ".$item->getpathname()."\n";
                $this->addDir($item->getpathname());
            }

            if (! in_array($item->getExtension(), $this->getExtensions())) {
                print "ignoring extension: ".$item->getpathname()."\n";
                continue;
            }
            print "adding file ".$item->getpathname()." ... ";
            $this->saveFile($item->getpathname());
            print "\n";
        }
        return true;
    }

    public function saveFile(string $path): bool
    {
        if (!file_exists($path)) {
            throw new \Exception("File at {$path} does not exist.");
        }

        $contents = file_get_contents($path);
        $filehash = md5($contents);

        // Get file data from the database
        $fileData = $this->saver->getFileByPath($path);

		// it's new - add guard clause
        if (is_null($fileData)) {
            print "new";
            $resp = $this->commsmgr->uploadAssistantFile($path);
			$this->saver->addOrUpdateFile($path, $resp['id'], $filehash);
			return true;
		}

		// it's old // update guard clause
		if ($fileData['filehash'] !== $filehash) {
            print "old and changed";
            //print "old file but changed: $path\n";
			$this->commsmgr->deleteAssistantFile($fileData['remoteid']);	
			$resp = $this->commsmgr->uploadAssistantFile($path);	
			$this->saver->addOrUpdateFile($path, $resp['id'], $filehash);
			return true;
		}

		// it's old but not changed - no action
        //print "old file not changed: $path\n";
        print "known / unchanged";
		return true;
    }

    public function getExtensions(): array
    {
        return ['c', 'cpp', 'csv', 'docx', 'html', 'java', 'json', 'md', 'pdf', 'php', 'pptx', 'py', 'rb', 'tex', 'txt', 'css', 'jpeg', 'jpg', 'js', 'gif', 'png', 'tar', 'ts', 'xlsx', 'xml', 'zip']; 
    }
}
