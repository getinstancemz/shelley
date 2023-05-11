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

If you don't want to rely on an environment variable you can also edit copy the `conf/chat.json.sample` file to `conf/chat.json` and then edit the relevant field.

To start a named converation rather than the default conversation:

```
$ php scripts/shelley.php my-conversation-name
```
