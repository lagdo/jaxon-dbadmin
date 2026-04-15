<?php

namespace Lagdo\DbAdmin\Support\Service\Admin;

use Lagdo\DbAdmin\Support\Service\Audit\Options;

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
     * The constructor
     *
     * @param ConnectionProxy $proxy
     * @param array $options
     * @param array $database
     */
    public function __construct(private ConnectionProxy $proxy,
        array $options, private array $database)
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

        if (isset($this->database['password'])) {
            // Hide the password.
            $this->database['password'] = '';
        }
        $values = [
            'query' => $query,
            'driver' => $this->database['driver'],
            'options' => json_encode($this->database) ?? '{}',
            'category' => $category,
            'last_update' => $this->proxy->currentTime(),
            'user_id' => $this->proxy->getUserId(),
        ];
        // Duplicates on query are checked on client side, not here.
        $query = "INSERT INTO dbadmin_runned_commands
(query,driver,options,category,last_update,user_id)
VALUES (:query,:driver,:options,:category,:last_update,:user_id)";
        $statement = $this->proxy->executeQuery($query, $values);
        if ($statement !== false) {
            return true;
        }

        $this->proxy->logWarning('Unable to save command in the query audit database.');
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
