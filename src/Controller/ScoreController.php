<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Security\User\User;
use Pimcore\Cache;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Valantic\DataQualityBundle\Config\DataObjectConfigInterface;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Service\CacheService;
use Valantic\DataQualityBundle\Service\UserSettingsService;
use Valantic\DataQualityBundle\Service\Formatters\ValueFormatter;
use Valantic\DataQualityBundle\Service\Formatters\ValuePreviewFormatter;
use Valantic\DataQualityBundle\Shared\SortOrderTrait;
use Valantic\DataQualityBundle\Validation\DataObject\Validate;

#[Route('/score')]
class ScoreController extends BaseController
{
    use SortOrderTrait;

    /**
     * Show score of an object (passed via ?id=n) for the admin backend.
     */
    #[Route('/show/', options: ['expose' => true])]
    public function showAction(
        Request $request,
        Validate $validation,
        ValueFormatter $valueFormatter,
        ValuePreviewFormatter $valuePreviewFormatter,
        ConfigurationRepository $configurationRepository,
        UserSettingsService $settingsService,
    ): JsonResponse {
        $obj = Concrete::getById($request->query->getInt('id'));

        if (!$obj instanceof Concrete) {
            return $this->json([
                'score' => -1,
                'scores' => [],
                'attributes' => [],
                'color' => null,
            ]);
        }

        /** @var User $user */
        $user = $this->getUser();
        $userConfig = null;

        if ($obj->getClassName() !== null) {
            $userConfig = $settingsService->get($obj->getClassName(), $user->getUserIdentifier());
        }

        $allowedLocales = [];
        if (!$user->getUser()->isAdmin()) {
            $allowedLocales = array_keys(DataObject\Service::getLanguagePermissions($obj, $user->getUser(), 'lView') ?? []);
        }

        $cacheKey = CacheService::getCacheKey((int) $obj->getId());
        $cacheTags = CacheService::getTags((int) $obj->getId(), $obj::class);

        if ($userConfig !== null || !empty($allowedLocales)) {
            $cacheKey = CacheService::getCacheKey((int) $obj->getId(), $user->getUser());
            $cacheTags = CacheService::getTags((int) $obj->getId(), $obj::class, $user->getUser());
        }

        $data = Cache::load($cacheKey);
        //        if ($data) {
        $classInformation = $configurationRepository->getClassInformation($obj::class);

        $validation->setObject($obj);

        if (!empty($userConfig)) {
            $validation->setCacheScores(false);
            $validation->setGroups($userConfig['groups'] ?: []);
            $validation->setIgnoreFallbackLanguage($userConfig['ignoreFallbackLanguage']);
        }

        if (!empty($allowedLocales)) {
            $validation->setAllowedLocales($allowedLocales);
        }

        $validation->validate();

        $attributes = [];
        foreach ($validation->calculateScores() as $attribute => $score) {
            $attributes[] = array_merge(
                [
                    'attribute' => $attribute,
                    'label' => $classInformation->getAttributeLabel($attribute),
                    'note' => $configurationRepository->getNoteForAttribute($obj::class, $attribute),
                    'type' => $classInformation->getAttributeType($attribute),
                ],
                $score->jsonSerialize(),
                [
                    'value' => $valueFormatter->format($score->getValue()),
                    'value_preview' => $valuePreviewFormatter->format($score->getValue()),
                ]
            );
        }

        $data = [
            'object' => $validation->objectScore(),
            'attributes' => $this->sortBySortOrder($attributes, 'label'),
            'groups' => array_map(
                fn (string $group): array => ['group' => $group],
                array_unique([DataObjectConfigInterface::VALIDATION_GROUP_DEFAULT, ...$validation->getGroups()])
            ),
            'settings' => [
                'groups' => $validation->getGroups(),
                'ignoreFallbackLanguage' => $validation->getIgnoreFallbackLanguage(),
            ],
        ];

        Cache::save($data, $cacheKey, $cacheTags);
        //        }

        return $this->json($data);
    }

    /**
     * Check if an object can be scored i.e. if it is configured.
     */
    #[Route('/check', options: ['expose' => true])]
    public function checkAction(
        Request $request,
        ConfigurationRepository $configurationRepository,
    ): JsonResponse {
        $obj = Concrete::getById($request->query->getInt('id'));

        if (!$obj instanceof Concrete) {
            return $this->json([
                'status' => false,
            ]);
        }

        $config = $configurationRepository->getForClass($obj::class);
        if (empty($config['config']) || $configurationRepository->getDisableTabOnObject($obj::class)) {
            return $this->json([
                'status' => false,
            ]);
        }

        return $this->json([
            'status' => $configurationRepository->isClassConfigured($obj::class),
        ]);
    }
}
