<?php

namespace getinstance\utils\aichat\ai\assistants;

use hiddenhatpress\openai\assistants\AsstComms;
use hiddenhatpress\openai\assistants\Assistants;

class CommsManager
{

    private Assistants $assistants;
    private string $assistantid;
    private string $threadid;
    private array $asstentity;

    public function __construct(
        Assistants $assistants,
        string $name,
        string $premise,
        ?string $assistantid,
        ?string $threadid,
    ) {
        $this->assistants = $assistants;
        $this->getOrCreateAssistant($name, $premise, $assistantid, $threadid);
    }

    public function getOrCreateAssistant(string $name, string $premise, ?string $assistantid, ?string $threadid)
    {
        $asstservice = $this->assistants->getAssistantService();
        $threadservice = $this->assistants->getThreadService();


        if (empty($assistantid)) {
            $asstresp = $asstservice->create($name, $premise, ["retrieval"] );
            $this->assistantid = $asstresp['id'];
        } else {
            $asstresp = $asstservice->retrieve($assistantid);
            $this->assistantid = $assistantid;
        }

        if (empty($threadid)) {
            $threadresp = $threadservice->create();
            $this->threadid = $threadresp['id'];
        } else {
            $this->threadid = $threadid;
        }

        $this->asstentity = $asstresp;
        return [$this->assistantid, $this->threadid];
    }

    public function setPremise(string $premise): array
    {
		$this->asstentity['instructions'] = $premise;
		return $this->saveAsst();
    }

    public function setTool(string $tool): array
    {
		$this->asstentity['tools'] = [["type" => $tool]];
		$resp = $this->saveAsst();
        return $resp;
    }


	private function saveAsst()
	{
        $asstservice = $this->assistants->getAssistantService();
		$toolarray = [];
		$tools = $this->asstentity['tools'];
		foreach($tools as $tool) {
			$toolarray[] = $tool['type'];
		}
		return $asstservice->modify(
			$this->assistantid,
			$this->asstentity['name'],
			$this->asstentity['instructions'],
			$toolarray,
			$this->asstentity['file_ids'],
			$this->asstentity['description'],
			$this->asstentity['model']
		);
	}

    public function uploadAssistantFile(string $path): array
    {
        
        $fileservice = $this->assistants->getAssistantFileService();
        $fileresp = $fileservice->createAndAssignAssistantFile($this->assistantid, $path );
		if (! isset($fileresp['id'])) {
            print_r($fileresp);
			throw new \Exception("could not upload new version of file: '{$path}'");	
		}
        return $fileresp;
    }

    public function deleteAssistantFile(string $fileid): array
    {
        $fileservice = $this->assistants->getAssistantFileService();
        $fileresp = $fileservice->unassignAndDeleteAssistantFile($this->assistantid, $fileid);
		if (! empty($fileresp[0]['error']) || ! empty($fileresp[1]['error']) ) {
			$error = "unable to delete remote: $fileid";
			throw new \Exception($error);
		}
        return $fileresp;
    }

    public function listAssistantFiles(): array
    {
        $fileservice = $this->assistants->getAssistantFileService();
        $files = $fileservice->listAssistantFiles($this->assistantid);
        $ret = [];
        foreach ($files['data'] as $afile) {
            //$ret[] =$fileservice->retrieveAssistantFile($this->assistantid, $afile['id']);
            $ret[] =$fileservice->retrieveFile($afile['id']);
        }
        return $ret;
    }

    public function query(string $message)
    {
        $asstservice = $this->assistants->getAssistantService();
        $threadservice = $this->assistants->getThreadService();
        $messageservice = $this->assistants->getMessageService();
        $runservice = $this->assistants->getRunService();
        
        $runs = $runservice->listRuns($this->threadid);
        /*
        if (count($runs)) {
            print_r($runs);
        }
        exit;
        */
        $msgresp = $messageservice->create($this->threadid, $message);
        $runresp = $runservice->create($this->threadid, $this->assistantid);
        while(
            isset($runresp['status']) && (
                $runresp['status'] == "queued" || 
                $runresp['status'] == "in_progress" || 
                $runresp['status'] == "requires_action"
            )) {
            $runresp = $runservice->retrieve($this->threadid, $runresp['id']);
            if ($runresp['status'] == "requires_action") {
                $this->handleRequiresAction($runresp);
            }
            sleep(1);
        }
        if ( ! isset($runresp['status']) || $runresp['status'] != "completed" ) {
            throw new \Exception("unknown issue with query: ".print_r($runresp, true));
        }
        $msgs = $messageservice->listMessages($this->threadid);
        $resp = $msgs['data'][0]['content'][0]['text']['value'];
        //$asstmessage = new Message(-1, "assistant", $resp);
        //$this->saver->saveMessage($asstmessage);
        return $resp;
    }

    public function getAssistantFunctionNames(): array
    {
        $ret = [];
        foreach ($this->asstentity['tools'] as $tool) {
            if ($tool['type'] == "function") {
                $ret[] = $tool['function']['name'];
            }
        }
        return $ret;
    }

    public function handleRequiresAction(array $runresp): void
    {
        $runservice = $this->assistants->getRunService();
		$calls = $runresp['required_action']['submit_tool_outputs']['tool_calls'] ?? null;
		if (is_null($calls)) {
			throw new \Exception("unknown action data: ".print_r($runresp, true));
		}

		$outputs = [];
		foreach ($calls as $call) {
			$id = $call['id'];
			$function = $call['function']['name'];
			$arguments = $call['function']['arguments'];
			// would now call out to a resolver	
			$outputs[] = [
				"tool_call_id" => $id,
				"output" => "{[
                    'ai client development',
                    'PHP Book writing',
                    'Python Book writing'
                    ]}"
			];
		}
        print "# submitting outputs ".print_r($outputs); 	
		$toolresp = $runservice->submitToolOutputs(
			$this->threadid,
			$runresp['id'],	
			$outputs
		);
		//print_r($toolresp);

		// would now submit $output: https://platform.openai.com/docs/api-reference/runs/submitToolOutputs
/*
  "required_action": {
    "type": "submit_tool_outputs",
    "submit_tool_outputs": {
      "tool_calls": [
        {
          "id": "call_abc123",
          "type": "function",
          "function": {
            "name": "getCurrentWeather",
            "arguments": "{\"location\":\"San Francisco\"}"
          }
        },
*/
    }

    public function getMessages(): array {
        $messageservice = $this->assistants->getMessageService();
        $messages = $messageservice->listMessages($this->threadid);

        $marray  = $messages['data'];
        $ret = [];
        foreach ($marray as $msg) {
            $role = strtoupper($msg['role']);
            $ret[] = "{$role}> ".$msg['content'][0]['text']['value'];
        }
        return $ret;
    }

    public function getAssistant() {
        return $this->asstentity;
    }
    public function getAssistantId() {
        return $this->assistantid;
    }

    public function getThreadId() {
        return $this->threadid;
    }
}
