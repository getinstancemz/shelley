<?php

namespace getinstance\utils\aichat\ai;
use Orhanerday\OpenAi\OpenAi;
use Gioni06\Gpt3Tokenizer\Gpt3TokenizerConfig;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;

class Comms
{
    private string $secretKey;
    private Gpt3Tokenizer $tokenizer;

    public function __construct($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function sendQuery(Messages $messages): string
    {
        return $this->sendQueryChat($messages);
        //return $this->sendQueryCompletion($messages);
    }

    public static function countTokens(string $str): int
    {
        $config = new Gpt3TokenizerConfig();
        $tokenizer = new Gpt3Tokenizer($config);
        $numberOfTokens = $tokenizer->count($str);
        return $numberOfTokens;
    }

    public function sendQueryCompletion(Messages $messages): string
    {
		$open_ai = new OpenAi($this->secretKey);
		$completion = $open_ai->complete([
			//'engine' => 'text-davinci-003',
			'engine' => 'text-davinci-003',
			'prompt' => $messages->toPrompt(5),
			'temperature' => 0.5,
			'max_tokens' => 400,
			'frequency_penalty' => 0,
			'presence_penalty' => 0.6,
		]);
        //print "sending:\n\n|". $messages->toPrompt(5)."|\n\n";
		$ret = json_decode($completion, true);
        if (! isset($ret['choices'][0]['text'])) {
			throw new \Exception($completion);
		}
        $response = $ret['choices'][0]['text'];
        $messages->addMessage("assistant", $response);
        return $response;
	}

    public function sendQueryChat(Messages $messages): string
    {
        //return "dummy2";
        /*
        print "## about to send:\n\n";
        print_r($messages->toArray(20, 3000));
        print "## done\n\n";
        exit;
        */

        /*
        $response = "responding...";
        $messages->addMessage("assistant", $response);
        return $response;
        */

        $open_ai = new OpenAi($this->secretKey);
        $completion = $open_ai->chat([
            'model' => 'gpt-4',
            'messages' => $messages->toArray(20, 3000),
            'temperature' => 0.5,
            'max_tokens' => 4000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0.6,
        ]);

        $ret = json_decode($completion, true);
        if (isset($ret['error'])) {
            throw new \Exception($ret['error']['message']);
        }
        if (! isset($ret['choices'][0]['message']['content'])) {
            throw new \Exception("Unknown error: " . $completion);
        }
		$response = $ret['choices'][0]['message']['content'];
        $messages->addMessage("assistant", $response);
        return $response;
    }
}
