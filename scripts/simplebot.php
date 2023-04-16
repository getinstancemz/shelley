#!/usr/local/bin/php
<?php

require_once(__DIR__ . "/../vendor/autoload.php");

use getinstance\utils\aichat\control\Runner;

$runner = new Runner();
$msgs = $runner->start();
$input = "";
while (($input = readline("USER      > ")) != "q") {
    $resp = $runner->query($msgs, $input);
    print "\n";
    print "ASSISTANT > {$resp} \n";
    print "\n";
}

