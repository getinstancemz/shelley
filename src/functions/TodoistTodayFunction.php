<?php

namespace getinstance\utils\aichat\functions;

class TodoistTodayFunction extends AssistantFunction  {
    public function __construct()
    {
        parent::__construct(
            "todoisttoday",
            "Get the to do items due today"
        );
    }
}

/*
 *
 *
 *
 *
         {
            "type": "function",
            "function": {
                "name": "todoisttoday",
                "description": "Get the to do items due today",
                "parameters": {
                    "type": "object",
                    "properties": { },
                    "required": []
                }
            }
        }
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
            }
        }

*/
