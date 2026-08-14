<?php

namespace Lagdo\DbAdmin\Support\Service\Admin;

use Lagdo\DbAdmin\Support\Service\Audit\Options;
use Closure;

use function json_encode;

/**
 * SQL queries logging and storage.
 */
class QueryLogger
{
    /**
     * @var array<bool>
     */
    private array $record;

    /**
     * @var int
     */
    private int $category = Options::CAT_BUILDER;

    /**
     * @param AuditDatabase $auditDb
     * @param array $options
     * @param Closure $database
     */
    public function __construct(private AuditDatabase $auditDb,
        array $options, private Closure $database)
    {
        $this->record = [
            Options::CAT_LIBRARY => false, // (bool)($options['library']['enabled'] ?? false),
            Options::CAT_BUILDER => (bool)($options['builder']['enabled'] ?? false),
            Options::CAT_EDITOR => (bool)($options['editor']['enabled'] ?? false),
        ];
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
        return !($this->record[$category] ?? false);
    }

    /**
     * @param string $query
     * @param int $category
     *
     * @return bool
     */
    private function saveRunnedCommand(string $query, int $category): bool
    {
        if ($this->categoryDisabled($category)) {
            return false;
        }
        if (($userId = $this->auditDb->getUserId()) === 0) {
            $this->auditDb->logWarning('Unable to find a valid user for the audit query.');
            return false;
        }

        // Get the database options using the provided closure.
        $database = ($this->database)();

        if (isset($database['password'])) {
            // Hide the password.
            $database['password'] = '';
        }
        $values = [
            'query' => $query,
            'driver' => $database['driver'],
            'options' => json_encode($database) ?? '{}',
            'category' => $category,
            'last_update' => $this->auditDb->currentTime(),
            'user_id' => $userId,
        ];
        // Duplicates on query are checked on client side, not here.
        $query = "INSERT INTO dbadmin_runned_commands
(query,driver,options,category,last_update,user_id)
VALUES (:query,:driver,:options,:category,:last_update,:user_id)";
        $result = $this->auditDb->executeQuery($query, $values);
        if (!$result->hasError()) {
            return true;
        }

        $this->auditDb->logWarning('Unable to save command in the query audit database.');
        return false;
    }

    /**
     * @param string $query
     *
     * @return bool
     */
    public function saveCommand(string $query): bool
    {
        $category = $this->category;
        // Reset to the default category.
        $this->category = Options::CAT_BUILDER;

        return $this->saveRunnedCommand($query, $category);
    }
}
