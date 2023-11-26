<?php

namespace getinstance\utils\aichat\uicommand;

class ModeCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {

        $runner = $this->runner;
        $currentmode = $runner->getMode();

        print "# current mode: {$currentmode}\n\n";
        $modes = [
            "chat",
            "assistant"
        ];
        list($idx, $val) = $this->ui->picklist("Choose mode", $modes);
        if ($val == "chat") {
            $runner->switchToChat();
        } else {
            $runner->switchToAssistant();
        }
        print "# saved $val\n";
    }

    public function getName(): string
    {
        return "mode";
    }

    public function getDescription(): string
    {
        return "switch mode (between chat and assistant)";
    }
}
