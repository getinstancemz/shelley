<?php

namespace getinstance\utils\aichat\uicommand;

class FileCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $path = $args[0];
        if (empty($path)) {
            print "# Path required\n";
            return;
        }
        if ((! preg_match("|^http[s]?://|", $path)) && ! file_exists($path)) {
            print "# Cannot find a file at '{$path}'\n";
            return;
        }
        $contents = file_get_contents($args[0]);
        $len = count(explode("\n", $contents));
        print "# got contents of '{$args[0]} ({$len} lines)'\n";
        $buffer .= $contents;
    }

    public function getName(): string
    {
        return "file";
    }

    public function getDescription(): string
    {
        return "<path> - Will include the contents of <path> in your buffer";
    }
}
