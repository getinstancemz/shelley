<?php

namespace getinstance\utils\aichat\uicommand;

class DeleteConvoCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $convoname = empty($args[0]) ? "default" : $args[0];
        if (! $this->ui->confirm("Are you sure?")) {
            print "# no action\n";
            return;
        }
        print "# would begin\n";
        //$convos = $this->runner->switchConvo($convoname);
        //$this->ui->initSummarise();
    }

    public function getName(): string
    {
        return "del";
    }

    public function getDescription(): string
    {
        return "<name> - Delete conversation and all settings and messages (WARNING - permanent and irrevocable)";
    }
}
