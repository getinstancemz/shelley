<?php

namespace getinstance\utils\aichat\control;

use getinstance\utils\aichat\ai\Comms;
use getinstance\utils\aichat\ai\models\GPT4;
use getinstance\utils\aichat\ai\models\GPT35;
use getinstance\utils\aichat\ai\Messages;
use getinstance\utils\aichat\persist\ConvoSaver;

/* listing 01.08 */
class Runner
{
    private Messages $messages;
/* /listing 01.08 */
    private Messages $ctl;
    private Comms $ctlcomms;
/* listing 01.08 */
    public function __construct(private object $conf, private ConvoSaver $saver)
    {
/* listing 01.14 */
/* /listing 01.08 */
        // Constructor

/* /listing 01.14 */
/* listing 01.08 */
        $this->comms = new Comms(new GPT35(), $this->conf->openai->token);
        $convoconf = $saver->getConf();
        $premise = $convoconf["premise"] ?? null;
        $this->messages = new Messages($premise);
        $this->initMessages();
/* /listing 01.08 */
/* listing 01.14 */
        $this->ctl = new Messages("You are an LLM client management helper. You summarise messages and perform other meta tasks to help the user and primary assistant communicate well");
        $this->ctlcomms = new Comms(new GPT35(), $this->conf->openai->token);
/* listing 01.08 */
/* /listing 01.14 */
    }

    private function initMessages(): void
    {
        $dbmsgs = $this->saver->getMessages(100);
        foreach ($dbmsgs as $msg) {
            $this->messages->addMessage($msg);
        }
    }
/* /listing 01.08 */

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

/* listing 01.09 */
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
/* /listing 01.09 */

/* listing 01.14 */

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
                $this->ctl->addNewMessage("user", $prompt . $dbmsg->getContent());
                $summary = $this->ctlcomms->sendQuery($this->ctl);
            }
            $dbmsg->setSummary($summary);
            $this->saver->saveMessage($dbmsg);
            //$this->saver->updateMessage($dbmsg['id'], $dbmsg['role'], $dbmsg['text'], $dbmsg['tokencount'], $summary, Comms::countTokens($summary));
        }
    }
/* /listing 01.14 */
/* listing 01.09 */
}
