<?php

namespace Lagdo\DbAdmin\Db\Facades;

use function explode;
use function in_array;
use function array_keys;
use function strtoupper;

/**
 * Facade to user functions
 */
class UserFacade extends AbstractFacade
{
    /**
     * Get the privilege list
     * This feature is available only for MySQL
     *
     * @param string $database  The database name
     *
     * @return array
     */
    public function getPrivileges(string $database = ''): array
    {
        $headers = [
            $this->utils->trans->lang('Username'),
            $this->utils->trans->lang('Server'),
            '',
            '',
        ];

        $details = [];
        foreach ($this->driver->getUsers($database) as $user) {
            // Fetch user grants
            $userEntity = $this->driver->getUserGrants($user["User"], $user["Host"]);
            $details[] = [
                'user' => $this->utils->str->html($userEntity->name),
                'host' => $this->utils->str->html($userEntity->host),
                'grants' => \array_keys($userEntity->grants),
            ];
        }

        return \compact('headers', 'details');
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
    private function getPrivilegeInput(string $privilege, string $desc, string $context, array $grants): array
    {
        $detail = [$desc, $this->utils->str->html($privilege)];
        // echo '<tr><td' . ($desc ? ">$desc<td" : " colspan='2'") .
        //     ' lang="en" title="' . $this->utils->str->html($comment) . '">' . $this->utils->str->html($privilege);
        $i = 0;
        foreach ($grants as $object => $grant) {
            $name = "'grants[$i][" . $this->utils->str->html(strtoupper($privilege)) . "]'";
            $value = $grant[strtoupper($privilege)] ?? false;
            if ($context == 'Server Admin' && $object != (isset($grants['*.*']) ? '*.*' : '.*')) {
                $detail[] = '';
            }
            // elseif(isset($values['grant']))
            // {
            //     $detail[] = "<select name=$name><option><option value='1'" .
            //         ($value ? ' selected' : '') . '>' . $this->utils->trans->lang('Grant') .
            //         "<option value='0'" . ($value == '0' ? ' selected' : '') . '>' .
            //         $this->utils->trans->lang('Revoke') . '</select>';
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
    private function _getUserPrivileges(array $grants): array
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
            'Server Admin' => $this->utils->trans->lang('Server'),
            'Databases' => $this->utils->trans->lang('Database'),
            'Tables' => $this->utils->trans->lang('Table'),
            'Columns' => $this->utils->trans->lang('Column'),
            'Procedures' => $this->utils->trans->lang('Routine'),
        ];
        foreach ($contexts as $context => $desc) {
            foreach ($features[$context] as $privilege => $comment) {
                $privileges[] = $this->getPrivilegeInput($privilege, $desc, $context, $grants);
            }
        }

        return $privileges;
    }

    /**
     * Get the grants of a user on a given host
     *
     * @return array
     */
    public function newUserPrivileges(): array
    {
        $grants = [".*" => []];

        $headers = [
            $this->utils->trans->lang('Contexts'),
            $this->utils->trans->lang('Privileges'),
        ];
        $i = 0;
        foreach ($grants as $object => $grant) {
            //! separate db, table, columns, PROCEDURE|FUNCTION, routine
            $headers[] = $object === '*.*' ?
                '<input type="hidden" name="objects[' . $i . ']" value="*.*" />*.*' :
                '<input name="objects[' . $i . ']" value="' . $this->utils->str->html($object) . '" autocapitalize="off" />';
            $i++;
        }

        $user = [
            'host' => [
                'label' => $this->utils->trans->lang('Server'),
                'value' => '',
            ],
            'name' => [
                'label' => $this->utils->trans->lang('Username'),
                'value' => '',
            ],
            'pass' => [
                'label' => $this->utils->trans->lang('Password'),
                'value' => '',
            ],
            'hashed' => [
                'label' => $this->utils->trans->lang('Hashed'),
                'value' => false,
            ],
        ];

        $details = $this->_getUserPrivileges($grants);

        return \compact('user', 'headers', 'details');
    }

    /**
     * Get the grants of a user on a given host
     *
     * @param string $user      The username
     * @param string $host      The host name
     * @param string $database  The database name
     *
     * @return array
     */
    public function getUserPrivileges(string $user, string $host, string $database): array
    {
        $userEntity = $this->driver->getUserGrants($user, $host);
        if ($database !== '') {
            $userEntity->grants = isset($userEntity->grants[$database]) ?
                [$database => $userEntity->grants[$database]] : [];
        }

        $headers = [
            $this->utils->trans->lang('Contexts'),
            $this->utils->trans->lang('Privileges'),
        ];
        $i = 0;
        foreach ($userEntity->grants as $object => $grant) {
            //! separate db, table, columns, PROCEDURE|FUNCTION, routine
            $headers[] = $object === '*.*' ?
                '<input type="hidden" name="objects[' . $i . ']" value="*.*" />*.*' :
                '<input name="objects[' . $i . ']" value="' .
                    $this->utils->str->html($object) . '" autocapitalize="off" />';
            $i++;
        }

        $user = [
            'host' => [
                'label' => $this->utils->trans->lang('Server'),
                'value' => $host,
            ],
            'name' => [
                'label' => $this->utils->trans->lang('Username'),
                'value' => $user,
            ],
            'pass' => [
                'label' => $this->utils->trans->lang('Password'),
                'value' => $userEntity->password ,
            ],
            'hashed' => [
                'label' => $this->utils->trans->lang('Hashed'),
                'value' => ($userEntity->password  !== ''),
            ],
        ];

        $details = $this->_getUserPrivileges($userEntity->grants);

        return \compact('user', 'headers', 'details');
    }
}
