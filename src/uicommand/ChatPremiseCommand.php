<?php

namespace getinstance\utils\aichat\uicommand;

class ChatPremiseCommand extends PremiseCommand 
{
    public function execute(string &$buffer, array $args): void
    {
        if ($this->testForReport($args)) {
            return;
        }
        // otherwise change it
        $this->runner->setPremise($args[0]);
        print "# premise set:\n";
    }

}
