<?php

namespace getinstance\utils\aichat\functions;


class FunctionRegistry { 
    private array $commands;

    public function __construct()
    {
        $this->commands = [];
        $cmds = [
            new TodoistTodayFunction(),
        ];

        foreach ($cmds as $command) {
            $this->commands[$command->getName()] = $command;
        }
    }

    public function getCommands(): array
    {
        return array_values($this->commands);
    }
}
