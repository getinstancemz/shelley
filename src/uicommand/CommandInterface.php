<?php

namespace getinstance\utils\aichat\uicommand;
/* listing 01.18 */
interface CommandInterface
{
    public function execute(string &$buffer, array $args): void;
    public function getName(): string;
    public function matches(string $input): bool;
}
