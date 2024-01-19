<?php

namespace Valantic\DataQualityBundle\Service;

class UserSettingsService
{
    private const SCOPE = 'valantic-data-quality';
    private const SETTINGS_ID = 'settings';

    public function __construct(private readonly SettingsStoreService $settingsStoreService)
    {
    }

    public function get(string $className, string $userId): mixed
    {
        $settingsId = $this->getUserSettingsId($userId);
        $scope = $this->getScopeName($className);

        return $this->settingsStoreService->get($settingsId, $scope);
    }

    public function set(array $settings, string $className, string $userId): void
    {
        $scope = $this->getScopeName($className);
        $settingsId = $this->getUserSettingsId($userId);

        $this->settingsStoreService->set($settingsId, $settings, null, $scope);
    }

    public function deleteAll(string $className): void
    {
        $scope = $this->getScopeName($className);

        $this->settingsStoreService->deleteScope($scope);
    }

    public function delete(string $className, string $userId): void
    {
        $scope = $this->getScopeName($className);
        $settingsId = $this->getUserSettingsId($userId);

        $this->settingsStoreService->delete($settingsId, $scope);
    }

    protected function getScopeName(string $className): string
    {
        return self::SCOPE . '-' . $className;
    }

    protected function getUserSettingsId(string $userId): string
    {
        return self::SETTINGS_ID . '-' . $userId;
    }
}
