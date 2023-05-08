<?php

namespace getinstance\utils\aichat\control;

use getinstance\utils\aichat\persist\ConvoSaver;
use getinstance\utils\aichat\uicommand\ArbitraryCommand;
use getinstance\utils\aichat\uicommand\RedoCommand;
use getinstance\utils\aichat\uicommand\DisplayBufferCommand;
use getinstance\utils\aichat\uicommand\FileCommand;
use getinstance\utils\aichat\uicommand\ContextCommand;
use getinstance\utils\aichat\uicommand\PremiseCommand;
use getinstance\utils\aichat\ai\Messages;

/* listing 01.06 */
class ProcessUI
{
/* /listing 01.06 */
    private array $commands = [];
    private ConvoSaver $saver;
/* listing 01.06 */
/* listing 01.17 */
    public function __construct(private Runner $runner)
    {
/* /listing 01.06 */
        $this->saver = $runner->getSaver();
        $this->commands = [
            new RedoCommand($runner),
            new DisplayBufferCommand($runner),
            new FileCommand($runner),
            new ContextCommand($runner),
            new PremiseCommand($runner),
            // Add other command classes here
        ];
/* listing 01.06 */
    }
/* /listing 01.17 */

/* /listing 01.06 */
    public function initSummarise(Messages $msgs)
    {
        // summarise current state of conversation
        $conversation = $this->saver->getConvo();
        $conf = $this->saver->getConf();

        print "# starting or resuming '{$conversation['name']}'\n";
        if (isset($conf['lastmessage'])) {
            print "# last conversation {$conf['lastmessage']}\n";
        }
        print "\n";
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
/* listing 01.06 */

/* listing 01.13 */
    public function run()
    {
/* /listing 01.06 */

    // ...
/* listing 01.06 */
/* /listing 01.13 */
        $msgs = $this->runner->getMessages();
/* /listing 01.06 */
        $this->initSummarise($msgs);
/* listing 01.06 */

/* listing 01.13 */
        $input = "";
        while (($input = $this->process("USER      > ")) != "q\n") {
/* /listing 01.13 */
            try {
                print "# sending\n";
                $resp = $this->runner->query($input);
            } catch (\Exception $e) {
                $resp = "** I'm sorry, I encountered an error:\n";
                $resp .= $e->getMessage();
            }
            print "ASSISTANT > {$resp} \n";
/* listing 01.13 */
/* /listing 01.06 */
            // ...

            print "# summarising...";
            $this->runner->summariseMostRecent();
            print " done\n";
/* listing 01.06 */
        }
    }
/* /listing 01.06 */
/* /listing 01.13 */

/* listing 01.07 */
/* listing 01.16 */
    private function process($prompt)
    {
        $buffer = "";
        while ($input = readline($prompt)) {
            $prompt = "";
/* /listing 01.07 */
/* /listing 01.16 */
            if ($this->hasContinuationEndChar($input, $buffer)) {
                continue;
            }
            if ((new ArbitraryCommand($this->runner, "m"))->matches($input)) {
                $final = $this->processMulti($buffer);
                return $final;
            }

/* listing 01.07 */
/* listing 01.16 */
            if (! $this->invokeCommand($input, $buffer)) {
                $buffer .= $input;
                break;
            }
        }

        return $buffer;
    }

/* /listing 01.16 */
/* /listing 01.07 */
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
            if ($this->isCommand($input, $buffer)) {
                continue;
            }
            if ((new ArbitraryCommand($this->runner, "e"))->matches($input)) {
                print "# sending\n";
                return $buffer;
            }
            $buffer .= $input . "\n";
        }
    }

/* listing 01.07 */
/* listing 01.16 */
    private function invokeCommand(string $input, string &$buffer)
    {
/* /listing 01.07 */
        foreach ($this->commands as $command) {
            if ($command->matches($input)) {
                $command->execute($buffer, $command->getLastMatchArgs());
                return true;
            }
        }
/* listing 01.07 */
        return false;
    }
/* /listing 01.16 */
}
