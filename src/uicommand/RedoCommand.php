<?php

namespace getinstance\utils\aichat\uicommand;

class RedoCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $buffer = "";
        echo "# forgetting buffer -- go again\n";
    }

    public function getName(): string
    {
        return 'redo';
    }

    public function getDescription(): string
    {
        return "Wipes the buffer";
    }
}
