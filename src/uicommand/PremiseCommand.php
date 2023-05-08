<?php

namespace getinstance\utils\aichat\uicommand;

class PremiseCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $premise = $args[0];
        // if empty then just report
        if (preg_match("/^\s*$/", $premise)) {
            print "# premise:\n";
            print "# " . $this->runner->getPremise() . "\n";
            return;
        }

        // otherwise change it
        $this->runner->setPremise($args[0]);
        print "# premise set:\n";
    }

    public function getName(): string
    {
        return 'premise';
    }
}
