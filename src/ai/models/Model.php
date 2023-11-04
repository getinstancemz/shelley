<?php

namespace getinstance\utils\aichat\ai\models;

abstract class Model
{
    abstract public function getName(): string;
    abstract public function getMaxTokens(): int;
    abstract public function getMode(): string;

    /**
     * The amount to allow for a response
     * (20% of maximum allowed for model)
     */
    public function getMaxResponse(): float
    {
        return ($this->getMaxTokens() * 0.2);
    }

    /**
     * The amount to allow for context
     * What is left over from the total token, less the response allowance
     * with a 5% margin
     */
    public function getMaxContext(): float
    {
        $contextmax = ($this->getMaxTokens() - $this->getMaxResponse());
        return ($contextmax - ($contextmax * 0.05));
    }
}
