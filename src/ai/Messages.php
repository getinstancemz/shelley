<?php

namespace getinstance\utils\aichat\ai;

/* listing 012.02 */
class Messages
{
    private string $premise;
    private array $messages = [];
    private int $trunc = 1000;

    private int $maxtokens = 4000;

    public function __construct(?string $premise=null)
    {
        if (is_null($premise)) {
            $premise = "You are an interested, inquisitive and helpful assistant";
        }
        $this->premise = $premise;
    }

    public function setTrunc(int $size)
    {
        $this->trunc = $size;
    }

    public function addMessage(string $role, string $message, int $tokens=0, string $summary="", int $summarytokens=0 ) {
        $this->messages[] = [
            "role" => $role, 
            "content" => trim($message),
            "tokens" => $tokens,
            "summary" => $summary,
            "summarytokens" => $summarytokens
        ];
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
            [ "role" => "system", "content" => $this->premise ],
            [ "role" => "user", "content" => $this->premise . " Do you understand this instruction?" ],
            [ "role" => "assistant", "content" => "I understand. I am ready." ],
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
            $newsize = ($size + $this->summariseAndSize($message));
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

    private function summariseAndSize(array &$msg): int
    {
        if (! isset($msg['tokens']) || $msg['tokens'] <= 0) {
            $msg['tokens'] = Comms::countTokens($msg['content']);
        }
        if (! isset($msg['summary']) || empty($msg['summary'])) {
            $msg['summary'] = substr($msg['content'], 0, $this->trunc);
        }
        if (! isset($msg['summarytokens']) || empty($msg['summarytokens'])) {
            $msg['summarytokens'] = Comms::countTokens($msg['summary']);
        }
        return $msg['tokens'];
    }

    private function checkRequiredMessages(array &$premise, array &$last, int $available)
    {
        $size = 0;
        foreach ($premise as $msg) {
            $size += $this->summariseAndSize($msg);
        }
        $size += $this->summariseAndSize($last);
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
