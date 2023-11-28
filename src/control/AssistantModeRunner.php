<?php

namespace getinstance\utils\aichat\control;

use getinstance\utils\aichat\ai\Message;
use hiddenhatpress\openai\assistants\Assistants;
use hiddenhatpress\openai\assistants\AsstComms;

use getinstance\utils\aichat\uicommand\assistants\AFileCommand;

use getinstance\utils\aichat\ai\models\Model;
use getinstance\utils\aichat\ai\Messages;
use getinstance\utils\aichat\persist\ConvoSaver;

class AssistantModeRunner extends ModeRunner
{
    // for saving summaries
    private Messages $ctl;
    private Comms $ctlcomms;
    private Assistants $assistants;

    private array $asstentity = []; 


    public function __construct(Runner $runner, ProcessUI $ui, object $conf, ConvoSaver $saver)
    {
        parent::__construct($runner, $ui, $conf, $saver);
        print "hello!";
        // commands
        $this->addCommand(new AFileCommand($ui, $runner));
        $this->initMessages();
    }
   
    public function getPremise()
    {
        return $this->asstentity['instructions'];
    }

    public function setPremise(string $premise)
    {
        $this->messages->setPremise($premise);
        $convoconf = $this->saver->setConfVal("premise", $premise);
    }

    public function getMessageHistory(int $count = 1, int $maxtokens = 0): array
    {
        $messages = $this->saver->getMessages($count);
        return array_map(function ($val) {
            return $val->getOutputArray();
        }, $messages);
        //$context = $this->messages->toArray($count, $maxtokens);
        //return $context;
    }

    public function setModel(Model $model): void
    {
        $this->comms->setModel($model);
    }

    public function setupAssistant()
    {
        // TODO - fix model handling
        $convoconf = $this->saver->getConf();
        $convoname = $this->saver->getConvoName();
        $modelmap = $this->runner->getModelMap();

        // hardcode for now
        $model = $modelmap['gpt-4-1106-preview'];

        // save model
        $this->saver->setConfVal("model", $model->getName());

        // get assistant comms
        $asstcomms = new AsstComms($model->getName(), $this->conf->openai->token);
        $this->assistants = new Assistants($asstcomms);
        $premise = $convoconf["premise"]  ?? "You are an interested, inquisitive and helpful assistant";

        $asstservice = $this->assistants->getAssistantService();
        $threadservice = $this->assistants->getThreadService();

        // get or create asst info
        if (! empty($convoconf['assistant_id'])) {
            if (empty($convoconf['thread_id'])) {
                $asstservice->del($convoconf['assistant_id']);
                $this->saver->delConfVal('assistant_id');
                print "# OOPS - I had an assistant id but not a thread id -- attempting to recreate\n";
                return $this->setupAssistant();
            }
            $asstresp = $asstservice->retrieve($convoconf['assistant_id']);
            $message = "retrieved existing";
        } else {
            //$premise = $this->saver->getConfVal("premise") ?? "You are an interested, inquisitive and helpful assistant";
            $asstresp = $asstservice->create( $convoname, $premise, ["retrieval"] );
            $this->saver->setConfVal("assistant_id", $asstresp['id']);
            $threadresp = $threadservice->create();
            $this->saver->setConfVal("thread_id", $threadresp['id']);
            $message = "created new";
        }
        $this->asstentity = $asstresp;
    }

    public function initMessages(): void
    {
        $convoconf = $this->saver->getConf();
        $this->setupAssistant();
        /*
        if (! isset($convoconf['assistant_id'])) {
            $this->setupAssistant();
            exit;
        }
        */

/*
        $premise = $convoconf["premise"] ?? null;
        $model = $this->runner->getModel(); 
        $this->comms = new Comms($model, $this->conf->openai->token);
        $premise = $convoconf["premise"] ?? null;
        $this->messages = new Messages($premise);
        $dbmsgs = $this->saver->getMessages(100);
        foreach ($dbmsgs as $msg) {
            $this->messages->addMessage($msg);
        }
*/
    }

    public function query(string $message)
    {
        $usermessage = new Message(-1, "user", $message);
        $this->saver->saveMessage($usermessage);

        $convoconf = $this->saver->getConf();
        $asstservice = $this->assistants->getAssistantService();
        $threadservice = $this->assistants->getThreadService();
        $messageservice = $this->assistants->getMessageService();
        $runservice = $this->assistants->getRunService();

        $assistantid = $convoconf['assistant_id'];
        $threadid = $convoconf['thread_id'];
        $msgresp = $messageservice->create($threadid, $message);
        $runresp = $runservice->create($threadid, $assistantid);
        while($runresp['status'] != "completed") {
            //print "# polling {$runresp['status']}\n";
            $runresp = $runservice->retrieve($threadid, $runresp['id']);
            sleep(1);
        }
        $msgs = $messageservice->listMessages($threadid);
        $resp = $msgs['data'][0]['content'][0]['text']['value'];
        $asstmessage = new Message(-1, "assistant", $resp);
        $this->saver->saveMessage($asstmessage);
        return $resp;
    }

    public function cleanUp()
    {
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
