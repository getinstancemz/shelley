<?php

namespace getinstance\utils\aichat\ai\models;

class GPT35 extends Model {
    public function getName(): string
    {
        return "gpt-3.5-turbo";
    }

    public function getMaxTokens(): int
    {
        return 4096;
    }

    public function getMode(): string
    {
        return "chat";
    }
}
