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

// Set up the SQLite database connection
$convo = $argv[1] ?? "default";
$conffile = __DIR__ . "/../conf/chat.json";

if (file_exists($conffile)) {
    $conf = json_decode(file_get_contents($conffile));
} else {
    $conf = new stdClass();
    $conf->openai = new stdClass();
}

$conf->openai->token ??= getenv('OPENAI_API_KEY');

if (empty($conf->openai->token)) {
    errorUsage("could not find OpenAI token");
}

$conf->datadir ??= __DIR__ . "/../data";

$saver = new ConvoSaver($conf->datadir, $convo);
$runner = new Runner($conf, $saver);
$runner->run();
