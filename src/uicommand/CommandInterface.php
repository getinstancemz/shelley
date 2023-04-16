<?php
namespace getinstance\utils\aichat\uicommand;

interface CommandInterface
{
    public function execute(string &$buffer, array $args): void;
    public function getName(): string;
    public function matches(string $input): bool;
}

