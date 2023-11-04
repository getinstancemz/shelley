<?php

namespace getinstance\utils\aichat\control;

use getinstance\utils\aichat\persist\ConvoSaver;
use getinstance\utils\aichat\uicommand\ArbitraryCommand;
use getinstance\utils\aichat\uicommand\DeleteConvoCommand;
use getinstance\utils\aichat\uicommand\EditCommand;
use getinstance\utils\aichat\uicommand\HelpCommand;
use getinstance\utils\aichat\uicommand\RedoCommand;
use getinstance\utils\aichat\uicommand\DisplayBufferCommand;
use getinstance\utils\aichat\uicommand\FileCommand;
use getinstance\utils\aichat\uicommand\ContextCommand;
use getinstance\utils\aichat\uicommand\PremiseCommand;
use getinstance\utils\aichat\uicommand\ChatsCommand;
use getinstance\utils\aichat\uicommand\UseCommand;
use getinstance\utils\aichat\uicommand\ModelCommand;
use getinstance\utils\aichat\uicommand\NotFoundCommand;
use getinstance\utils\aichat\ai\Messages;

class ProcessUI
{
    private array $commands = [];
    private ConvoSaver $saver;
    private NotFoundCommand $notfoundcommand;

    public function __construct(private Runner $runner)
    {
        $this->saver = $runner->getSaver();
        $this->commands = [
            new HelpCommand($this, $runner),
            new EditCommand($this, $runner),
            new RedoCommand($this, $runner),
            new DisplayBufferCommand($this, $runner),
            new FileCommand($this, $runner),
            new ContextCommand($this, $runner),
            new PremiseCommand($this, $runner),
            new ChatsCommand($this, $runner),
            new UseCommand($this, $runner),
            new DeleteConvoCommand($this, $runner),
            new ModelCommand($this, $runner),
            // Add other command classes here
        ];
        $this->notfoundcommand = new NotFoundCommand($this, $runner);
    }

    public function getCommands(): array
    {
        return $this->commands;
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
        print "# premise: ".$this->runner->getPremise()."\n";
        print "# model:   ".$this->runner->getModel()->getName()."\n";
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
                $resp .= "\n";
                $resp .= $e->getFile() . " :: " . $e->getLine();
                $resp .= "\n";
                $resp .= $e->getTraceAsString();
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
            return $this->process($origprompt);
        }
        return $buffer;
    }

    public function picklist(string $prompt, array $options): array {
        $actual = [];

        $count = 1;
        foreach ($options as $key => $option) {
            $actual[$count] = [$key, $option];
            print "#    [$count] $option\n";
            $count++;
        }
        while ($input = readline("# {$prompt}: ")) {
            $imput = trim($input);
            if (isset($actual[$input])) {
                return [$actual[$input][0], $actual[$input][1]];
            }
        }
    }

    public function confirm(string $prompt): bool {
        $input = readline("# {$prompt} [y/N]: ");
        if (preg_match("/^[yY]/", $input)) {
            return true;
        }
        return false;
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
            if ((new ArbitraryCommand($this, $this->runner, "e"))->matches($input)) {
                print "# sending\n";
                return $buffer;
            }
            if ($this->invokeCommand($input, $buffer)) {
                continue;
            }
            $buffer .= $input . "\n";
        }
    }

    private function invokeCommand(string $input, string &$buffer)
    {
        $commands = $this->commands;
        $commands[] = $this->notfoundcommand;
        foreach ($commands as $command) {
            if ($command->matches($input)) {
                $command->execute($buffer, $command->getLastMatchArgs());
                return true;
            }
        }
        return false;
    }
}
