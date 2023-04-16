<?php

namespace getinstance\utils\aichat\uicommand;


class FileCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $contents = file_get_contents($args[0]);
        $len = count(explode("\n", $contents));
        print "# got contents of '{$args[0]} ({$len} lines)'\n";
        $buffer .= $contents;
    }

    public function getName(): string
    {
        return "file";
    }
}

