<?php

namespace getinstance\utils\aichat\uicommand;

use getinstance\utils\aichat\control\Runner;
use getinstance\utils\aichat\control\ProcessUI;

class ArbitraryCommand extends AbstractCommand
{
    private $name;
    public function __construct(ProcessUI $ui, Runner $runner, string $name)
    {
        $this->name = $name;
        parent::__construct($ui, $runner);
    }

    public function execute(string &$buffer, array $args): void
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return "An empty command that can be overridden";
    }
}
