<?php 
namespace getinstance\utils\aichat\uicommand;
use getinstance\utils\aichat\control\Runner;
use getinstance\utils\aichat\control\ProcessUI;

abstract class AbstractCommandFactory 
{
    protected Runner $runner;
    protected ProcessUI $ui;

    public function __construct(Runner $runner, ProcessUI $ui) {
        $this->ui = $ui;
        $this->runner = $runner;
    }

    abstract function getPremiseCommand(): AbstractCommand;
}
