<?php 
namespace getinstance\utils\aichat\uicommand;

use getinstance\utils\aichat\uicommand\AbstractCommand;
use getinstance\utils\aichat\uicommand\AbstractCommandFactory;
use getinstance\utils\aichat\uicommand\PremiseCommand;

class AssistantsCommandFactory extends AbstractCommandFactory
{
    function getPremiseCommand(): AbstractCommand {
        return new AssistantPremiseCommand($this->ui, $this->runner);
    }
}
