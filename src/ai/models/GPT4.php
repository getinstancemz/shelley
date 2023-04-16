<?php

namespace getinstance\utils\aichat\ai\models;

class GPT4 extends Model {
    public function getName(): string
    {
        return "gpt-4";
    }

    public function getMaxTokens(): int
    {
        return 8192;
    }

    public function getMode(): string
    {
        return "chat";
    }
}
