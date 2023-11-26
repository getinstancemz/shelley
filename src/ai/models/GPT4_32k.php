<?php

namespace getinstance\utils\aichat\ai\models;

class GPT4_32k extends Model
{
    public function getName(): string
    {
        return "gpt-4-0613";
    }

    public function getMaxTokens(): int
    {
        return 32768;
    }

    public function getMode(): string
    {
        return "chat";
    }
}
