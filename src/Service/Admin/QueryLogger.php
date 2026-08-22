<?php

namespace Lagdo\DbAdmin\Support\Service\Admin;

use Lagdo\DbAdmin\Driver\Sql\Connection\QueryResultInterface;
use Lagdo\DbAdmin\Support\Driver\QueryCallback;
use Lagdo\DbAdmin\Support\Provider\DatabaseConfigProvider;
use Lagdo\DbAdmin\Support\Service\Audit\Options;
use Closure;

use function json_encode;
use function hash;

/**
 * SQL queries logging and storage.
 */
class QueryLogger implements QueryCallback
{
    /**
     * @var bool
     */
    private bool $enabled = false;

    /**
     * @var array<bool>
     */
    private array $save;

    /**
     * @var int
     */
    private int $category = Options::CAT_BUILDER;

    /**
     * @param QueryTimer $timer
     * @param AuditDatabase $auditDb
     * @param DatabaseConfigProvider $configProvider
     * @param Closure $database
     */
    public function __construct(private QueryTimer $timer, private AuditDatabase $auditDb,
        DatabaseConfigProvider $configProvider, private Closure $database)
    {
        $this->save = [
            Options::CAT_LIBRARY => $configProvider->saveLibraryQueries(),
            Options::CAT_BUILDER => $configProvider->saveBuilderQueries(),
            Options::CAT_EDITOR => $configProvider->saveEditorQueries(),
        ];
    }

    /**
     * @param bool|null $enabled
     *
     * @return bool
     */
    public function enabled(bool|null $enabled = null): bool
    {
        if ($this->enabled !== null) {
            $this->enabled = $enabled;
        }
        return $this->enabled;
    }

    /**
     * @return void
     */
    public function setCategoryToEditor(): void
    {
        $this->category = Options::CAT_EDITOR;
    }

    /**
     * @param int $category
     *
     * @return bool
     */
    private function categoryDisabled(int $category): bool
    {
        return !($this->save[$category] ?? false);
    }

    /**
     * @param string $query
     * @param QueryResultInterface|bool $result
     * @param int $category
     *
     * @return bool
     */
    private function saveExecution(string $query, QueryResultInterface|bool $result, int $category): bool
    {
        if ($this->categoryDisabled($category)) {
            return false;
        }
        if (($userId = $this->auditDb->getUserId()) === 0) {
            $this->auditDb->logWarning('Unable to find a valid user for the audit query.');
            return false;
        }

        // Get the database options using the provided closure.
        $database = ($this->database)()->getValues();

        if (isset($database['password'])) {
            // Hide the password.
            $database['password'] = '';
        }
        $values = [
            'query' => $query,
            'query_hash' => hash('sha256', $query),
            'driver' => $database['driver'],
            'options' => json_encode($database) ?? '{}',
            // 'error_code' => ,
            // 'error_message' => ,
            // 'rows_affected' => ,
            // 'rows_returned' => ,
            'started_at' => $this->timer->startTime(),
            'duration' => $this->timer->duration(false),
            'category' => $category,
            'last_update' => $this->auditDb->currentTime(),
            'user_id' => $userId,
        ];
        // Duplicates on query are checked on client side, not here.
        $query = "INSERT INTO dbadmin_executions
(query,query_hash,driver,options,started_at,duration,category,last_update,user_id)
VALUES (:query,:query_hash,:driver,:options,:started_at,:duration,:category,:last_update,:user_id)";
        if (!$this->auditDb->executeQuery($query, $values)->hasError()) {
            return true;
        }

        $this->auditDb->logWarning('Unable to save command in the query audit database.');
        return false;
    }

    /**
     * @inheritDoc
     */
    public function beforeQueryExec(string $query): void
    {
    }

    /**
     * @inheritDoc
     */
    public function afterQueryExec(string $query, QueryResultInterface|bool $result): void
    {
        $category = $this->category;
        // Reset to the default category.
        $this->category = Options::CAT_BUILDER;
        $this->saveExecution($query, $result, $category);
    }
}
