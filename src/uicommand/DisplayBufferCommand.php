<?php

namespace getinstance\utils\aichat\uicommand;


class DisplayBufferCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        print "# current state of buffer:\n";
        print "# \n";
        print $buffer;
        print "# \n\n";
    }

    public function getName(): string
    {
        return 'buf';
    }
}

