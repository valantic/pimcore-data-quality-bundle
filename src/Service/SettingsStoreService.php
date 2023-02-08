<?php

namespace Valantic\DataQualityBundle\Service;

use Pimcore\Model\Tool\SettingsStore;

class SettingsStoreService
{
    public function get(string $id, ?string $scope = null): mixed
    {
        $instance = SettingsStore::get($id, $scope);

        if (!($instance instanceof SettingsStore)) {
            return null;
        }

        $value = $instance->getData();

        if ($instance->getType() === 'string') {
            $data = json_decode((string) $value, flags: JSON_THROW_ON_ERROR);

            if (is_object($data)) {
                return (array) $data;
            }
        }

        return $value;
    }

    public function getByScope(string $scope): array
    {
        $ids = SettingsStore::getIdsByScope($scope);

        $values = [];

        foreach ($ids as $id) {
            $values[$id] = $this->get($id, $scope);
        }

        return $values;
    }

    public function set(string $id, mixed $value, ?string $type = null, ?string $scope = null): void
    {
        if (empty($type)) {
            $type = isset($value) ? gettype($value) : 'string';

            if ($type === 'boolean') {
                $type = 'bool';
            }

            if ($type === 'array') {
                $type = 'string';
                $value = json_encode($value);
            }
        }

        SettingsStore::set($id, $value, $type, $scope);
    }

    public function delete(string $id, ?string $scope = null): void
    {
        SettingsStore::delete($id, $scope);
    }

    public function deleteScope(string $scope): void
    {
        $ids = SettingsStore::getIdsByScope($scope);

        foreach ($ids as $id) {
            $this->delete($id, $scope);
        }
    }
}
