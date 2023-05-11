<?php

namespace getinstance\utils\aichat\ai;

class Message {
    public static int $trunc = 500;
    private int $id;
    private string $role;
    private string $content;
    private int $tokencount=0;
    private string $summary="";
    private int $summarytokencount=0;

    private bool $fullcontentmode = true;
    private bool $changed = false;

    public function __construct(
        int $id,
        string $role,
        string $content,
        int $tokencount=0,
        string $summary="",
        int $summarytokencount=0
    ) {
        if ($id <= 0) {
            $this->markChanged();
        }
        $this->id = $id;
        $this->role = $role;
        $this->content = $content;
        $this->summary = $summary;

        $this->tokencount = $tokencount;
        if (! $tokencount) { 
            $this->tokencount = Comms::countTokens($content);
            $this->markChanged();
        }

        if (! empty($this->summary)) {
            $this->summarytokencount = $summarytokencount;
            if (! $summarytokencount) { 
                $this->summarytokencount = Comms::countTokens($this->summary);
                $this->markChanged();
            }
        }
    }

    function getId() {
        return $this->id;
    }

    function setId(int $id) {
        $this->id = $id;
    }

    function setRole(string $role) {
        $this->role = $role;
    }

    function getRole() {
        return $this->role;
    }

    function setContent(string $content) {
        $this->content = $content;
        $this->tokencount = Comms::countTokens($content);
    }

    function setSummary(string $summary) {
        $this->summary = $summary;
        $this->summarytokencount = Comms::countTokens($this->summary);
    }

    function setFullContentMode(bool $which): void
    {
        $this->fullcontentmode = $which;
    }

    public function getTokenCount(): int {
        return $this->tokencount;
    }

    function hasSummary(): bool {
        return (! empty($this->summary));
    }

    function getSummary(): string
    {
        return $this->summary;
    }

    public function getSummaryTokenCount(): int {
        return $this->summarytokencount;
    }

    function getContextSummary(): string
    {
        if (! $this->hasSummary()) {
            return (substr($this->content, 0, self::$trunc)); 
        }
        return $this->summary;
    }

    public function getContextSummaryTokenCount(): int {
        if (! $this->hasSummary()) {
            return Comms::countTokens($this->getContextSummary());
        }
        return $this->summarytokencount;
    }

    public function getContent(): string {
        return $this->content;
    }

    function getContextContent() {
        return ($this->fullcontentmode)?$this->content:$this->getContextSummary();
    }

    function getOutputArray(): array
    {
        return [
            "role" => $this->role,
            "content" => $this->getContextContent()
        ];
    }
    public function markChanged() {
        $this->changed=true;
    }
}
