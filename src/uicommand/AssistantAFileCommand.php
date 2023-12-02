<?php

namespace getinstance\utils\aichat\uicommand;
use getinstance\utils\aichat\uicommand\AbstractCommand;

class AssistantAFileCommand extends AbstractCommand
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
        if (! file_exists($path)) {
            print "# Cannot find a file at '{$path}'\n";
            return;
        }
        try {
            print "# sending\n";
            $asstrunner->uploadAssistantFile($path);
        } catch(\Exception $e) {
            print "# unable to upload file: {$e->getMessage()}\n";
            return;
        }
        print "# file uploaded\n";
    }

    public function getName(): string
    {
        return "afile";
    }

    public function getDescription(): string
    {
        return "<path> - will add the file to the assistant";
    }
}
