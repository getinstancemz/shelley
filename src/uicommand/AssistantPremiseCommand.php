<?php
namespace getinstance\utils\aichat\uicommand;
use getinstance\utils\aichat\uicommand\AbstractCommand;

class AssistantPremiseCommand extends PremiseCommand
{

    public function execute(string &$buffer, array $args): void
    {

        if ($this->testForReport($args)) {
            return;
        }

        $asstrunner = $this->runner->getModeRunner();
        // otherwise change it
        $this->runner->setPremise($args[0]);
        print "# premise set\n";
    }

}
