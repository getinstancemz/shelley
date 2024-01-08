<?php

namespace getinstance\utils\aichat\persist;

use getinstance\utils\aichat\ai\Comms;
use getinstance\utils\aichat\ai\Message;

class ConvoSaver
{
    private \PDO $pdo;
    private int $convo_id;
    private string $datadir;
    private string $convoname;

    public function __construct(string $datadir, string $convoname)
    {
        if (! file_exists($datadir)) {
            mkdir($datadir, 0755) || throw new \Exception("could not create directory ($datadir)");
        }
        $this->datadir = $datadir;
        $db_file = realpath($datadir) . '/chatbot.db';
        $this->pdo = new \PDO(
            "sqlite:" . $db_file,
            null,
            null,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        $this->useConvo($convoname);
    }

    public function getDataDir(): string
    {
        return $this->datadir;
    }

    public function hasConvo(string $name): bool|int
    {
        $stmt = $this->pdo->prepare("SELECT * FROM conversation WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $convos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return (count($convos)) ? $convos[0]['id']:false;
    }

    public function deleteConvoAndMessages(string $name)
    {
        if (! ($id = $this->hasConvo($name))) {
            throw new \Exception("Unknown conversation '{$name}'");
        }
        $stmt = $this->pdo->prepare("DELETE FROM conversation WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $stmt = $this->pdo->prepare("DELETE FROM messages WHERE conversation_id = :id");
        $stmt->execute([':id' => $id]);
        
        $stmt = $this->pdo->prepare("DELETE FROM convoconf WHERE conversation_id = :id");
        $stmt->execute([':id' => $id]);

    }

    public function getConvo(?int $id=null): array
    {
        $id = (is_null($id))?$this->convo_id:$id;
        $stmt = $this->pdo->prepare("SELECT * FROM conversation WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $convo = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($convo === false) {
            throw new \Exception("Conversation not found.");
        }

        return $convo;
    }

    public function getConvos(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM conversation ORDER BY id DESC");
        $stmt->execute();
        $convos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $convos;
    }

    public function createOrAccessDb(): void
    {
        // Create tables if they don't exist
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS conversation (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS convoconf (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            confkey TEXT,
            confval TEXT,
            conversation_id INTEGER,
            FOREIGN KEY (conversation_id) REFERENCES conversation(id)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role TEXT,
            text TEXT,
            tokencount DEFAULT 0,
            summary DEFAULT \"\",
            summarytokencount DEFAULT 0,
            conversation_id INTEGER,
            type TEXT,
            FOREIGN KEY (conversation_id) REFERENCES conversation(id)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS afile (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            conversation_id TEXT,
            path TEXT,
            size INTEGER default 0,
            filehash TEXT,
            batchid INTEGER DEFAULT 0,
            FOREIGN KEY (conversation_id) REFERENCES conversation(id),
            FOREIGN KEY (batchid) REFERENCES batchfile(id)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS batchfile (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            remoteid TEXT DEFAULT \"\",
            size INTEGER DEFAULT 0,
            status TEXT,
            conversation_id TEXT,
            FOREIGN KEY (conversation_id) REFERENCES conversation(id)
        )");

    }

    public function deleteBatchFile(int $id)
    {
        $max = 15;
		$stmt = $this->pdo->prepare("DELETE FROM batchfile WHERE id = :id and conversation_id = :conversation_id");
		$stmt->execute([':id' => $id, ':conversation_id' => $this->convo_id]);
        return true;
    }

    public function getNextBatchFile()
    {
        $max = 15;
		$stmt = $this->pdo->prepare("SELECT * FROM batchfile WHERE conversation_id = :conversation_id ORDER BY size");
		$stmt->execute([':conversation_id' => $this->convo_id]);
		$files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (count($files) < $max) {
            $name = "batch" . (count($files)+1);
            return $this->newBatchFile($name);
        }
        // otherwise return the smallest sized file
        return $files[0];
    }

    public function newBatchFile(string $name): array
    {
        $existing = $this->getBatchFileByName($name);
        if (! is_null($existing)) {
            throw new \Exception("Batch file $name already exists for conversation ({$this->convo_id})");
        }
        $stmt = $this->pdo->prepare(
            "INSERT INTO batchfile (name, status, conversation_id) 
            VALUES (:name, 'init', :conversation_id)
        ");
		$stmt->execute([
            ':conversation_id' => $this->convo_id,
            ':name' => $name,
        ]);
        return [
            "id" => $this->pdo->lastInsertId(),
            "name" => "$name",
            "status" => "init",
            "size" => 0 ,
            "conversation_id" => $this->convo_id
        ];
    }

	public function getBatchFilesToUpload(): array
    {
		$stmt = $this->pdo->prepare("SELECT * FROM batchfile WHERE status='queued' AND conversation_id = :conversation_id");
		$stmt->execute([':conversation_id' => $this->convo_id]);
		$files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $files;
    }

	public function getFilesByBatch(int $batchid): array
    {
		$stmt = $this->pdo->prepare("SELECT * FROM afile WHERE batchid=:batchid AND conversation_id = :conversation_id");
		$stmt->execute([':batchid' => $batchid, ':conversation_id' => $this->convo_id]);
		$files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $files;
    }

	public function getBatchFile(string $id): ?array
    {
		$stmt = $this->pdo->prepare("SELECT * FROM batchfile WHERE id= :id");
		$stmt->execute([':id' => $id]);
		$file = $stmt->fetch(\PDO::FETCH_ASSOC);
		return is_array($file) ? $file : null;
    }

	public function getBatchFileByName(string $name): ?array
	{
		$stmt = $this->pdo->prepare("SELECT * FROM batchfile WHERE name= :name AND conversation_id = :conversation_id");
		$stmt->execute([':name' => $name, ':conversation_id' => $this->convo_id]);
		$file = $stmt->fetch(\PDO::FETCH_ASSOC);
		return is_array($file) ? $file : null;
	}

	public function getFileByPath(string $path): ?array
	{
		$stmt = $this->pdo->prepare("SELECT * FROM afile WHERE path = :path AND conversation_id = :conversation_id");
		$stmt->execute([':path' => $path, ':conversation_id' => $this->convo_id]);
		$file = $stmt->fetch(\PDO::FETCH_ASSOC);
		return is_array($file) ? $file : null;
	}

	public function getFiles(): array
    {
		$stmt = $this->pdo->prepare("SELECT * FROM afile WHERE conversation_id = :conversation_id");
		$stmt->execute([':conversation_id' => $this->convo_id]);
		$files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $files;
    }

	public function getFileByRemoteId(string $remoteid): ?array
	{
		$stmt = $this->pdo->prepare("SELECT * FROM afile WHERE remoteid= :remoteid AND conversation_id = :conversation_id");
		$stmt->execute([':remoteid' => $remoteid, ':conversation_id' => $this->convo_id]);
		$file = $stmt->fetch(\PDO::FETCH_ASSOC);
		return is_array($file) ? $file : null;
	}

	public function markBatchFileForUpload(string $batchid, int $additionalsize): bool
    {
        $batch = $this->getBatchFile($batchid);  
        $oldsize = $batch['size'];
        $newsize = $oldsize + $additionalsize;
		$stmt = $this->pdo->prepare("UPDATE batchfile SET size = :size, status = 'queued' WHERE id = :id");
		$stmt->execute([':id' => $batchid, ':size' => $newsize]);
        return true;
    }

	public function markBatchFileWritten(string $batchid, string $remoteid): bool
    {
		$stmt = $this->pdo->prepare("UPDATE batchfile SET status = 'written', remoteid = :remoteid WHERE id = :id");
		$stmt->execute([':id' => $batchid, ':remoteid' => $remoteid]);
        return true;
    }

	public function removeFile(string $id): bool
	{
		$stmt = $this->pdo->prepare("DELETE FROM afile WHERE id= :id");
		$stmt->execute([':id' => $id]);
		return true;
	}

	public function addOrUpdateFile(string $path, string $filehash, int $size, int $batchid): array
	{
		$file = $this->getFileByPath($path);
		$writearr = [
				':path' => $path,
				':filehash' => $filehash,
				':size' => $size,
				':batchid' => $batchid,
				':conversation_id' => $this->convo_id
			];
		if (is_array($file)) {
			// Update the existing file
			$stmt = $this->pdo->prepare("
                UPDATE afile SET
                batchid= :batchid,
                filehash = :filehash,
                size= :size
                WHERE 
                path = :path AND 
                conversation_id = :conversation_id
            ");
			$stmt->execute($writearr);
		} else {
			// Add a new file
			$stmt = $this->pdo->prepare("INSERT INTO afile (path, filehash, size, batchid, conversation_id) VALUES ( :path, :filehash, :size, :batchid, :conversation_id)");
			$stmt->execute($writearr);
		}
		return $this->getFileByPath($path); // Assuming this now returns an array as per the getFileByPath change
	}

	public function fileChanged(string $path, string $filehash): bool
	{
		$file = $this->getFileByPath($path);
		if (is_null($file)) {
			// If the file does not exist in the database, consider it changed/new
			return true;
		}
		// Compare hashes explicitly. If different, file has changed
		return $file['filehash'] !== $filehash;
	}

    public function getConf(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM convoconf WHERE conversation_id = :cid");
        $stmt->execute([':cid' => $this->convo_id]);
        $conf = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($conf)) {
            return [];
        }
        $ret = [];
        foreach ($conf as $row) {
            $ret[$row['confkey']] = $row['confval'];
        }

        return $ret;
    }

    public function getConfVal(string $confkey): ?string
    {
        $conf = $this->getConf();
        return $conf[$confkey] ?? null;
    }

    public function delConfVal($confkey): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM convoconf 
                WHERE conversation_id=:conversation_id AND confkey=:confkey");
        $stmt->execute([
            ':conversation_id' => $this->convo_id,
            ':confkey' => $confkey
        ]);
    }

    public function setConfVal($confkey, $confval): void
    {
        $oldconf = $this->getConf();
        if (isset($oldconf[$confkey])) {
            $stmt = $this->pdo->prepare("UPDATE convoconf SET 
                conversation_id = :conversation_id,
                confkey = :confkey,
                confval = :confval
                WHERE conversation_id=:conversation_id AND confkey=:confkey");
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO convoconf (conversation_id, confkey, confval)
                VALUES (:conversation_id, :confkey, :confval)");
        }
        $stmt->execute([
            ':conversation_id' => $this->convo_id,
            ':confkey' => $confkey,
            ':confval' => $confval,
        ]);
    }

    public function createConvo(string $name): int
    {
        $this->createOrAccessDb();
        $stmt = $this->pdo->prepare("SELECT id FROM conversation WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $convo_id = $stmt->fetchColumn();

        if ($convo_id === false) {
            $stmt = $this->pdo->prepare("INSERT INTO conversation (name) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            $convo_id = $this->pdo->lastInsertId();
        }
        return $convo_id;
    }

    public function getConvoname()
    {
        return $this->convoname;
    }

    public function useConvo(string $name): int
    {
        $convo_id = $this->createConvo($name); 
        $this->convoname = $name;
        $this->convo_id = $convo_id;
        return $convo_id;
    }

    public function saveMessage(Message $message): Message
    {
        if ($message->getId() <= 0) {
            return $this->addMessage($message);
        } else {
            return $this->updateMessage($message);
        }
    }

    public function addMessage(Message $message): Message
    {
        $stmt = $this->pdo->prepare("INSERT INTO messages (role, text, tokencount, conversation_id, summary, summarytokencount, type) VALUES (:role, :text, :tokencount, :conversation_id, :summary, :summarytokencount, :type)");
        $stmt->execute([
            ':role' => $message->getRole(),
            ':text' => trim($message->getContent()),
            ':tokencount' => $message->getTokenCount(),
            ':conversation_id' => $this->convo_id,
            ':summary' => $message->getSummary(),
            ':summarytokencount' => $message->getSummaryTokenCount(),
            ':type' => "text"
        ]);
        $message->setId($this->pdo->lastInsertId());
        return $message;
    }

    public function updateMessage(Message $message): Message
    {
        $stmt = $this->pdo->prepare("UPDATE messages SET role = :role, 
            text = :text, summary = :summary, summarytokencount = :summarytokencount, conversation_id = :conversation_id, type = :type WHERE id=:id");
        $stmt->execute([
            ':role' => $message->getRole(),
            ':text' => trim($message->getContent()),
            ':conversation_id' => $this->convo_id,
            ':summary' => $message->getSummary(),
            ':summarytokencount' => $message->getSummaryTokenCount(),
            ':type' => "text",
            ':id' => $message->getId()
        ]);
        return $message;
    }

    public function getUnsummarisedMessages(int $limit): array
    {
        $query = "SELECT * FROM messages WHERE summary='' AND conversation_id = :conversation_id ORDER BY id DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            ':conversation_id' => $this->convo_id,
            ':limit' => $limit
        ]);
        return $this->makeMessages(array_reverse($stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    public function getMessages(int $limit = 0): array
    {
        $query = "SELECT * FROM messages WHERE conversation_id = :conversation_id ORDER BY id DESC";
        if ($limit > 0) {
            $query .= " LIMIT :limit";
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->bindValue(':conversation_id', $this->convo_id, \PDO::PARAM_INT);
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        }
        $stmt->execute();

        return $this->makeMessages(array_reverse($stmt->fetchAll(\PDO::FETCH_ASSOC)));
    }

    private function makeMessages(array $rawmessages): array
    {
        $ret = [];
        foreach ($rawmessages as $dbmsg) {
            $ret[] = new Message(
                $dbmsg['id'],
                $dbmsg['role'],
                $dbmsg['text'],
                (int)$dbmsg['tokencount'],
                (string)$dbmsg['summary'],
                $dbmsg['summarytokencount']
            );
        }
        return $ret;
    }
}
