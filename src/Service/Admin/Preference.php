<?php

namespace Lagdo\DbAdmin\Db\Service\Admin;

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
    private bool $enabled;

    /**
     * The constructor
     *
     * @param ConnectionProxy $proxy
     * @param array $options
     */
    public function __construct(private ConnectionProxy $proxy, array $options)
    {
        $this->enabled = (bool)($options['preferences']['enabled'] ?? false);
    }

    /**
     * @param int $userId
     *
     * @return int
     */
    private function _getDefaultProfileId(int $userId): int
    {
        $query = "SELECT id FROM dbadmin_profiles WHERE title='' AND user_id=:user_id LIMIT 1";
        $statement = $this->proxy->executeQuery($query, ['user_id' => $userId]);
        return !$statement || !($row = $statement->fetchAssoc()) ? 0 : (int)$row['id'];
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
        $statement = $this->proxy->executeQuery($query, ['user_id' => $userId]);
        if ($statement !== false) {
            return $this->_getDefaultProfileId($userId);
        }

        $this->proxy->logWarning('Unable to create a default profile for the user.');
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
        $statement = $this->proxy->executeQuery($query, [
            'category' => $category,
            'profile_id' => $profileId,
        ]);
        return !$statement || !($row = $statement->fetchAssoc()) ? 0 : (int)$row['id'];
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
        $statement = $this->proxy->executeQuery($query, [
            'category' => $category,
            'last_update' => $this->proxy->currentTime(),
            'profile_id' => $profileId,
        ]);
        if ($statement !== false) {
            return $this->_getPreferenceId($profileId, $category);
        }

        $this->proxy->logWarning('Unable to create a default profile for the user.');
        return 0;
    }

    /**
     * @return array
     */
    private function getAppPreference(): array
    {
        if (($userId = $this->proxy->getUserId()) <= 0) {
            return [];
        }
        if (($profileId = $this->getDefaultProfileId($userId)) <= 0) {
            return [];
        }
        $category = 1; // The category for the app preferences.
        if (($preferenceId = $this->getPreferenceId($profileId, $category)) <= 0) {
            return [];
        }

        $query = "SELECT content FROM dbadmin_preferences WHERE id=:preference_id LIMIT 1";
        $statement = $this->proxy->executeQuery($query, [
            'preference_id' => $preferenceId,
        ]);
        if (!$statement || !($row = $statement->fetchAssoc())) {
            return [];
        }

        return [
            'id' => $preferenceId,
            'content' => json_decode((string)$row['content'], true),
        ];
    }

    /**
     * @return array
     */
    public function getAppTabs(): array
    {
        if (!$this->enabled) {
            return [];
        }

        $preference = $this->getAppPreference();
        return array_values($preference['content']['tabs'] ?? []);
    }

    /**
     * @param array $tabs
     *
     * @return bool
     */
    public function saveAppTabs(array $tabs): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $preference = $this->getAppPreference();
        if (count($preference) === 0) {
            return false;
        }

        $sql = "UPDATE dbadmin_preferences
SET content=:content,last_update=:last_update WHERE id=:preference_id";
        $statement = $this->proxy->executeQuery($sql, [
            'content' => json_encode(['tabs' => $tabs]),
            'last_update' => $this->proxy->currentTime(),
            'preference_id' => $preference['id'],
        ]);
        if ($statement !== false) {
            return true;
        }

        $this->proxy->logWarning('Unable to save tabs in the user preferences.');
        return false;
    }
}
