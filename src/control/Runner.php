<?php

namespace getinstance\utils\aichat\control;
use getinstance\utils\aichat\ai\Comms;
use getinstance\utils\aichat\ai\models\GPT4;
use getinstance\utils\aichat\ai\models\GPT35;
use getinstance\utils\aichat\ai\Messages;
use getinstance\utils\aichat\persist\ConvoSaver;

class Runner
{
    private object $conf;
    private Messages $messages;
    private Messages $ctl;
    private ConvoSaver $saver;
    private string $datadir;

    public function __construct(object $conf, ConvoSaver $saver) {
        $this->conf = $conf;
        $this->comms = new Comms(new GPT4(), $this->conf->openai->token);

        $this->ctlcomms = new Comms(new GPT35(), $this->conf->openai->token);
        $this->ctlcomms->setResponseProportion(0.8);

        $this->saver = $saver;
        $convoconf = $saver->getConf();
        $premise = $convoconf["premise"] ?? null;
        $this->messages = new Messages($premise);
        $this->ctl = new Messages("You are an LLM management helper. You summarise messages and perform other meta tasks to help the user and primary assistant communicate well");
        $this->initMessages();
    }

    private function initMessages() {
        // load up messages from db for this chat
        $dbmsgs = $this->saver->getMessages(100); 
        foreach($dbmsgs as $dbmsg) {
            $this->messages->addMessage($dbmsg['role'], $dbmsg['text'], $dbmsg['tokencount'], $dbmsg['summary']);
        }

    //public function addMessage(string $role, string $message, int $tokens=0, string $summary="", int $summarytokens=0 ) {
    }

    public function getSaver() {
        return $this->saver;
    }

    public function getComms() {
        return $this->comms;
    }

    public function getMessages() {
        return $this->messages;
    }

    public function getPremise() {
        return $this->messages->getPremise();
    }

    public function setPremise(string $premise) {
        $this->messages->setPremise($premise);
        $convoconf = $this->saver->setConfVal("premise", $premise);
        
    }

    public function query(string $message, ?Messages $messages=null) {
        //$message = $message ?? "BLOOP";
        $msgs = $messages ?? $this->messages;
        $this->saver->saveMessage("user", $message);
        $msgs->addMessage("user", $message);
        //$count = $this->comms->countTokens($message);
        //return "fake response to query: $message ($count)\n";

        $resp = $this->comms->sendQuery($msgs);
        $this->saver->saveMessage("assistant", $resp);
        $this->saver->setConfVal("lastmessage", (new \DateTime("now"))->format("c"));
        return $resp;
    }

    public function summariseMostRecent() {
        $dbmsgs = $this->saver->getUnsummarisedMessages(3); 
        if (! count($dbmsgs)) {
            return;
        }
        $prompt = "Please summarise this message in 300 characters or fewer: ";
        foreach($dbmsgs as $dbmsg) {
            if (strlen($dbmsg['text']) <= 300) {
                $summary = $dbmsg['text'];
            } else {
                $this->ctl->addMessage("user", $prompt.$dbmsg['text']);
                $summary = $this->ctlcomms->sendQuery($this->ctl);
            }
            $this->saver->updateMessage($dbmsg['id'], $dbmsg['role'], $dbmsg['text'], $summary);
        }
    }

    public function save(string $convo, Messages $messages) {
        $payload = json_encode($messages->toArray());
        $path = $this->datadir ."/".$convo.".json";
        file_put_contents($path, $payload);
    }
}
