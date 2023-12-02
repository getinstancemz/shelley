<?php

namespace getinstance\utils\aichat\uicommand;
class AssistantToolsCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $tools = [
            "retrieval"=> "retrieval",
            "code_interpreter"=> "code_interpreter",
        ];
        list($idx, $val) = $this->ui->picklist("Choose tool", array_keys($tools));
        // save it and set it
        $this->runner->getModeRunner()->setTool($val);
        print "# set $val\n";
    }

    public function getName(): string
    {
        return "tool";
    }

    public function getDescription(): string
    {
        return "Switch asssistant to the given tool (functions to come)";
    }
}
