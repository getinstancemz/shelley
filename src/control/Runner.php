<?php

namespace getinstance\utils\aichat\control;

use getinstance\utils\aichat\ai\models\GPT4_1106_Preview;
use getinstance\utils\aichat\ai\models\GPT4_32k;
use getinstance\utils\aichat\ai\models\GPT4;
use getinstance\utils\aichat\ai\models\GPT35;


use getinstance\utils\aichat\ai\Comms;
use getinstance\utils\aichat\ai\models\Model;
use getinstance\utils\aichat\ai\Messages;
use getinstance\utils\aichat\persist\ConvoSaver;

use getinstance\utils\aichat\control\ProcessUI;

class Runner
{
    private ProcessUI $ui;
    private ModeRunner $moderunner;

    public function __construct(private object $conf, private ConvoSaver $saver)
    {
        $this->ui = new ProcessUI($this);
        $this->moderunner = $this->getModeRunner();
    }

    // managed here //////////////////////////////////////////////////////////////////
    
    public function getMode()
    {
        $convoconf = $this->saver->getConf();
        $mode = $convoconf["mode"] ?? "chat";
        return $mode;
    }

    public function getModeRunner()
    {
        if ($this->getMode() == "chat") {
            return new ChatModeRunner($this, $this->ui, $this->conf, $this->saver);
        } else if($this->getMode() == "assistant") {
            return new AssistantModeRunner($this, $this->ui, $this->conf, $this->saver);
        }
        throw new \Exception("unknown mode: ".$this->getMode());
    }

    public function switchToAssistant()
    {
        $this->saver->setConfVal("mode", "assistant");
        $this->moderunner = new AssistantModeRunner($this, $this->ui, $this->conf, $this->saver);
    }

    public function switchToChat()
    {
        $this->saver->setConfVal("mode", "chat");
        $this->moderunner = new ChatModeRunner($this, $this->ui, $this->conf, $this->saver);
    }


    public function run(): void
    {
        $this->ui->run();
    }

    public function getConf()
    {
        $conf = $this->saver->getConf();
        return $conf;
    }

    public function switchConvo(string $name)
    {
        if (! $this->saver->hasConvo($name)) {
            throw new \Exception("No conversation: $name");
        }
        $this->saver->useConvo($name);
        $this->initMessages();
    }

    public function getConvo()
    {
        $conversation = $this->saver->getConvo();
        return $conversation;
    }

    public function getModelMap(): array {
        $models = [
            "gpt-4" => new GPT4(),
            "gpt-4-0613" => new GPT4_32k(),
            "gpt-4-1106-preview" => new GPT4_1106_Preview(),
            "gpt-3.5-turbo" => new GPT35(),
        ];
        return $models;
    }

    public function getModel(): Model
    {
        $val = $this->saver->getConfVal("model");
        $val ??= "gpt-3.5-turbo";
        $map = $this->getModelMap();
        if (! isset($map[$val])) {
            throw new \Exception("unknown model '{$val}'");
        }
        return $map[$val];
    }

    public function getSaver()
    {
        return $this->saver;
    }

    // delegated  //////////////////////////////////////////////////////////////////

    public function getMessageHistory(int $count = 1, int $maxtokens = 0)
    {
        return $this->moderunner->getMessageHistory($count, $maxtokens);
    }

    public function getCommands(): array
    {
        return $this->moderunner->getCommands();
    }

    public function setModel(Model $model): void
    {
        $this->saver->setConfVal("model", $model->getName());
        $this->moderunner->setModel($model);
    }

    private function initMessages(): void
    {
        $this->moderunner->initMessages();
    }

    public function getPremise()
    {
        return $this->moderunner->getPremise();
    }

    public function setPremise(string $premise)
    {
        return $this->moderunner->setPremise($premise);
    }

    public function query(string $message)
    {
        return $this->moderunner->query($message);
    }

    public function cleanUp()
    {
        return $this->moderunner->cleanUp();
    }
}
