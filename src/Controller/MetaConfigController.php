<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Security\User\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Valantic\DataQualityBundle\Enum\ThresholdEnum;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Repository\DataObjectRepository;
use Valantic\DataQualityBundle\Service\CacheService;
use Valantic\DataQualityBundle\Service\UserSettingsService;
use Valantic\DataQualityBundle\Shared\SortOrderTrait;

#[Route('/meta-config')]
class MetaConfigController extends BaseController
{
    use SortOrderTrait;

    /**
     * Returns the config for the admin editor.
     */
    #[Route('/list', options: ['expose' => true], methods: ['GET', 'POST'])]
    public function listAction(Request $request, ConfigurationRepository $configurationRepository): JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        $filter = $request->get('filterText');

        $entries = [];

        foreach ($configurationRepository->getConfiguredClasses() as $className) {
            if (stripos($className, (string) $filter) === false) {
                continue;
            }

            $entries[] = [
                'classname' => $className,
                'nesting_limit' => $configurationRepository->getConfiguredNestingLimit($className),
                'locales' => $configurationRepository->getConfiguredLocales($className),
                'threshold_green' => $configurationRepository->getConfiguredThreshold($className, ThresholdEnum::green),
                'threshold_orange' => $configurationRepository->getConfiguredThreshold($className, ThresholdEnum::orange),
                'ignore_fallback_language' => $configurationRepository->getIgnoreFallbackLanguage($className),
                'disable_tab_on_object' => $configurationRepository->getDisableTabOnObject($className),
            ];
        }

        return $this->json($this->sortBySortOrder($entries, 'classname'));
    }

    /**
     * Return a list of possible classes to configure.
     */
    #[Route('/classes', options: ['expose' => true], methods: ['GET'])]
    public function listClassesAction(): JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);
        $classes = array_map(
            fn ($name): array => ['name' => $name, 'short' => self::classBasename($name)],
            $this->getClassNames()
        );

        return $this->json([
            'classes' => $this->sortBySortOrder($classes, 'short'),
        ]);
    }

    /**
     * Adds or updates the locale config for a class.
     */
    #[Route('/modify', options: ['expose' => true], methods: ['POST'])]
    public function modifyAction(
        Request $request,
        ConfigurationRepository $configurationRepository,
        CacheService $cacheService,
    ): JsonResponse {
        /** @var class-string $className */
        $className = (string) $request->request->get('classname');

        if (empty($className)) {
            return $this->json(['status' => false]);
        }
        $configurationRepository->setClassConfig(
            $className,
            $request->get('locales'),
            $request->request->getInt('threshold_green'),
            $request->request->getInt('threshold_orange'),
            $request->request->getInt('nesting_limit', 1),
            $request->request->getBoolean('ignore_fallback_language'),
            $request->request->getBoolean('disable_tab_on_object')
        );
        $configurationRepository->persist();

        $cacheService->clearTag(sprintf('%s_%s', CacheService::DATA_QUALITY_CACHE_KEY, self::classBasename($className)));

        return $this->json([
            'status' => true,
        ]);
    }

    /**
     * Deletes a config entry for a class.
     */
    #[Route('/modify', options: ['expose' => true], methods: ['DELETE'])]
    public function deleteAction(
        Request $request,
        ConfigurationRepository $configurationRepository,
        CacheService $cacheService,
    ): JsonResponse {
        /** @var class-string $className */
        $className = (string) $request->request->get('classname');

        if (empty($className)) {
            return $this->json(['status' => false]);
        }
        $configurationRepository->deleteClassConfig(
            $className
        );
        $configurationRepository->persist();

        $cacheService->clearTag(sprintf('%s_%s', CacheService::DATA_QUALITY_CACHE_KEY, self::classBasename($className)));

        return $this->json([
            'status' => true,
        ]);
    }

    /**
     * Resets all user config.
     */
    #[Route('/reset', options: ['expose' => true], methods: ['POST'])]
    public function resetAction(Request $request, UserSettingsService $settingsService, CacheService $cacheService): JsonResponse
    {
        $className = (string) $request->request->get('classname');

        if (empty($className)) {
            return $this->json(['status' => false]);
        }

        $settingsService->deleteAll($className);

        $cacheService->clearTag(CacheService::DATA_QUALITY_USER_TAG_KEY);

        return $this->json([
            'status' => true,
        ]);
    }

    /**
     * Adds or updates the user config.
     */
    #[Route('/user/modify', options: ['expose' => true], methods: ['POST'])]
    public function userModifyAction(
        Request $request,
        UserSettingsService $settingsService,
        ConfigurationRepository $configurationRepository,
        CacheService $cacheService,
    ): JsonResponse {
        $className = (string) $request->request->get('classname');

        if (empty($className)) {
            return $this->json(['status' => false]);
        }

        /** @var class-string $fullClassName */
        $fullClassName = sprintf('%s\%s', DataObjectRepository::PIMCORE_DATA_OBJECT_NAMESPACE, $className);
        $ignoreFallbackLanguage = $configurationRepository->getIgnoreFallbackLanguage($fullClassName);

        $settings = [
            'groups' => $request->get('groups'),
            'ignoreFallbackLanguage' => $request->request->getBoolean('ignoreFallbackLanguage'),
        ];

        /** @var User $user */
        $user = $this->getUser();
        if (empty($settings['groups']) && $settings['ignoreFallbackLanguage'] === $ignoreFallbackLanguage) {
            $settingsService->delete($className, $user->getUserIdentifier());
        } else {
            $settingsService->set($settings, $className, $user->getUserIdentifier());
        }
        $cacheService->clearTag(sprintf('%s_%d', CacheService::DATA_QUALITY_USER_TAG_KEY, $user->getId()));

        return $this->json([
            'status' => true,
        ]);
    }

    /**
     * Resets the current user config.
     */
    #[Route('/user/reset', options: ['expose' => true], methods: ['POST'])]
    public function userResetAction(Request $request, UserSettingsService $settingsService, CacheService $cacheService): JsonResponse
    {
        $className = (string) $request->request->get('classname');

        if (empty($className)) {
            return $this->json(['status' => false]);
        }

        /** @var User $user */
        $user = $this->getUser();
        $settingsService->delete($className, $user->getUserIdentifier());

        $cacheService->clearTag(sprintf('%s_%d', CacheService::DATA_QUALITY_USER_TAG_KEY, $user->getId()));

        return $this->json([
            'status' => true,
        ]);
    }
}
