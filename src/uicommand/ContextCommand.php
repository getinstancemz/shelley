<?php

namespace getinstance\utils\aichat\uicommand;


class ContextCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $indent = str_pad("", 13);
        $buffer = "";
        $messages = $this->getMessages();
        print "# Here is the current context:\n";
        foreach($messages->toArray(20, 3000) as $row) {
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
}

