# Shelley
A PHP OpenAI client designed to be run from the command line

## Requirements
* PHP 8.1 or greater
* Sqlite3
* [composer](getcomposer.org/)

## Quick Start

```
$ composer install
$ export OPENAI_API_KEY=sk-your-open-ai-key-kjksjdkfjd
$ php scripts/shelley.php
```

If you don't want to rely on an environment variable you can also copy the `conf/chat.json.sample` file to `conf/chat.json` and then edit the relevant field.

To start a named conversation rather than the default conversation:

```
$ php scripts/shelley.php my-conversation-name
```

## UI Commands
Run commands on their own line.

If you need to get to a new line without submitting can just add the continuation character (`/` or `\`) to the end of your current line and hit return

| Command | Description |
|---------|-------------|
| /help    | Describe UI commands |
| /redo    | Wipes the buffer |
| /buf     | Outputs the contents of the current buffer (the text you're about to send) |
| /file    | &lt;path&gt; - Will include the contents of the given path in your buffer |
| /context | Prints the full context for the current conversation (warning: will spam your buffer) |
| /premise | [text] - Sets assistant premise for the conversation if [text] provided. Shows the premise otherwise |
| /chats   | Lists all conversations in reverse order of creation |
| /use     | &lt;name&gt; - Switches conversation to the named chat |
| /del     | &lt;name&gt; - Deletes the named chat (will recreate an empty `default` if named) |
| /m       | Switch into multi-line mode. Sending /e will end the mode and submit |

