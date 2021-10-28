<?php

namespace Lagdo\DbAdmin\DbAdmin;

/**
 * Admin user functions
 */
class UserAdmin extends AbstractAdmin
{
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

        $password = '';
        $details = [];
        foreach ($this->admin->getUsers($database) as $user) {
            // Fetch user grants
            $grants = $this->admin->getUserGrants($user["User"], $user["Host"], $password);
            $details[] = [
                'user' => $this->util->html($user["User"]),
                'host' => $this->util->html($user["Host"]),
                'grants' => \array_keys($grants),
            ];
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

        $details = $this->admin->getUserPrivileges($grants);

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
        $password = '';
        $grants = $this->admin->getUserGrants($user, $host, $password);
        if ($database !== '') {
            $grants = isset($grants[$database]) ? [$database => $grants[$database]] : [];
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
                '<input name="objects[' . $i . ']" value="' .
                    $this->util->html($object) . '" autocapitalize="off" />';
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
                'value' => $password ,
            ],
            'hashed' => [
                'label' => $this->trans->lang('Hashed'),
                'value' => ($password  != ''),
            ],
        ];

        $details = $this->admin->getUserPrivileges($grants);

        return \compact('user', 'headers', 'details', 'mainActions');
    }
}
