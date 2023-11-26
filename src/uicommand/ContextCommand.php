<?php

namespace getinstance\utils\aichat\uicommand;

class ContextCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $model = $this->runner->getModel();
        $indent = str_pad("", 13);
        $buffer = "";
        $messhistory = $this->runner->getMessageHistory(20, $model->getMaxContext());
        print "# Here is the current context:\n";
        foreach ($messhistory as $row) {
            $content = $row['content'];
            $role = str_pad(strtoupper($row['role']), 10);
            print "{$role}> $content\n";
        }
        print "# Context ends\n\n";
    }

    public function getName(): string
    {
        return 'context';
    }

    public function getDescription(): string
    {
        return "Prints the full context for the current conversation (warning: will spam your buffer)";
    }

}
