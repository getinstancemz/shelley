<?php

namespace getinstance\utils\aichat\uicommand;
use getinstance\utils\aichat\uicommand\AbstractCommand;

class AssistantADirCommand extends AbstractCommand
{
   
    public function execute(string &$buffer, array $args, ): void
    {
        $asstrunner = $this->runner->getModeRunner();
        $filemanager = $asstrunner->getFileManager();
        $path = trim($args[0]);
        if (empty($path)) {
            print "# Path required\n";
            return;
        }
        if (! is_dir($path)) {
            print "# Cannot find a directory at '{$path}'\n";
            return;
        }
        try {
            print "# sending\n";
            $asstrunner->uploadAssistantDirectory($path);
        } catch(\Exception $e) {
            print "# unable to upload directory: {$e->getMessage()}\n";
            return;
        }
        print "# directory contents compiled\n";
        $filemanager->uploadBatchFiles(); 
        print "# directory contents uploaded\n";
    }

    public function getName(): string
    {
        return "adir";
    }

    public function getDescription(): string
    {
        return "<dir> - will add the contents the directory to the assistant (idempotent)";
    }
}
