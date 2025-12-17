<?php

namespace Lagdo\DbAdmin\Db\Service\Admin;

use Lagdo\DbAdmin\Db\Service\Audit\Options;

use function json_encode;

/**
 * SQL queries logging and storage.
 */
class QueryLogger
{
    /**
     * @var bool
     */
    private bool $enduserEnabled;

    /**
     * @var bool
     */
    private bool $historyEnabled;

    /**
     * @var array
     */
    private array $userDatabase;

    /**
     * @var int
     */
    private int $category;

    /**
     * The constructor
     *
     * @param ConnectionProxy $proxy
     * @param array $options
     */
    public function __construct(private ConnectionProxy $proxy, array $options)
    {
        $this->enduserEnabled = (bool)($options['enduser']['enabled'] ?? false);
        $this->historyEnabled = (bool)($options['history']['enabled'] ?? false);
        $this->category = Options::CAT_BUILDER;
        $this->userDatabase = $options['database'];
    }

    /**
     * @return void
     */
    public function setCategoryToHistory(): void
    {
        $this->category = Options::CAT_EDITOR;
    }

    /**
     * @return bool
     */
    private function enduserDisabled(): bool
    {
        return (!$this->enduserEnabled && !$this->historyEnabled) ||
            !$this->proxy->getOwnerId(true);
    }

    /**
     * @param int $category
     *
     * @return bool
     */
    private function categoryDisabled(int $category): bool
    {
        return (!$this->enduserEnabled && $category === Options::CAT_BUILDER) ||
            ($category < Options::CAT_BUILDER || $category > Options::CAT_EDITOR);
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

        if (isset($this->userDatabase['password'])) {
            // Hide the password.
            $this->userDatabase['password'] = '';
        }
        $values = [
            'query' => $query,
            'driver' => $this->userDatabase['driver'],
            'options' => json_encode($this->userDatabase) ?? '{}',
            'category' => $category,
            'last_update' => $this->proxy->currentTime(),
            'owner_id' => $this->proxy->getOwnerId(),
        ];
        // Duplicates on query are checked on client side, not here.
        $query = "INSERT INTO dbadmin_runned_commands
(query,driver,options,category,last_update,owner_id)
VALUES (:query,:driver,:options,:category,:last_update,:owner_id)";
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

        return $this->enduserDisabled() ? false :
            $this->saveRunnedCommand($query, $category);
    }
}
