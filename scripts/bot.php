#!/usr/local/bin/php
<?php

require_once(__DIR__ . "/../vendor/autoload.php");

/* listing 01.05 */
use getinstance\utils\aichat\control\Runner;
use getinstance\utils\aichat\control\ProcessUI;
use getinstance\utils\aichat\persist\ConvoSaver;

/* /listing 01.05 */
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

/* listing 01.05 */
function errorUsage(string $msg): void
{
    // ...
/* /listing 01.05 */
    fputs(STDERR, usage($msg));
/* listing 01.05 */
    exit(1);
}

// Set up the SQLite database connection
$convo = $argv[1] ?? "default";
$conffile = __DIR__ . "/../conf/chat.json";
$conf = json_decode(file_get_contents($conffile));
$conf->datadir ??= __DIR__ . "/../data";

$saver = new ConvoSaver($conf->datadir, $convo);
$runner = new Runner($conf, $saver);
$ui = new ProcessUI($runner);
$ui->run();

