<?php

namespace getinstance\utils\aichat\uicommand;
use getinstance\utils\aichat\uicommand\AbstractCommand;

class AssistantAFileListCommand extends AbstractCommand
{
   
    public function execute(string &$buffer, array $args, ): void
    {
        $asstrunner = $this->runner->getModeRunner();
        $resp = $asstrunner->listAssistantFiles();
        //foreach ($resp
        print_r($resp);
        //print "# file uploaded";
    }

    public function getName(): string
    {
        return "afiles";
    }

    public function getDescription(): string
    {
        return "list assistant files";
    }
}
