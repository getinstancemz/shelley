<?php

namespace getinstance\utils\aichat\ai;

/* listing 012.02 */
class Messages
{
    private string $premise = "You are an interested, inquisitive and helpful assistant";
    private array $messages = [];
    private int $trunc = 1000;

    public function __construct()
    {
    }

    // will only act on newly added messages without an existing summary
    public function setTrunc(int $trunc) {
        $this->trunc = $trunc;
    }

    private function makeSummary(string $content, string $summary): string
    {
        return (empty($summary)) ? substr($content, 0, $this->trunc) : $summary;
    }

    private function getContentSize(string $content, int $tokens): int
    {
        return ($tokens > 0) ? $tokens :  Comms::countTokens($content);
    }

    private function makeMessage(string $role, string $message, int $tokens=0, string $summary="", int $summarytokens=0 ) {
        $msg = [
            "role" => $role, 
            "content" => trim($message),
            "tokens" => $this->getContentSize($message, $tokens),
            "summary" => $this->makeSummary($message, $summary),
        ];
        $msg["summarytokens"]  = $this->getContentSize($msg['summary'], $summarytokens);
        return $msg;
    }

    public function addMessage(string $role, string $message, int $tokens=0, string $summary="", int $summarytokens=0 ) {
        $this->messages[] = $this->makeMessage($role, $message, $tokens, $summary, $summarytokens); 
    }
    
    public function getPremise() {
        return $this->premise;
    }

    public function setPremise(string $premise) {
        $this->premise = $premise;
    }

    public function resetMessages()
    {
        $this->messages = [];
    }

    public function toArray($maxrows=0, $maxtokens=0) {
        $desc = [
            $this->makeMessage("system", $this->premise),
            $this->makeMessage("user", $this->premise . " Do you understand this instruction?" ),
            $this->makeMessage("assistant", "I understand. I am ready.")
        ];
        $messages = $this->messages;
        if ($maxrows > 0) {
            $messages = array_slice($this->messages, ($maxrows*-1));
        }
        if ($maxtokens > 0) {
            $messages = $this->compress($desc, $messages, $maxtokens);
        }

        return array_map(function($val) { return ["role" => $val['role'], "content" => $val['content']]; }, $messages );
    }

    private function compress(array $premise, array $context, int $available) {
        $last = array_pop($context);
        $available = $this->checkRequiredMessages($premise, $last, $available);        
        // we have a new availability and we reset size
        $size = 0;
        $ret = [];
        // reverse because we want to privilege recent messages
        $context = array_reverse($context);
        foreach ($context as $message) {
            $newsize = ($size + $message['tokens']);
            if ($newsize > $available) {
                $newsize = ($size + $message['summarytokens']);
                // if still too big we're done
                if ($newsize > $available) {
                    return array_merge( $premise, $ret, [ $last ] );
                }
                // short version fits but we're on short rations now
                $message['content'] = $message['summary'];
            }
            // add to the beginning of $ret
            array_unshift($ret, $message);
            $size = $newsize;
        }

        // return the lot
        return array_merge( $premise, $ret, [ $last ] );
    }

    private function checkRequiredMessages(array &$premise, array &$last, int $available)
    {
        $size = 0;
        foreach ($premise as $msg) {
            $size += $msg['tokens'];
        }
        $size += $last['tokens'];
        // premise and the last message are non-negotiable
        if ($size > $available) {
            throw new \Exception("The message exceeds the available space ({$available}) - size: $size");
        }
        return ($available - $size);
    }

    public function toPrompt($max, $maxtokens) {
        $chat = $this->toArray($max, $maxtokens);
        $system = array_shift($chat);
        $query = $system['content'] ."\n";
        foreach($chat as $line) {
            $query .= "{$line['role']}: {$line['content']}\n";
        }
        $query .= "assistant: \n";
        return $query;
    }
}
