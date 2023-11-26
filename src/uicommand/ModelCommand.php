<?php

namespace getinstance\utils\aichat\uicommand;
class ModelCommand extends AbstractCommand
{
    public function execute(string &$buffer, array $args): void
    {
        $models = $this->runner->getModelMap();
        list($idx, $val) = $this->ui->picklist("Choose model", array_keys($models));
        // save it and set it
        $this->runner->setModel($models[$val]);
        print "# picked $val\n";

    }

    public function getName(): string
    {
        return "model";
    }

    public function getDescription(): string
    {
        return "<model> - Switch conversation to the given model";
    }
}
