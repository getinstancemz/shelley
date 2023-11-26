<?php

namespace getinstance\utils\aichat\control;

use getinstance\utils\aichat\ai\Comms;
use getinstance\utils\aichat\ai\models\Model;
use getinstance\utils\aichat\ai\Messages;
use getinstance\utils\aichat\persist\ConvoSaver;

use getinstance\utils\aichat\ai\models\GPT4;
use getinstance\utils\aichat\ai\models\GPT35;

class AssistantModeRunner extends ModeRunner
{
    // for saving summaries
    private Messages $ctl;
    private Comms $ctlcomms;

    public function __construct(Runner $runner, ProcessUI $ui, object $conf, ConvoSaver $saver)
    {
        parent::__construct($runner, $ui, $conf, $saver);

        $this->initMessages();
        $this->ctl = new Messages("You are an LLM client management helper. You summarise messages and perform other meta tasks to help the user and primary assistant communicate well");
        $this->ctlcomms = new Comms(new GPT35(), $this->conf->openai->token);
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

    public function getMessageHistory(int $count = 1, int $maxtokens = 0)
    {
        $context = $this->messages->toArray($count, $maxtokens);
        return $context;
    }

    public function setModel(Model $model): void
    {
        $this->comms->setModel($model);
    }

    public function initMessages(): void
    {
        $convoconf = $this->saver->getConf();
        $premise = $convoconf["premise"] ?? null;
        $model = $this->runner->getModel(); 
        $this->comms = new Comms($model, $this->conf->openai->token);
        $premise = $convoconf["premise"] ?? null;
        $this->messages = new Messages($premise);
        $dbmsgs = $this->saver->getMessages(100);
        foreach ($dbmsgs as $msg) {
            $this->messages->addMessage($msg);
        }
    }

    public function query(string $message)
    {
        $msgs = $this->messages;
        $usermessage = $msgs->addNewMessage("user", $message);
        $this->saver->saveMessage($usermessage);
        $resp = $this->comms->sendQuery($msgs);
        $asstmessage = $msgs->addNewMessage("assistant", $resp);
        $this->saver->saveMessage($asstmessage);
        $this->saver->setConfVal("lastmessage", (new \DateTime("now"))->format("c"));
        return $resp;
    }

    public function cleanUp()
    {
        $this->summariseMostRecent();
    }

    private function summariseMostRecent()
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
