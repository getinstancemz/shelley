<?php

namespace getinstance\utils\aichat\uicommand;

class HelpCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        print "# UI Commands:\n";
        foreach($this->ui->getCommands() as $cmd) {
            print "#    ".str_pad("/".$cmd->getName(), 10) ."- ". $cmd->getDescription()."\n";
        }
        print "#    ".str_pad("/m", 10) ."- Switch into multi-line mode. Sending /e will end the mode and submit\n";
    }

    public function getName(): string
    {
        return "help";
    }

    public function getDescription(): string
    {
        return "Describe UI commands";
    }

}
