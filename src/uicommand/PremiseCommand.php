<?php

namespace getinstance\utils\aichat\uicommand;

abstract class PremiseCommand extends AbstractCommand
{

    protected function testForReport(array $args): bool
    {
        $premise = $args[0];
        // if empty then just report
        if (preg_match("/^\s*$/", $premise)) {
            print "# premise:\n";
            print "# " . $this->runner->getPremise() . "\n";
            return true;
        }
        return false;
    }

    public function getName(): string
    {
        return 'premise';
    }

    public function getDescription(): string
    {
        return "[text] - Sets assistant premise for the conversation if [text] provided. Shows the premise otherwise";
    }
}
