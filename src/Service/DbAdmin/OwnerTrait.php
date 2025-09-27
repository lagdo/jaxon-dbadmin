<?php

namespace Lagdo\DbAdmin\Service\DbAdmin;

use Lagdo\Facades\Logger;
use Lagdo\DbAdmin\Driver\Db\ConnectionInterface;

trait OwnerTrait
{
    /**
     * @var int|null
     */
    private int|null $ownerId = null;

    /**
     * @var ConnectionInterface|null
     */
    abstract protected function connection(): ?ConnectionInterface;

    /**
     * @var string
     */
    abstract protected function user(): string;

    /**
     * @param string $username
     *
     * @return int
     */
    private function readOwnerId(string $username): int
    {
        $statement = "select id from dbadmin_owners where username='$username' limit 1";
        $ownerId = $this->connection()?->result($statement) ?? 0;
        return $this->ownerId = !$ownerId ? 0 : (int)$ownerId;
    }

    /**
     * @param string $username
     *
     * @return int
     */
    private function newOwnerId(string $username): int
    {
        // Try to save the user and return his id.
        $query = "insert into dbadmin_owners(username) values('$username')";
        $statement = $this->connection()?->query($query) ?? false;
        if ($statement !== false) {
            return $this->readOwnerId($username);
        }

        Logger::warning('Unable to save new owner in the query logging database.', [
            'error' => $this->connection()?->error() ??
                'Not connected to the logging database',
        ]);
        return false;
    }

    /**
     * @param bool $canCreate
     *
     * @return int
     */
    private function getOwnerId(bool $canCreate = true): int
    {
        return $this->ownerId !== null ? $this->ownerId :
            ($this->readOwnerId($this->user()) ?: ($canCreate ?
                $this->newOwnerId($this->user()) : 0));
    }
}
