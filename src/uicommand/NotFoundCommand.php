<?php

namespace getinstance\utils\aichat\uicommand;

class NotFoundCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        print "# I'm sorry, Dave. That command was not found\n";
    }

    public function getName(): string
    {
        return 'notfound';
    }

    public function matches(string $input): bool
    {
        $ret = (bool)preg_match($this->getPattern(), $input, $matches);
        array_shift($matches);
        //$this->args = [];
        return $ret;
    }

    // matches any command - because this is a backstop command
    protected function getPattern(): string
    {
        $trig = "(?:/|\\\\)";
        return "&^{$trig}(?:\S+)\s*(.*)\s*$&";
    }

    public function getDescription(): string
    {
        return "Error backstop";
    }
}
