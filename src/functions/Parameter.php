<?php

namespace getinstance\utils\aichat\functions;

class Parameter {
    private string $name;
    private string $type;
    private string $description;
    private array $enums;
    private bool $required;

    public function __construct(string $name, string $description, string $type, bool $required=false, string ...$enums)
    {
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->required =  $required;
        $this->enums = $enums;
    }
    public function isRequired() {
        return $this->required;
    }
    public function toArray(): array
    {
        $values = [
            "type" => $this->type,
            "description" => $this->description,
        ];
        if (! empty($this->enums)) {
            $values['enum'] = $this->enums;
        }
        return [
            $this->name,
            $values
        ];
    }
} 

/*
                    "properties": {
                        "location": {
                            "type": "string",
                            "description": "The city and state, e.g. San Francisco, CA",
                        },
                        "unit": {"type": "string", "enum": ["celsius", "fahrenheit"]},
                    },
                    "required": ["location"],
                },
*/
