<?php

declare(strict_types=1);

namespace getinstance\lazy\medium;

use PHPUnit\Framework\TestCase;
use getinstance\utils\aichat\ai\Messages;

final class MessagesTest extends TestCase
{
    public function testToArray(): void
    {
        $messages = new Messages();

        // $this->assertTrue(true);
        $messages->setTrunc(5);
        $messages->addMessage("user", "one one one");
        $messages->addMessage("assistant", "two two two");
        $messages->addMessage("user", "three three three");
        $messages->addMessage("assistant", "four four four");
       
        $arr = $messages->toArray();
        $this->assertEquals($arr[0]['content'], "one one one");
        $this->assertEquals($arr[1]['content'], "two two two");
        $this->assertEquals($arr[2]['content'], "three three three");
        $this->assertEquals($arr[3]['content'], "four four four");

        $arr = $messages->toArray(2);
        $this->assertEquals(count($arr), 2);

        $messages2 = new Messages();
        $messages2->setTrunc(5);
        $words = ["one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten"];
        $role = "assistant";
        foreach ($words as $word) {
            $messages2->addMessage($role, $this->dup($word, 100));
            $role = ($role=="assistant")?"user":"assistant";
        }

        $arr = $messages2->toArray(20, 500);
        print_r($arr);

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

