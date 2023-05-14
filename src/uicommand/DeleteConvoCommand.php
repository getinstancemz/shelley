<?php

namespace getinstance\utils\aichat\uicommand;

class DeleteConvoCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $convoname = empty($args[0]) ? "default" : $args[0];
        if (! $this->ui->confirm("Are you sure you want to delete '{$convoname}'?")) {
            print "# no action\n";
            return;
        }
        $saver = $this->runner->getSaver();
        if (! $saver->hasConvo($convoname)) {
            print "no conversation '{$convoname}'\n";
            return;
        }
        $currentname = $this->runner->getSaver()->getConvoname();
        $saver->deleteConvoAndMessages($convoname);
        print "# done\n";

        if ($convoname == "default") {
            // force creation of an empty default
            $saver->createConvo("default");
        }

        if ($convoname == $currentname) {
            $this->runner->switchConvo("default");
            $this->ui->initSummarise();
        } 
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
