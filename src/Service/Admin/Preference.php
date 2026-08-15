<?php

namespace Lagdo\DbAdmin\Support\Service\Admin;

use Lagdo\DbAdmin\Support\Provider\DatabaseConfigProvider;

use function array_filter;
use function array_values;
use function count;
use function json_encode;
use function json_decode;

/**
 * CRUD for user preferences.
 */
class Preference
{
    /**
     * @var bool
     */
    private bool $preferencesEnabled;

    /**
     * @param AuditDatabase $auditDb
     * @param DatabaseConfigProvider $configProvider
     */
    public function __construct(private AuditDatabase $auditDb, DatabaseConfigProvider $configProvider)
    {
        $this->preferencesEnabled = $configProvider->queryPreferencesEnabled();
    }

    /**
     * @param int $userId
     *
     * @return int
     */
    private function _getDefaultProfileId(int $userId): int
    {
        $query = "SELECT id FROM dbadmin_profiles WHERE title='' AND user_id=:user_id LIMIT 1";
        $result = $this->auditDb->executeQuery($query, ['user_id' => $userId]);
        return $result->hasRowset() && ($row = $result->fetchAssoc()) ? (int)$row['id'] : 0;
    }

    /**
     * @param int $userId
     *
     * @return int
     */
    private function getDefaultProfileId(int $userId): int
    {
        if (($profileId = $this->_getDefaultProfileId($userId)) > 0) {
            return $profileId;
        }

        // Try to create a default profile for the user.
        $query = "INSERT INTO dbadmin_profiles(title,user_id) VALUES ('',:user_id)";
        $result = $this->auditDb->executeQuery($query, ['user_id' => $userId]);
        if (!$result->hasError()) {
            return $this->_getDefaultProfileId($userId);
        }

        $this->auditDb->logWarning('Unable to create a default profile for the user.');
        return 0;
    }

    /**
     * @param int $profileId
     * @param int $category
     *
     * @return int
     */
    private function _getPreferenceId(int $profileId, int $category): int
    {
        $query = "SELECT id FROM dbadmin_preferences
WHERE category=:category AND profile_id=:profile_id LIMIT 1";
        $result = $this->auditDb->executeQuery($query, [
            'category' => $category,
            'profile_id' => $profileId,
        ]);
        return $result->hasRowset() && ($row = $result->fetchAssoc()) ? (int)$row['id'] : 0;
    }

    /**
     * @param int $profileId
     * @param int $category
     *
     * @return int
     */
    private function getPreferenceId(int $profileId, int $category): int
    {
        if (($preferenceId = $this->_getPreferenceId($profileId, $category)) > 0) {
            return $preferenceId;
        }

        // Try to create a preference for the profile and category.
        $query = "INSERT INTO dbadmin_preferences(content,category,last_update,profile_id)
VALUES ('{}', :category, :last_update, :profile_id)";
        $result = $this->auditDb->executeQuery($query, [
            'category' => $category,
            'last_update' => $this->auditDb->currentTime(),
            'profile_id' => $profileId,
        ]);
        if (!$result->hasError()) {
            return $this->_getPreferenceId($profileId, $category);
        }

        $this->auditDb->logWarning('Unable to create a default profile for the user.');
        return 0;
    }

    /**
     * @param int $category
     *
     * @return array
     */
    private function getPreferenceContent(int $category): array
    {
        if (($userId = $this->auditDb->getUserId()) <= 0) {
            return [];
        }
        if (($profileId = $this->getDefaultProfileId($userId)) <= 0) {
            return [];
        }
        if (($preferenceId = $this->getPreferenceId($profileId, $category)) <= 0) {
            return [];
        }

        $query = "SELECT content FROM dbadmin_preferences WHERE id=:preference_id LIMIT 1";
        $result = $this->auditDb->executeQuery($query, [
            'preference_id' => $preferenceId,
        ]);

        return $result->hasRowset() && ($row = $result->fetchAssoc()) ? [
            'id' => $preferenceId,
            'content' => json_decode((string)$row['content'], true),
        ] : [];
    }

