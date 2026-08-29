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
     * @var int
     */
    private int $userId = 0;

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
        if (!$result->hasRowset() || !($row = $result->fetchAssoc())) {
            return $this->userId = 0;
        }

        return $this->userId = (int)$row['id'];
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
        return $this->userId = 0;
    }

    /**
     * @param bool $canCreate
     *
     * @return int
     */
    public function getUserId(bool $canCreate = true): int
    {
        $user = $this->auth->userId();
        return match(true) {
            $user === '' => 0,
            !$this->connected() => 0,
            $this->userId > 0 => $this->userId,
            $this->readUserId($user) > 0 => $this->userId,
            !$canCreate => 0,
            // Try to create a new user entry for the user.
            default => $this->newUserId($user),
        };
    }

    /**
     * @return bool
     */
    public function canSaveQuery(): bool
    {
        return $this->configProvider->canSaveQuery() && $this->getUserId(false) > 0;
    }

    /**
     * @return bool
     */
    public function showQueryHistory(): bool
    {
        return $this->configProvider->showQueryHistory() && $this->getUserId(false) > 0;
    }

    /**
     * @return bool
     */
    public function showQueryFavorite(): bool
    {
        return $this->configProvider->showQueryFavorite() && $this->getUserId(false) > 0;
    }

    /**
     * @return bool
     */
    public function canShowQuery(): bool
    {
        return ($this->configProvider->showQueryHistory() ||
            $this->configProvider->showQueryFavorite()) && $this->getUserId(false) > 0;
    }

    /**
     * @return bool
     */
    public function showQueryPreferences(): bool
    {
        return $this->configProvider->showQueryPreferences() && $this->getUserId(false) > 0;
    }
}
