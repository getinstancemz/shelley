<?php

namespace getinstance\utils\aichat\control;

use getinstance\utils\aichat\ai\Message;
use hiddenhatpress\openai\assistants\Assistants;
use hiddenhatpress\openai\assistants\AsstComms;

use getinstance\utils\aichat\persist\FileManager;

use getinstance\utils\aichat\ai\assistants\CommsManager;

use getinstance\utils\aichat\uicommand\AssistantsCommandFactory;
use getinstance\utils\aichat\uicommand\AssistantAFileCommand;
use getinstance\utils\aichat\uicommand\AssistantAFileListCommand;
use getinstance\utils\aichat\uicommand\AssistantADirCommand;
use getinstance\utils\aichat\uicommand\AssistantDelFileCommand;
use getinstance\utils\aichat\uicommand\AssistantToolsCommand;

use getinstance\utils\aichat\ai\models\Model;
use getinstance\utils\aichat\ai\Messages;
use getinstance\utils\aichat\persist\ConvoSaver;

class AssistantModeRunner extends ModeRunner
{
    // for saving summaries
    private Messages $ctl;

    private CommsManager $comms;
    //private Comms $ctlcomms;
    //private Assistants $assistants;

    public function __construct(Runner $runner, ProcessUI $ui, object $conf, ConvoSaver $saver)
    {
        $commfactory = new AssistantsCommandFactory($runner, $ui);
        parent::__construct($runner, $ui, $commfactory, $conf, $saver, $commfactory);
        // commands
        $this->addCommand(new AssistantAFileCommand($ui, $runner, $this));
        $this->addCommand(new AssistantAFileListCommand($ui, $runner, $this));
        $this->addCommand(new AssistantDelFileCommand($ui, $runner, $this));
        $this->addCommand(new AssistantADirCommand($ui, $runner, $this));
        $this->addCommand(new AssistantToolsCommand($ui, $runner, $this));
        $this->initMessages();
    }


    public function getFileManager(): FileManager
    {
        return new FileManager($this->comms, $this->saver);
    }

    public function uploadAssistantDirectory($path): bool
    {
        $this->getFileManager()->addDir($path); 
        return true;
    }

    public function uploadAssistantFile($path): bool
    {
        //$resp = $this->comms->uploadAssistantFile($path);
        $this->getFileManager()->saveFile($path); 
        return true;
    }

    public function listAssistantFiles(): array
    {
        $resp = $this->comms->listAssistantFiles();
        return $resp;
    }

    public function delAssistantFile(string $path): bool
    {
        $this->getFileManager()->removeFile($path); 
        return true;
    }

    public function getPremise()
    {
        $asstentity = $this->comms->getAssistant();
        return $asstentity['instructions'];
    }

    public function setPremise(string $premise): bool
    {
        $convoconf = $this->saver->setConfVal("premise", $premise);
        $setresp = $this->comms->setPremise($premise);
        return true;
    }

    public function setTool(string $tool): bool
    {
        $setresp = $this->comms->setTool($tool);
        return true;
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
        // currently we don't set a model in Assistants mode
    }


    public function setupAssistant()
    {
        // TODO - fix model handling
        $convoconf = $this->saver->getConf();
        $modelmap = $this->runner->getModelMap();

        // hardcode for now
        $model = $modelmap['gpt-4-1106-preview'];

        // save model
        $this->saver->setConfVal("model", $model->getName());

        // get assistant comms
        $asstcomms = new AsstComms($model->getName(), $this->conf->openai->token);

        $assistants = new Assistants($asstcomms);
        $convoname = $this->saver->getConvoName();
        $premise = $convoconf["premise"]  ?? "You are an interested, inquisitive and helpful assistant";
        $threadid =  $convoconf['thread_id'] ?? null;
        $assistantid =  $convoconf['assistant_id'] ?? null;

        // CommsManager  will make the connection and create assistant / thread if needed
        $this->comms = new CommsManager($assistants, $convoname, $premise,  $assistantid, $threadid);
        $this->saver->setConfVal("assistant_id", $this->comms->getAssistantId());
        $this->saver->setConfVal("thread_id", $this->comms->getThreadId());

        /*
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
        */
    }

    public function initMessages(): void
    {
        //$convoconf = $this->saver->getConf();
        $this->setupAssistant();
    }

    public function query(string $message): string
    {
        $usermessage = new Message(-1, "user", $message);
        $this->saver->saveMessage($usermessage);

        $resp = $this->comms->query($message);

        $asstmessage = new Message(-1, "assistant", $resp);
        $this->saver->saveMessage($asstmessage);
        return $resp;
        /*
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
        return $resp;
        */
    }

    public function cleanUp()
    {
        $this->getFileManager()->uploadBatchFiles();
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
