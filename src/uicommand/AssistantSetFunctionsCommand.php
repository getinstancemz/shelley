<?php

namespace getinstance\utils\aichat\uicommand;
use getinstance\utils\aichat\functions\TodoistTodayFunction;

class AssistantSetFunctionsCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $moderunner = $this->runner->getModeRunner();
        $freg = $moderunner->getFunctionRegistry();
        $comms = $moderunner->getAssistantComms();
        $function = [
            "todoisttoday",
        ];
        $funcs = $comms->getAssistantFunctionNames();
        $available = $freg->getCommands();
        $picklist = [];
        foreach ($available as $cmd) {
            $selected = (in_array($cmd->getName(), $funcs))?"[*]":"[ ]";
            $picklist[] = $selected . " " . 
                        $cmd->getName() . " " . 
                        $cmd->getDescription(); 
        }
        $result = $this->ui->picklist("Add/remove function", $picklist);
        if (is_null($result)) {
            return;
        }
        list($idx, $val) = $result;
        $command = $available[$idx];
        print_r($command);
        //print "$idx: $val\n";
    }

    public function getName(): string
    {
        return "functions";
    }

    public function getDescription(): string
    {
        return "set/unset functions for assistant";
    }
}
