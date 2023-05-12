<?php

namespace getinstance\utils\aichat\uicommand;

class EditCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'aichat');
        file_put_contents($tmpfile, $buffer);
        $editor = getenv('EDITOR') ?: 'vi';
        $descriptorspec = [
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR,
        ];
        $process = proc_open("$editor $tmpfile", $descriptorspec, $pipes);
        if (is_resource($process)) {
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_close($process);
            $buffer = file_get_contents($tmpfile);
            unlink($tmpfile);
        }
    }

    public function getName(): string
    {
        return "edit";
    }

    public function getDescription(): string
    {
        return "Edit the buffer with external editor";
    }
}

