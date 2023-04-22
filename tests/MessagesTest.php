<?php

declare(strict_types=1);

namespace getinstance\lazy\medium;

use PHPUnit\Framework\TestCase;
use getinstance\utils\aichat\ai\Messages;

final class MessagesTest extends TestCase
{
    public function testToArray(): void
    {
        $words = ["one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten"];
        $role = "assistant";
        /*
        $messages = new Messages();

        $messages->setTrunc(5);
        $messages->addMessage("user", "one one one");
        $messages->addMessage("assistant", "two two two");
        $messages->addMessage("user", "three three three");
        $messages->addMessage("assistant", "four four four");

        // toArray() without constraints -- regurgitates
        $arr = $messages->toArray();
        $this->assertEquals($arr[0]['content'], "one one one");
        $this->assertEquals($arr[1]['content'], "two two two");
        $this->assertEquals($arr[2]['content'], "three three three");
        $this->assertEquals($arr[3]['content'], "four four four");

        // 2 row constraint
        $arr = $messages->toArray(2);
        $this->assertEquals(count($arr), 2);

        // messages -- we will limit tokens and let Messages truncate older content
        $messages2 = new Messages();
        $messages2->setTrunc(5);
        foreach ($words as $word) {
            $messages2->addMessage($role, $content = $this->dup($word, 100));
            $role = ($role=="assistant")?"user":"assistant";
        }

        $arr = $messages2->toArray(20, 500);
        // will have been truncated
        $this->assertEquals("one o", $arr[3]['content']);
        $this->assertEquals($content, $arr[12]['content']);

        // print $content;
        // $arr = $messages2->toArray();
        // print_r($arr);
        */ 
        $messages3 = new Messages();
        $messages3->setTrunc(5);
        foreach ($words as $word) {
            $messages3->addMessage($role, $content = $this->dup($word, 100), str_word_count($content), "summary: $word");
            $role = ($role=="assistant")?"user":"assistant";
        }

        $arr = $messages3->toArray(20, 500);
        //print_r($arr[3]);
        $this->assertEquals("summary: one", $arr[3]['content']);
        $this->assertEquals($content, $arr[12]['content']);
        //print_r($arr);
    }

    public function dup(string $val, $num)
    {
        $ret = [];
        for ($x=0; $x<$num; $x++) {
            $ret[] = $val;
        }
        return implode(" ", $ret);
    }
}

