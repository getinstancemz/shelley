<?php

namespace getinstance\utils\aichat\uicommand;
use getinstance\utils\aichat\uicommand\AbstractCommand;

class AssistantFileSyncCommand extends AbstractCommand
{
   
    public function execute(string &$buffer, array $args, ): void
    {
        $asstrunner = $this->runner->getModeRunner();
        $filemanager = $asstrunner->getFileManager();
        $filemanager->sync();
        $filemanager->uploadBatchFiles();
        print "# sync sunked\n";
    }

    public function getName(): string
    {
        return "fsync";
    }

    public function getDescription(): string
    {
        return "sync all known files -- only uploads if changed. Removes remote if local file can't be found.";
    }
}
