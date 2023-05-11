<?php

namespace getinstance\utils\aichat\uicommand;

class UseCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        try {
            $convoname = empty($args[0]) ? "default" : $args[0];
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
}
