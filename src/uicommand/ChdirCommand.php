<?php
namespace getinstance\utils\aichat\uicommand;

use getinstance\utils\aichat\control\Runner;
use getinstance\utils\aichat\persist\ConvoSaver;

class ChdirCommand extends AbstractCommand
{

    public function execute(string &$buffer, array $args): void
    {
        $saver = $this->runner->getSaver();
        if (empty($args[0])) {
            $cwd = $saver->getConfVal('cwd');
            if (is_null($cwd)) {
                $cwd = realpath(getcwd());
                $saver->setConfVal('cwd', $cwd);
                print "# current directory not yet stored.\n";
                print "# using '{$cwd}'\n";
                return;
            } else {
                print "# no argument provided\n";
                return;
            }
        }
        $directory = trim($args[0]); // Trim any whitespace from the argument

        // Check if the directory is valid
        if (!is_dir($directory)) {
            print "# directory '{$directory}' does not appear to existi\n";
            return;
        }
        $directory = realpath($directory);
        // Change and save the current working directory
        $saver->setConfVal('cwd', $directory);
        chdir($directory);
        print "# changed working directory to '{$directory}' and stored for future runs\n";
    }

    public function getName(): string
    {
        return "chdir";
    }

    public function getDescription(): string
    {
        return "<directory> - Change the working directory. This will be used for future runs.";
    }
}
