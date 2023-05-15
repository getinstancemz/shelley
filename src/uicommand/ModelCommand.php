<?php

namespace getinstance\utils\aichat\uicommand;
use getinstance\utils\aichat\ai\models\GPT4;
use getinstance\utils\aichat\ai\models\GPT35;

class ModelCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $models = [
            "gpt-3.5-turbo" => new GPT35(),
            "gpt-4" => new GPT4()
        ];

        list($idx, $val) = $this->ui->picklist("Choose model", array_keys($models));
        // save for next time
        $this->runner->getSaver()->setConfVal("model", $val);
        // set it for this time
        $this->runner->setModel($models[$val]);
        print "# picked $val\n";

    /*
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
    */
    }

    public function getName(): string
    {
        return "model";
    }

    public function getDescription(): string
    {
        return "<model> - Switch conversation to the given model";
    }
}
