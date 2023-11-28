<?php

namespace getinstance\utils\aichat\control;

use getinstance\utils\aichat\ai\Comms;
use getinstance\utils\aichat\ai\models\Model;
use getinstance\utils\aichat\ai\models\GPT4;
use getinstance\utils\aichat\ai\models\GPT35;
use getinstance\utils\aichat\ai\Messages;
use getinstance\utils\aichat\persist\ConvoSaver;

use getinstance\utils\aichat\control\ProcessUI;

use getinstance\utils\aichat\uicommand\AbstractCommand;
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
use getinstance\utils\aichat\uicommand\ModeCommand;

abstract class ModeRunner
{
    protected Messages $messages;
    protected Comms $comms;
    private array $commands = [];

    protected object $conf;
    protected ConvoSaver $saver;
    protected Runner $runner;

    public function __construct(Runner $runner, ProcessUI $ui, object $conf, ConvoSaver $saver)
    {
        $this->conf = $conf;
        $this->saver = $saver;
        $this->runner = $runner;

        $this->commands = [
            new HelpCommand($ui, $runner),
            new EditCommand($ui, $runner),
            new RedoCommand($ui, $runner),
            new DisplayBufferCommand($ui, $runner),
            new FileCommand($ui, $runner),
            new ContextCommand($ui, $runner),
            new PremiseCommand($ui, $runner),
            new ChatsCommand($ui, $runner),
            new UseCommand($ui, $runner),
            new DeleteConvoCommand($ui, $runner),
            new ModelCommand($ui, $runner),
            new ModeCommand($ui, $runner),
            // Add other command classes here
        ];

    }

    public function getCommands() {
        return $this->commands;
    }

    protected function addCommand(AbstractCommand $command): void
    {
        $this->commands[] = $command;
    }

    public abstract function setPremise(string $premise);

    public abstract function getPremise();

    public abstract function getMessageHistory(int $count = 1, int $maxtokens = 0);

    public abstract function initMessages(): void;

    public abstract function setModel(Model $model): void;
 
    public abstract function query(string $message);

    public abstract function cleanUp();
} 

