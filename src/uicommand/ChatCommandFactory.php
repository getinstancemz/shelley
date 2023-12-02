<?php 
namespace getinstance\utils\aichat\uicommand;

class ChatCommandFactory extends AssistantsCommandFactory
{
    function getPremiseCommand(): AbstractCommand {
        return new ChatPremiseCommand($this->ui, $this->runner);
    }
}
