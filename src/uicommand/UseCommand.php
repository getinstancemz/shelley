<?php

namespace getinstance\utils\aichat\uicommand;

class UseCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        try {
            $convoname = empty($args[0]) ? "default" : $args[0];
            $saver = $this->runner->getSaver();
            if (! $saver->hasConvo($convoname)) {
                print "# No such conversation: '{$convoname}'\n";
                if (! $this->ui->confirm("create '{$convoname}'?")) {
                    print "# no action\n";
                } else {
                    print "# creating\n";
                    $saver->createConvo($convoname);
                }
            }
            $convos = $this->runner->switchConvo($convoname);
            $this->ui->initSummarise();
        } catch (\Exception $e) {
            print "# No such conversation: '{$convoname}'";
        }
    }

    public function getName(): string
    {
        return "use";
    }

    public function getDescription(): string
    {
        return "<name> - Switches conversation to <name>";
    }
}
