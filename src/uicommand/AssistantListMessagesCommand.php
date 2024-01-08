<?php

namespace getinstance\utils\aichat\uicommand;
use getinstance\utils\aichat\uicommand\AbstractCommand;

class AssistantListMessagesCommand extends AbstractCommand
{
   
    public function execute(string &$buffer, array $args, ): void
    {
        $asstrunner = $this->runner->getModeRunner();
        $comms = $asstrunner->getAssistantComms();
        $msgs = $comms->getMessages();
        foreach ($msgs as $msg) {
            print "# $msg\n";
        }
    }

    public function getName(): string
    {
        return "amsgs";
    }

    public function getDescription(): string
    {
        return "list most recent messages as stored on remote system";
    }
}
