<?php

namespace getinstance\utils\aichat\uicommand;
use getinstance\utils\aichat\uicommand\AbstractCommand;

class AssistantDelFileCommand extends AbstractCommand
{
   
    public function execute(string &$buffer, array $args, ): void
    {
        $asstrunner = $this->runner->getModeRunner();
        $filemanager = $asstrunner->getFileManager();
        $remoteid= trim($args[0]);
        if (empty($remoteid)) {
            print "# Remote id required\n";
            return;
        }
        try {
            $resp = $asstrunner->delAssistantFile($remoteid);
        } catch (\Exception $e) {
            print "# ".$e->getMessage();
            return;
        }
        $filemanager->uploadBatchFiles(); 
        print "# deleted\n";
    }

    public function getName(): string
    {
        return "delfile";
    }

    public function getDescription(): string
    {
        return " <fileid> delete an assistant file by remote id";
    }
}

