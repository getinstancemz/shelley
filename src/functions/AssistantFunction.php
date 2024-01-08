<?php

namespace getinstance\utils\aichat\functions;

abstract class AssistantFunction {

    protected string $name;
    protected string $description;
    protected array $parameters = [];

    public function __construct(string $name, string $description, Parameter ...$parameters)
    {
        $this->name = $name;
        $this->description = $description;
        $this->parameters = $parameters;
    }

	public function getName() {
        return $this->name;
    }
    public function getDescription(): string
    {
        return $this->description;
    }

    public function addParameter(Parameter $param) {
        $this->parameters[] = $param;
    }

    public function toArray() {
        $properties = [];
        $required = [];
        foreach($this->parameters as $parameter) {
            list($name, $values) = $parameter->toArray();
            $properties[$name] = $values;
            if ($property->isRequired()) {
                $required[] = $name;
            }
        }
        $ret = [
            "name" => $this->getName(),
            "description" => $this->getDescription(),
            "parameters" => [
                "type" => "object",
                "properties" => $properties,
                "required" => $required
            ]
        ];
        return $ret;
    }
}

/*
		{
            "type": "function",
            "function": {
                "name": "get_current_weather",
                "description": "Get the current weather in a given location",
                "parameters": {
                    "type": "object",
                    "properties": {
                        "location": {
                            "type": "string",
                            "description": "The city and state, e.g. San Francisco, CA",
                        },
                        "unit": {"type": "string", "enum": ["celsius", "fahrenheit"]},
                    },
                    "required": ["location"],
                },
            },
        }

*/
