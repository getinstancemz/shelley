<?php

namespace getinstance\utils\aichat\control;

use getinstance\utils\aichat\ai\Comms;
use getinstance\utils\aichat\ai\models\Model;
use getinstance\utils\aichat\ai\models\GPT4;
use getinstance\utils\aichat\ai\models\GPT35;
use getinstance\utils\aichat\ai\Messages;
use getinstance\utils\aichat\persist\ConvoSaver;

class Runner
{
    private Messages $messages;
    private Messages $ctl;
    private Comms $ctlcomms;
    public function __construct(private object $conf, private ConvoSaver $saver)
    {
        $this->initMessages();
        $this->ctl = new Messages("You are an LLM client management helper. You summarise messages and perform other meta tasks to help the user and primary assistant communicate well");
        $this->ctlcomms = new Comms(new GPT35(), $this->conf->openai->token);
    }

    public function switchConvo(string $name)
    {
        if (! $this->saver->hasConvo($name)) {
            throw new \Exception("No conversation: $name");
        }
        $this->saver->useConvo($name);
        $this->initMessages();
    }

    public function getModel(): Model
    {
        return $this->comms->getModel();
    }

    public function setModel(Model $model): void
    {
        $this->comms->setModel($model);
    }

    private function initMessages(): void
    {
        $convoconf = $this->saver->getConf();
        $premise = $convoconf["premise"] ?? null;
        $models = [
            "gpt-3.5-turbo" => new GPT35(),
            "gpt-4" => new GPT4()
        ];
        if (isset($convoconf['model']) && isset($models[$convoconf['model']])) {
            $model = $models[$convoconf['model']];
        } else {
            $model = new GPT35();
        }
        $this->comms = new Comms($model, $this->conf->openai->token);
        $premise = $convoconf["premise"] ?? null;
        $this->messages = new Messages($premise);
        $dbmsgs = $this->saver->getMessages(100);
        foreach ($dbmsgs as $msg) {
            $this->messages->addMessage($msg);
        }
    }

    public function getSaver()
    {
        return $this->saver;
    }

    public function getComms()
    {
        return $this->comms;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function getPremise()
    {
        return $this->messages->getPremise();
    }

    public function setPremise(string $premise)
    {
        $this->messages->setPremise($premise);
        $convoconf = $this->saver->setConfVal("premise", $premise);
    }

    public function query(string $message, ?Messages $messages = null)
    {
        $msgs = $messages ?? $this->messages;
        $usermessage = $msgs->addNewMessage("user", $message);
        $this->saver->saveMessage($usermessage);
        $resp = $this->comms->sendQuery($msgs);
        $asstmessage = $msgs->addNewMessage("assistant", $resp);
        $this->saver->saveMessage($asstmessage);
        $this->saver->setConfVal("lastmessage", (new \DateTime("now"))->format("c"));
        return $resp;
    }

    public function summariseMostRecent()
    {
        $dbmsgs = $this->saver->getUnsummarisedMessages(3);
        if (! count($dbmsgs)) {
            return;
        }
        $prompt = "Please summarise this message in 300 characters or fewer: ";
        foreach ($dbmsgs as $dbmsg) {
            if (strlen($dbmsg->getContent()) <= 300) {
                $summary = $dbmsg->getContent();
            } else {
                try {
                    $this->ctl->addNewMessage("user", $prompt . $dbmsg->getContent());
                    $summary = $this->ctlcomms->sendQuery($this->ctl);
                } catch (\Exception $e) {
                    // probably too large -- fall back
                    $summary = $dbmsg->getContextSummary();
                }
            }
            $dbmsg->setSummary($summary);
            $this->saver->saveMessage($dbmsg);
        }
    }
}
