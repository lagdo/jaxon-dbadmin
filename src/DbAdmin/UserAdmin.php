<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Exception;

/**
 * Admin user functions
 */
class UserAdmin extends AbstractAdmin
{
    /**
     * The user password
     *
     * @var string
     */
    protected $password = '';

    /**
     * Get the grants of a user on a given host
     *
     * @param string $user      The user name
     * @param string $host      The host name
     *
     * @return array
     */
    protected function fetchUserGrants($user = '', $host = '')
    {
        // From user.inc.php
        $grants = [];

        //! use information_schema for MySQL 5 - column names in column privileges are not escaped
        if (($statement = $this->driver->query("SHOW GRANTS FOR " .
            $this->driver->quote($user) . "@" . $this->driver->quote($host)))) {
            while ($row = $statement->fetchRow()) {
                if (\preg_match('~GRANT (.*) ON (.*) TO ~', $row[0], $match) &&
                    \preg_match_all('~ *([^(,]*[^ ,(])( *\([^)]+\))?~', $match[1], $matches, PREG_SET_ORDER)) { //! escape the part between ON and TO
                    foreach ($matches as $val) {
                        $match2 = $match[2] ?? '';
                        $val2 = $val[2] ?? '';
                        if ($val[1] != "USAGE") {
                            $grants["$match2$val2"][$val[1]] = true;
                        }
                        if (\preg_match('~ WITH GRANT OPTION~', $row[0])) { //! don't check inside strings and identifiers
                            $grants["$match2$val2"]["GRANT OPTION"] = true;
                        }
                    }
                }
                if (\preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~", $row[0], $match)) {
                    $this->password = $match[1];
                }
            }
        }

        return $grants;
    }

    /**
     * Get the user privileges
     *
     * @param array $grants     The user grants
     *
     * @return array
     */
    protected function fetchUserPrivileges(array $grants)
    {
        $features = $this->driver->privileges();
        $privileges = [];
        $contexts = [
            "" => "",
            "Server Admin" => $this->trans->lang('Server'),
            "Databases" => $this->trans->lang('Database'),
            "Tables" => $this->trans->lang('Table'),
            "Columns" => $this->trans->lang('Column'),
            "Procedures" => $this->trans->lang('Routine'),
        ];
        foreach ($contexts as $context => $desc) {
            foreach ($features[$context] as $privilege => $comment) {
                $detail = [$desc, $this->util->html($privilege)];
                // echo "<tr><td" . ($desc ? ">$desc<td" : " colspan='2'") .
                //     ' lang="en" title="' . $this->util->html($comment) . '">' . $this->util->html($privilege);
                $i = 0;
                foreach ($grants as $object => $grant) {
                    $name = "'grants[$i][" . $this->util->html(\strtoupper($privilege)) . "]'";
                    $value = $grant[\strtoupper($privilege)] ?? false;
                    if ($context == "Server Admin" && $object != (isset($grants["*.*"]) ? "*.*" : ".*")) {
                        $detail[] = '';
                    }
                    // elseif(isset($values["grant"]))
                    // {
                    //     $detail[] = "<select name=$name><option><option value='1'" .
                    //         ($value ? " selected" : "") . ">" . $this->trans->lang('Grant') .
                    //         "<option value='0'" . ($value == "0" ? " selected" : "") . ">" .
                    //         $this->trans->lang('Revoke') . "</select>";
                    // }
                    else {
                        $detail[] = "<input type='checkbox' name=$name" . ($value ? " checked />" : " />");
                    }
                    $i++;
                }
                $privileges[] = $detail;
            }
        }

        return $privileges;
    }

    /**
     * Get the privilege list
     * This feature is available only for MySQL
     *
     * @param string $database  The database name
     *
     * @return array
     */
    public function getPrivileges($database = '')
    {
        $mainActions = [
            'add-user' => $this->trans->lang('Create user'),
        ];

        $headers = [
            $this->trans->lang('Username'),
            $this->trans->lang('Server'),
            '',
            '',
        ];

        // From privileges.inc.php
        $statement = $this->driver->query("SELECT User, Host FROM mysql." .
            ($database == "" ? "user" : "db WHERE " . $this->driver->quote($database) . " LIKE Db") .
            " ORDER BY Host, User");
        $grant = $statement;
        if (!$statement) {
            // list logged user, information_schema.USER_PRIVILEGES lists just the current user too
            $statement = $this->driver->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) " .
                "AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");
        }
        $details = [];
        while ($row = $statement->fetchAssoc()) {
            $details[] = [
                'user' => $this->util->html($row["User"]),
                'host' => $this->util->html($row["Host"]),
            ];
        }

        // Fetch user grants
        foreach ($details as &$detail) {
            $grants = $this->fetchUserGrants($detail['user'], $detail['host']);
            $detail['grants'] = \array_keys($grants);
        }

        return \compact('headers', 'details', 'mainActions');
    }

    /**
     * Get the grants of a user on a given host
     *
     * @return array
     */
    public function newUserPrivileges()
    {
        $grants = [".*" => []];

        $headers = [
            $this->trans->lang('Contexts'),
            $this->trans->lang('Privileges'),
        ];
        $i = 0;
        foreach ($grants as $object => $grant) {
            //! separate db, table, columns, PROCEDURE|FUNCTION, routine
            $headers[] = $object === '*.*' ?
                '<input type="hidden" name="objects[' . $i . ']" value="*.*" />*.*' :
                '<input name="objects[' . $i . ']" value="' . $this->util->html($object) . '" autocapitalize="off" />';
            $i++;
        }

        $mainActions = [];

        $user = [
            'host' => [
                'label' => $this->trans->lang('Server'),
                'value' => '',
            ],
            'name' => [
                'label' => $this->trans->lang('Username'),
                'value' => '',
            ],
            'pass' => [
                'label' => $this->trans->lang('Password'),
                'value' => '',
            ],
            'hashed' => [
                'label' => $this->trans->lang('Hashed'),
                'value' => false,
            ],
        ];

        $details = $this->fetchUserPrivileges($grants);

        return \compact('user', 'headers', 'details', 'mainActions');
    }

    /**
     * Get the grants of a user on a given host
     *
     * @param string $user      The user name
     * @param string $host      The host name
     * @param string $database  The database name
     *
     * @return array
     */
    public function getUserPrivileges($user, $host, $database)
    {
        $grants = $this->fetchUserGrants($user, $host);
        if ($database !== '') {
            $grants = \array_key_exists($database, $grants) ? [$database => $grants[$database]] : [];
        }

        $headers = [
            $this->trans->lang('Contexts'),
            $this->trans->lang('Privileges'),
        ];
        $i = 0;
        foreach ($grants as $object => $grant) {
            //! separate db, table, columns, PROCEDURE|FUNCTION, routine
            $headers[] = $object === '*.*' ?
                '<input type="hidden" name="objects[' . $i . ']" value="*.*" />*.*' :
                '<input name="objects[' . $i . ']" value="' . $this->util->html($object) . '" autocapitalize="off" />';
            $i++;
        }

        $mainActions = [];

        $user = [
            'host' => [
                'label' => $this->trans->lang('Server'),
                'value' => $host,
            ],
            'name' => [
                'label' => $this->trans->lang('Username'),
                'value' => $user,
            ],
            'pass' => [
                'label' => $this->trans->lang('Password'),
                'value' => $this->password,
            ],
            'hashed' => [
                'label' => $this->trans->lang('Hashed'),
                'value' => ($this->password != ''),
            ],
        ];

        $details = $this->fetchUserPrivileges($grants);

        return \compact('user', 'headers', 'details', 'mainActions');
    }
}
