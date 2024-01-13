#!/usr/local/bin/php
<?php

require_once(__DIR__ . "/../vendor/autoload.php");

use getinstance\utils\aichat\control\Runner;
use getinstance\utils\aichat\control\ProcessUI;
use getinstance\utils\aichat\persist\ConvoSaver;

function usage(?string $msg = null): string
{
    $argv = $GLOBALS['argv'];
    $usage  = "\n";
    $usage .= sprintf("usage: %s [convo]\n", $argv[0]);
    $usage .= "    -h        this help message\n";
    $usage .= "    -k        kill any key set in local storage\n";
    $usage .= "\n";
    if (! is_null($msg)) {
        $usage .= "$msg\n\n";
    }
    return $usage;
}

function errorUsage(string $msg): void
{
    // ...
    fputs(STDERR, usage($msg));
    exit(1);
}

$options = getopt("kh", [], $rest_index);
$myargs = array_slice($argv, $rest_index);

if (isset($options['h'])) {
    print usage(); 
    exit(0);
}

// Set up the SQLite database connection
$convo = $myargs[0] ?? "default";
$conffile = __DIR__ . "/../conf/chat.json";

if (file_exists($conffile)) {
    $conf = json_decode(file_get_contents($conffile));
} else {
    $conf = new stdClass();
    $conf->openai = new stdClass();
}

$conf->datadir ??= __DIR__ . "/../data";
$saver = new ConvoSaver($conf->datadir, $convo);

if (isset($options['k'])) {
    $saver->deleteSysConfVal("openai_token");
}

$conf->openai->token ??= getenv('OPENAI_API_KEY');
$sysconf = $saver->getSysConf();
if (empty($conf->openai->token) && ! empty($sysconf['openai_token'])) {
    $conf->openai->token = $sysconf['openai_token'];
}


if (empty($conf->openai->token)) {
    print "# no openai key found. Add one at the prompt or quit and rerun having either\n";
    print "# - set up the configuration file at conf/chat.json\n";
    print "# - set the environment variable OPENAI_API_KEY\n\n";
    print "#"; 

    $prompt = "Please enter the AI key to proceed ('q' to quit)";

    while ($input = readline("# {$prompt}: ")) {
        $input = trim($input);
        if (empty($input)) {
            continue;
        }
        if ($input == "q") {
            exit(0);
        }
        $conf->openai->token = $input;
        $saver->setSysConfVal("openai_token", $input);
        break;
    }
}


$runner = new Runner($conf, $saver);
$ui = new ProcessUI($runner);
$ui->run();

