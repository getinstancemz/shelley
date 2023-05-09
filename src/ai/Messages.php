<?php

namespace getinstance\utils\aichat\ai;

/* listing 01.10 */
class Messages
{
    // ...

/* /listing 01.10 */
    private array $messages = [];
    private string $premise = "You are an interested, inquisitive and helpful assistant";

/* listing 01.10 */
    private function makeMessage(string $role, string $content): Message
    {
        $message = new Message(-1, $role, $content);
        return $message;
    }
    public function addNewMessage($role, $content): Message
    {
        return $this->addMessage($this->makeMessage($role, $content));
    }
    public function addMessage(Message $message): Message
    {
        $this->messages[] = $message;
        return $message;
    }
/* /listing 01.10 */

    public function getPremise()
    {
        return $this->premise;
    }

    public function setPremise(string $premise)
    {
        $this->premise = $premise;
    }

    public function resetMessages()
    {
        $this->messages = [];
    }
/* listing 01.15 */
/* listing 01.11 */
    public function toArray($maxrows = 0, $maxtokens = 0)
    {
/* /listing 01.11 */
        // ...

/* listing 01.11 */
/* /listing 01.15 */
        $desc = [
            $this->makeMessage("system", $this->premise),
            $this->makeMessage("user", $this->premise . " Do you understand this instruction?"),
            $this->makeMessage("assistant", "I understand. I am ready.")
        ];
        $messages = $this->messages;
        if ($maxrows > 0) {
            $messages = array_slice($this->messages, ($maxrows * -1));
        }
/* listing 01.15 */
/* /listing 01.11 */
        if ($maxtokens > 0) {
            $messages = $this->compress($desc, $messages, $maxtokens);
        }
/* listing 01.11 */

        // ...
/* /listing 01.15 */
        return array_map(function ($val) {
            return $val->getOutputArray();
        }, $messages);
/* listing 01.15 */
    }
/* /listing 01.11 */
    
    private function compress(array $premise, array $context, int $available)
    {
        $threshold = ($available * 0.75);
        $last = array_pop($context);
        $ret = [];

        // work through the messages
        // if we reach a threshold (75%) or run out of space
        // then switch to the summary version

/* /listing 01.15 */

        $available = $this->checkRequiredMessages($premise, $last, $available);
        // we have a new availability and we reset size
        $size = 0;
        // reverse because we want to privilege recent messages
        $context = array_reverse($context);
        foreach ($context as $message) {
            $newsize = ($size + $message->getTokenCount());
            if ($newsize > $available || $size > $threshold) {
                $newsize = ($size + $message->getContextSummaryTokenCount());
                // if still too big we're done
                if ($newsize > $available) {
                    return array_merge($premise, $ret, [ $last ]);
                }
                // short version fits but we're on short rations now
                $message->setFullContentMode(false);
            }
            // add to the beginning of $ret
            array_unshift($ret, $message);
            $size = $newsize;
        }

        // return the lot
/* listing 01.15 */
        return array_merge($premise, $ret, [ $last ]);
    }
/* /listing 01.15 */

    private function checkRequiredMessages(array &$premise, Message $last, int $available)
    {
        $size = 0;
        foreach ($premise as $msg) {
            $size += $msg->getTokenCount();
        }
        $size += $last->getTokenCount();
        // premise and the last message are non-negotiable
        if ($size > $available) {
            throw new \Exception("The message exceeds the available space ({$available}) - size: $size");
        }
        return ($available - $size);
    }

    public function toPrompt($max, $maxtokens)
    {
        $chat = $this->toArray($max, $maxtokens);
        $system = array_shift($chat);
        $query = $system['content'] . "\n";
        foreach ($chat as $line) {
            $query .= "{$line['role']}: {$line['content']}\n";
        }
        $query .= "assistant: \n";
        return $query;
    }
/* listing 01.11 */
}
