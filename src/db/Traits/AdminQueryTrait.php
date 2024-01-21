<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Exception;

use function substr;
use function strlen;
use function preg_replace;

trait AdminQueryTrait
{
    /**
     * Query printed after execution in the message
     *
     * @param string $query Executed query
     *
     * @return string
     */
    private function messageQuery(string $query/*, string $time*/): string
    {
        if (strlen($query) > 1e6) {
            // [\x80-\xFF] - valid UTF-8, \n - can end by one-line comment
            $query = preg_replace('~[\x80-\xFF]+$~', '', substr($query, 0, 1e6)) . "\n…";
        }
        return $query;
    }

    /**
     * Execute query
     *
     * @param string $query
     * @param bool $execute
     * @param bool $failed
     *
     * @return bool
     * @throws Exception
     */
    private function executeQuery(string $query, bool $execute = true, bool $failed = false/*, string $time = ''*/): bool
    {
        if ($execute) {
            // $start = microtime(true);
            $failed = !$this->driver->execute($query);
            // $time = $this->trans->formatTime($start);
        }
        if ($failed) {
            $sql = '';
            if ($query) {
                $sql = $this->messageQuery($query/*, $time*/);
            }
            throw new Exception($this->driver->error() . $sql);
        }
        return true;
    }

    /**
     * Execute remembered queries
     *
     * @param bool $failed
     *
     * @return bool
     * @throws Exception
     */
    private function executeSavedQuery(bool $failed): bool
    {
        list($queries/*, $time*/) = $this->driver->queries();
        return $this->executeQuery($queries, false, $failed/*, $time*/);
    }

    /**
     * Drop old object and create a new one
     *
     * @param string $drop Drop old object query
     * @param string $create Create new object query
     * @param string $dropCreated Drop new object query
     * @param string $test Create test object query
     * @param string $dropTest Drop test object query
     * @param string $oldName
     * @param string $newName
     *
     * @return string
     * @throws Exception
     */
    private function dropAndCreate(string $drop, string $create, string $dropCreated,
        string $test, string $dropTest, string $oldName, string $newName): string
    {
        if ($oldName == '' && $newName == '') {
            $this->executeQuery($drop);
            return 'dropped';
        }
        if ($oldName == '') {
            $this->executeQuery($create);
            return 'created';
        }
        if ($oldName != $newName) {
            $created = $this->driver->execute($create);
            $dropped = $this->driver->execute($drop);
            // $this->executeSavedQuery(!($created && $this->driver->execute($drop)));
            if (!$dropped && $created) {
                $this->driver->execute($dropCreated);
            }
            return 'altered';
        }
        $this->executeSavedQuery(!($this->driver->execute($test) &&
            $this->driver->execute($dropTest) &&
            $this->driver->execute($drop) && $this->driver->execute($create)));
        return 'altered';
    }
}