    /**
     * @param int $preferenceId
     * @param array $content
     *
     * @return bool
     */
    private function savePreferenceContent(int $preferenceId, array $content): bool
    {
        $sql = "UPDATE dbadmin_preferences
SET content=:content,last_update=:last_update WHERE id=:preference_id";
        $result = $this->auditDb->executeQuery($sql, [
            'content' => json_encode($content),
            'last_update' => $this->auditDb->currentTime(),
            'preference_id' => $preferenceId,
        ]);
        if (!$result->hasError()) {
            return true;
        }

        $this->auditDb->logWarning('Unable to save tabs in the user preferences.');
        return false;
    }

    /**
     * @return array
     */
    public function getAppTabs(): array
    {
        if (!$this->preferencesEnabled) {
            return [];
        }

        $category = 1; // The category for the app preferences.
        $preference = $this->getPreferenceContent($category);
        return $preference['content']['tabs'] ?? [];
    }

    /**
     * @param array $tabs
     *
     * @return bool
     */
    public function saveAppTabs(array $tabs): bool
    {
        if (!$this->preferencesEnabled) {
            return false;
        }

        $category = 1; // The category for the app preferences.
        $preference = $this->getPreferenceContent($category);
        if (count($preference) === 0) {
            return false;
        }

        $content = ['tabs' => $tabs];
        return $this->savePreferenceContent($preference['id'], $content);
    }

    /**
     * @param string $server
     *
     * @return array
     */
    public function getServerTabs(string $server): array
    {
        if (!$this->preferencesEnabled) {
            return [];
        }

        $category = 2; // The category for the server preferences.
        $preference = $this->getPreferenceContent($category);
        $tabs = array_filter(array_values($preference['content']['tabs'] ?? []),
            fn(array $tab) => $tab['server'] === $server);
        return $tabs[0]['values'] ?? [];
    }

    /**
     * @param string $server
     * @param array $tabs
     *
     * @return bool
     */
    public function saveServerTabs(string $server, array $tabs): bool
    {
        if (!$this->preferencesEnabled) {
            return false;
        }

        $category = 2; // The category for the server preferences.
        $preference = $this->getPreferenceContent($category);
        if (count($preference) === 0) {
            return false;
        }

        $content = $preference['content'] ?? [];
        $tabsToKeep = array_filter(array_values($content['tabs'] ?? []),
            fn(array $tab) => $tab['server'] !== $server);
        $content['tabs'] = [
            [
                'server' => $server,
                'values' => $tabs,
            ],
            ...$tabsToKeep,
        ];
        return $this->savePreferenceContent($preference['id'], $content);
    }

    /**
     * @param string $server
     * @param string $database
     *
     * @return array
     */
    public function getDatabaseTabs(string $server, string $database): array
    {
        if (!$this->preferencesEnabled) {
            return [];
        }

        $category = 3; // The category for the database preferences.
        $preference = $this->getPreferenceContent($category);
        $tabs = array_filter(array_values($preference['content']['tabs'] ?? []),
            fn(array $tab) => $tab['server'] === $server && $tab['database'] === $database);
        return $tabs[0]['values'] ?? [];
    }

    /**
     * @param string $server
     * @param string $database
     * @param array $tabs
     *
     * @return bool
     */
    public function saveDatabaseTabs(string $server, string $database, array $tabs): bool
    {
        if (!$this->preferencesEnabled) {
            return false;
        }

        $category = 3; // The category for the database preferences.
        $preference = $this->getPreferenceContent($category);
        if (count($preference) === 0) {
            return false;
        }

        $content = $preference['content'] ?? [];
        $tabsToKeep = array_filter(array_values($content['tabs'] ?? []),
            fn(array $tab) => $tab['server'] !== $server || $tab['database'] !== $database);
        $content['tabs'] = [
            [
                'server' => $server,
                'database' => $database,
                'values' => $tabs,
            ],
            ...$tabsToKeep,
        ];
        return $this->savePreferenceContent($preference['id'], $content);
    }
}
