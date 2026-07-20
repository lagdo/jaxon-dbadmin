<?php

namespace Lagdo\DbAdmin\Support\Service\Admin;

use Lagdo\DbAdmin\Driver\EngineInterface;
use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\DbAdmin\Support\Provider\DatabaseConfigProvider;
use Lagdo\DbAdmin\Support\Service\Audit;

use function gmdate;

/**
 * Connection to the audit database
 */
class AuditDatabase extends Audit\AuditDatabase
{
    /**
     * @var int|null
     */
    private int|null $userId = null;

    /**
     * @param AuthInterface $auth
     * @param EngineInterface $engine
     * @param DatabaseConfigProvider $configProvider
     */
    public function __construct(private AuthInterface $auth,
        EngineInterface $engine, DatabaseConfigProvider $configProvider)
    {
        parent::__construct($engine, $configProvider);
    }

    /**
     * @return string
     */
    public function currentTime(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    /**
     * @param string $username
     *
     * @return int
     */
    private function readUserId(string $username): int
    {
        $query = "SELECT id FROM dbadmin_users WHERE username=:username LIMIT 1";
        $result = $this->executeQuery($query, ['username' => $username]);
        return $result->hasRowset() && ($row = $result->fetchAssoc()) ? (int)$row['id'] : 0;
    }

    /**
     * @param string $username
     *
     * @return int
     */
    private function newUserId(string $username): int
    {
        // Try to save the user and return his id.
        $query = "INSERT INTO dbadmin_users(username) VALUES (:username)";
        $result = $this->executeQuery($query, ['username' => $username]);
        if (!$result->hasError()) {
            return $this->readUserId($username);
        }

        $this->logWarning('Unable to save new user in the query audit database.');
        return 0;
    }

    /**
     * @param bool $canCreate
     *
     * @return int
     */
    public function getUserId(bool $canCreate = true): int
    {
        $user = $this->auth->user();
        if (!$this->connected() || !$user) {
            return 0;
        }
        if ($this->userId !== null || ($this->userId = $this->readUserId($user)) > 0) {
            return $this->userId;
        }

        // Try to create a new user entry for the user.
        return !$canCreate ? 0 : ($this->userId = $this->newUserId($user));
    }

    /**
     * @return bool
     */
    public function canSaveQuery(): bool
    {
        return $this->connected() && $this->configProvider->canSaveQuery();
    }

    /**
     * @return bool
     */
    public function queryHistoryEnabled(): bool
    {
        return $this->connected() && $this->configProvider->queryHistoryEnabled();
    }

    /**
     * @return bool
     */
    public function queryFavoriteEnabled(): bool
    {
        return $this->connected() && $this->configProvider->queryFavoriteEnabled();
    }

    /**
     * @return bool
     */
    public function canShowQuery(): bool
    {
        return $this->configProvider->queryHistoryEnabled() ||
            $this->configProvider->queryFavoriteEnabled();
    }

    /**
     * @return bool
     */
    public function userPreferencesEnabled(): bool
    {
        return $this->connected() && $this->configProvider->userPreferencesEnabled();
    }
}
