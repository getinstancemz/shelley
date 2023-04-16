<?php

namespace getinstance\utils\aichat\control;
use getinstance\utils\aichat\ai\Comms;
use getinstance\utils\aichat\ai\Messages;
use getinstance\utils\aichat\persist\ConvoSaver;

class Runner
{
    private object $conf;
    private Messages $messages;
    private ConvoSaver $saver;
    private string $datadir;

    public function __construct(object $conf, ConvoSaver $saver) {
        $this->conf = $conf;
        $this->comms = new Comms($this->conf->openai->token);
        $this->saver = $saver;
        $convoconf = $saver->getConf();
        $premise = $convoconf["premise"] ?? null;
        $this->messages = new Messages($premise);
    }

    public function getSaver() {
        return $this->saver;
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

    public function save(string $convo, Messages $messages) {
        $payload = json_encode($messages->toArray());
        $path = $this->datadir ."/".$convo.".json";
        file_put_contents($path, $payload);
    }
}
