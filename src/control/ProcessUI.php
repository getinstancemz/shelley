<?php

namespace getinstance\utils\aichat\control;

use getinstance\utils\aichat\persist\ConvoSaver;
use getinstance\utils\aichat\uicommand\ArbitraryCommand;
use getinstance\utils\aichat\uicommand\RedoCommand;
use getinstance\utils\aichat\uicommand\DisplayBufferCommand;
use getinstance\utils\aichat\uicommand\FileCommand;
use getinstance\utils\aichat\uicommand\ContextCommand;
use getinstance\utils\aichat\uicommand\PremiseCommand;
use getinstance\utils\aichat\uicommand\ChatsCommand;
use getinstance\utils\aichat\uicommand\UseCommand;
use getinstance\utils\aichat\ai\Messages;

class ProcessUI
{
    private array $commands = [];
    private ConvoSaver $saver;
    public function __construct(private Runner $runner)
    {
        $this->saver = $runner->getSaver();
        $this->commands = [
            new RedoCommand($this, $runner),
            new DisplayBufferCommand($this, $runner),
            new FileCommand($this, $runner),
            new ContextCommand($this, $runner),
            new PremiseCommand($this, $runner),
            new ChatsCommand($this, $runner),
            new UseCommand($this, $runner),
            // Add other command classes here
        ];
    }

    public function initSummarise()
    {
        // summarise current state of conversation
        $msgs = $this->runner->getMessages();
        $conversation = $this->saver->getConvo();
        $conf = $this->saver->getConf();

        print "# starting or resuming '{$conversation['name']}'\n";
        if (isset($conf['lastmessage'])) {
            print "# last conversation {$conf['lastmessage']}\n";
        }
        print "#\n";
        print "# premise: ".$this->runner->getPremise()."\n";
        print "#\n";
        $context = $msgs->toArray(5);
        $indent = str_pad("", 13);

        // there will be one because we've already started
        if (count($context) <= 1) {
            print "# no history\n\n";
        } else {
            print "# recent summary\n\n";

            foreach ($context as $row) {
                $content = substr($row['content'], 0, 100);
                $content = preg_replace("/\n/", "\n#{$indent}", $content);
                $role = str_pad(strtoupper($row['role']), 10);
                print "# {$role}> $content\n";
            }
            print "\n";
        }
    }

    public function run()
    {
        $this->initSummarise();

        $input = "";
        while (($input = $this->process("USER      > ")) != "q\n") {
            try {
                print "# sending\n";
                $resp = $this->runner->query($input);
            } catch (\Exception $e) {
                $resp = "** I'm sorry, I encountered an error:\n";
                $resp .= $e->getMessage();
            }
            print "ASSISTANT > {$resp} \n";

            print "# summarising...";
            $this->runner->summariseMostRecent();
            print " done\n";
        }
    }

    private function process($prompt)
    {
        $buffer = "";
        $origprompt = $prompt;
        while ($input = readline($prompt)) {
            $prompt = "";
            if ($this->hasContinuationEndChar($input, $buffer)) {
                print "tarse";
                continue;
            }
            if ((new ArbitraryCommand($this, $this->runner, "m"))->matches($input)) {
                $final = $this->processMulti($buffer);
                return $final;
            }

            
            if (! $this->invokeCommand($input, $buffer)) {
                $buffer .= $input;
                
                break;
            }

        }

        if (preg_match("/^\s*+$/", $buffer)) {
            $this->process($origprompt);
        }

        return $buffer;
    }

    private function hasContinuationEndChar(string $input, &$buffer)
    {
        // allow end of line continuation with \ or  /
        $trig = "(?:/|\\\\)";
        if (preg_match("&^(.*){$trig}\s*$&", $input, $matches)) {
            $buffer .= $matches[1] . "\n";
            return true;
        }
        return false;
    }

    private function processMulti(string &$buffer)
    {
        print "# multi-mode on use /e to send buffer\n";
        $input = "";
        while (true) {
            $input = readline();
            if ($this->invokeCommand($input, $buffer)) {
                continue;
            }
            if ((new ArbitraryCommand($this->runner, "e"))->matches($input)) {
                print "# sending\n";
                return $buffer;
            }
            $buffer .= $input . "\n";
        }
    }

    private function invokeCommand(string $input, string &$buffer)
    {
        foreach ($this->commands as $command) {
            if ($command->matches($input)) {
                $command->execute($buffer, $command->getLastMatchArgs());
                return true;
            }
        }
        return false;
    }
}
