<?php

namespace getinstance\utils\aichat\uicommand;

use getinstance\utils\aichat\control\Runner;
use getinstance\utils\aichat\control\ProcessUI;
use getinstance\utils\aichat\ai\Comms;
use getinstance\utils\aichat\ai\Messages;

abstract class AbstractCommand implements CommandInterface
{
    private array $args = [];
    protected Runner $runner;
    protected ProcessUI $ui;

    public function __construct(ProcessUI $ui, Runner $runner)
    {
        $this->ui = $ui;
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
        return "&^{$trig}(?:{$this->getName()})(?:\s+|$)(.*)\s*$&";
    }
}
