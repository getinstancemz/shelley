<?php

namespace getinstance\utils\aichat\uicommand;

class ChatsCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $saver = $this->runner->getSaver();
        $convos = $saver->getConvos();
        print "# Chats (recently created first)\n";
        foreach ($convos as $convo) {
            print "#   {$convo['name']}\n";
        }
        print "#\n";
    }

    public function getName(): string
    {
        return "chats";
    }
}
