<?php

namespace getinstance\utils\aichat\uicommand;

use getinstance\utils\aichat\control\Runner;
use getinstance\utils\aichat\ai\Comms;
use getinstance\utils\aichat\ai\Messages;

abstract class AbstractCommand implements CommandInterface
{
    private array $args = [];
    protected Runner $runner;

    public function __construct(Runner $runner)
    {
        $this->runner = $runner;
    }

    public function getMessages()
    {
        return $this->runner->getMessages();
    }

    public function getLastMatchArgs()
    {
        return $this->args;
    }

    public function matches(string $input): bool
    {
        $ret = (bool)preg_match($this->getPattern(), $input, $matches);
        array_shift($matches);
        $this->args = $matches;
        return $ret;
    }

    protected function getPattern(): string
    {
        $trig = "(?:/|\\\\)";
        return "&^{$trig}(?:{$this->getName()})\s*(.*)\s*$&";
    }
}
