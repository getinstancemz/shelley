<?php

namespace getinstance\utils\aichat\ai\models;

class GPT4_1106_Preview extends Model
{
    public function getName(): string
    {
        return "gpt-4-1106-preview";
    }

    public function getMaxTokens(): int
    {
        //return 128000;
        return 4096;
    }

    public function getMode(): string
    {
        return "chat";
    }
}

