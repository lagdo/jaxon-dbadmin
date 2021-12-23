<?php

namespace Lagdo\DbAdmin\Db\Traits;

use function explode;
use function in_array;
use function array_keys;
use function strtoupper;
use function preg_match;
use function preg_match_all;

trait UserAdminTrait
{
    /**
     * Get the users and hosts
     *
     * @param string $database  The database name
     *
     * @return array
     */
    public function getUsers(string $database): array
    {
        // From privileges.inc.php
        $clause = ($database == '' ? 'user' : 'db WHERE ' . $this->driver->quote($database) . ' LIKE Db');
        $query = "SELECT User, Host FROM mysql.$clause ORDER BY Host, User";
        $statement = $this->driver->query($query);
        // $grant = $statement;
        if (!$statement) {
            // list logged user, information_schema.USER_PRIVILEGES lists just the current user too
            $statement = $this->driver->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) " .
                "AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");
        }
        $users = [];
        while ($row = $statement->fetchAssoc()) {
            $users[] = $row;
        }
        return $users;
    }

    /**
     * @param array $grants
     * @param array $row
     * @param string $password
     *
     * @return void
     */
    private function setUserGrant(array &$grants, array $row, string &$password)
    {
        if (preg_match('~GRANT (.*) ON (.*) TO ~', $row[0], $match) &&
            preg_match_all('~ *([^(,]*[^ ,(])( *\([^)]+\))?~', $match[1], $matches, PREG_SET_ORDER)) { //! escape the part between ON and TO
            foreach ($matches as $val) {
                $match2 = $match[2] ?? '';
                $val2 = $val[2] ?? '';
                if ($val[1] != 'USAGE') {
                    $grants["$match2$val2"][$val[1]] = true;
                }
                if (preg_match('~ WITH GRANT OPTION~', $row[0])) { //! don't check inside strings and identifiers
                    $grants["$match2$val2"]['GRANT OPTION'] = true;
                }
            }
        }
        if (preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~", $row[0], $match)) {
            $password = $match[1];
        }
    }

    /**
     * Get the grants of a user on a given host
     *
     * @param string $user      The username
     * @param string $host      The host name
     * @param string $password  The user password
     *
     * @return array
     */
    public function getUserGrants(string $user, string $host, string &$password): array
    {
        // From user.inc.php
        $grants = [];

        //! use information_schema for MySQL 5 - column names in column privileges are not escaped
        $query = 'SHOW GRANTS FOR ' . $this->driver->quote($user) . '@' . $this->driver->quote($host);
        if (($statement = $this->driver->query($query))) {
            while ($row = $statement->fetchRow()) {
                $this->setUserGrant($grants, $row, $password);
            }
        }

        return $grants;
    }

    /**
     * @param array $features
     * @param array $row
     *
     * @return void
     */
    private function makeFeatures(array &$features, array $row)
    {
        $contexts = explode(',', $row['Context']);
        foreach ($contexts as $context) {
            // Don't take 'Grant option' privileges.
            if ($row['Privilege'] === 'Grant option') {
                continue;
            }
            // Privileges of 'Server Admin' and 'File access on server' are merged
            if ($context === 'File access on server') {
                $context = 'Server Admin';
            }
            $privilege = $row['Privilege'];
            // Comment for this is 'No privileges - allow connect only'
            if ($context === 'Server Admin' && $privilege === 'Usage') {
                continue;
            }
            // MySQL bug #30305
            if ($context === 'Procedures' && $privilege === 'Create routine') {
                $context = 'Databases';
            }
            if (!isset($features[$context])) {
                $features[$context] = [];
            }
            $features[$context][$privilege] = $row['Comment'];
            if ($context === 'Tables' &&
                in_array($privilege, ['Select', 'Insert', 'Update', 'References'])) {
                $features['Columns'][$privilege] = $row['Comment'];
            }
        }
    }

    /**
     * @param string $privilege
     * @param string $desc
     * @param string $context
     * @param array $grants
     *
     * @return array
     */
    private function getUserPrivilegeDetail(string $privilege, string $desc, string $context, array $grants): array
    {
        $detail = [$desc, $this->util->html($privilege)];
        // echo '<tr><td' . ($desc ? ">$desc<td" : " colspan='2'") .
        //     ' lang="en" title="' . $this->util->html($comment) . '">' . $this->util->html($privilege);
        $i = 0;
        foreach ($grants as $object => $grant) {
            $name = "'grants[$i][" . $this->util->html(strtoupper($privilege)) . "]'";
            $value = $grant[strtoupper($privilege)] ?? false;
            if ($context == 'Server Admin' && $object != (isset($grants['*.*']) ? '*.*' : '.*')) {
                $detail[] = '';
            }
            // elseif(isset($values['grant']))
            // {
            //     $detail[] = "<select name=$name><option><option value='1'" .
            //         ($value ? ' selected' : '') . '>' . $this->trans->lang('Grant') .
            //         "<option value='0'" . ($value == '0' ? ' selected' : '') . '>' .
            //         $this->trans->lang('Revoke') . '</select>';
            // }
            else {
                $detail[] = "<input type='checkbox' name=$name" . ($value ? ' checked />' : ' />');
            }
            $i++;
        }
        return $detail;
    }

    /**
     * Get the user privileges
     *
     * @param array $grants     The user grants
     *
     * @return array
     */
    public function getUserPrivileges(array $grants): array
    {
        // From user.inc.php
        $features = [
            '' => [
                'All privileges' => '',
            ],
            'Columns' => [],
        ];
        $rows = $this->driver->rows('SHOW PRIVILEGES');
        foreach ($rows as $row) {
            $this->makeFeatures($features, $row);
        }

        foreach (array_keys($features['Tables']) as $privilege) {
            unset($features['Databases'][$privilege]);
        }

        $privileges = [];
        $contexts = [
            '' => '',
            'Server Admin' => $this->trans->lang('Server'),
            'Databases' => $this->trans->lang('Database'),
            'Tables' => $this->trans->lang('Table'),
            'Columns' => $this->trans->lang('Column'),
            'Procedures' => $this->trans->lang('Routine'),
        ];
        foreach ($contexts as $context => $desc) {
            foreach ($features[$context] as $privilege => $comment) {
                $privileges[] = $this->getUserPrivilegeDetail($privilege, $desc, $context, $grants);
            }
        }

        return $privileges;
    }
}
