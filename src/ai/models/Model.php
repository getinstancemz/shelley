<?php

namespace getinstance\utils\aichat\ai\models;

abstract class Model {
    public abstract function getName(): string;
    public abstract function getMaxTokens(): int;
    public abstract function getMode(): string;

    /**
     * The amount to allow for a response
     * (20% of maximum allowed for model)
     */
    public function getMaxResponse(): int
    {
        return ($this->getMaxTokens() * 0.2);
    }

    /**
     * The amount to allow for context
     * What is left over from the total token, less the response allowance
     * with a 5% margin
     */
    public function getMaxContext(): int
    {
        $contextmax = ($this->getMaxTokens()-$this->getMaxResponse());
        return ($contextmax - ($contextmax * 0.05));
    }
}
