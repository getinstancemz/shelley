<?php

namespace getinstance\utils\aichat\uicommand;
use getinstance\utils\aichat\control\Runner;


class ArbitraryCommand extends AbstractCommand
{
    private $name;
    public function __construct(Runner $runner, string $name) {
        $this->name = $name;
        parent::__construct($runner);
    }

    public function execute(string &$buffer, array $args): void
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}

