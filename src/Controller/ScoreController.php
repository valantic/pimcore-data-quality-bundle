<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Bundle\AdminBundle\Security\User\User;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Valantic\DataQualityBundle\Config\DataObjectConfigInterface;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Service\CacheService;
use Valantic\DataQualityBundle\Service\UserSettingsService;
use Valantic\DataQualityBundle\Service\Formatters\ValueFormatter;
use Valantic\DataQualityBundle\Service\Formatters\ValuePreviewFormatter;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Shared\SortOrderTrait;
use Valantic\DataQualityBundle\Validation\DataObject\Validate;

#[Route('/admin/valantic/data-quality/score')]
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
        DefinitionInformationFactory $definitionInformationFactory,
        ValueFormatter $valueFormatter,
        ValuePreviewFormatter $valuePreviewFormatter,
        ConfigurationRepository $configurationRepository,
        TagAwareCacheInterface $cache,
        CacheService $cacheService,
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

        $config = $configurationRepository->getForClass($obj::class);

        /** @var User */
        $user = $this->getUser();
        $userConfig = $settingsService->get($obj->getClassName(), (string) $user->getId());

        return $cache->get(
            $this->getCacheKey($request, $config, $userConfig),
            function(ItemInterface $item) use ($obj, $userConfig, $cacheService, $request, $valuePreviewFormatter, $valueFormatter, $configurationRepository, $validation, $definitionInformationFactory) {
                $item->tag($cacheService->getTags($obj));

                $classInformation = $definitionInformationFactory->make($obj::class);

                $groups = [];
                foreach ($configurationRepository->getConfiguredAttributes($obj::class) as $attribute) {
                    foreach ($configurationRepository->getRulesForAttribute($obj::class, $attribute) as $rule) {
                        foreach ($rule['groups'] ?? [] as $group) {
                            $groups[] = $group;
                        }
                    }
                }

                $ignoreFallbackLanguage = $configurationRepository->getIgnoreFallbackLanguage($obj::class);

                if (!empty($userConfig)) {
                    $groups = $userConfig['groups'] ?: [];
                    $ignoreFallbackLanguage = $userConfig['ignoreFallbackLanguage'];
                }

                $validation->setObject($obj);
                $validation->setGroups($groups);
                $validation->setIgnoreFallbackLanguage($ignoreFallbackLanguage);
                $validation->validate();
                $filter = $request->get('filterText');

                $attributes = [];

                foreach ($validation->attributeScores() as $attribute => $score) {
                    if (stripos($attribute, (string) $filter) === false) {
                        continue;
                    }

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

                return $this->json([
                    'object' => $validation->objectScore(),
                    'attributes' => $this->sortBySortOrder($attributes, 'label'),
                    'groups' => array_map(
                        fn (string $group): array => ['group' => $group],
                        array_unique([DataObjectConfigInterface::VALIDATION_GROUP_DEFAULT, ...$groups])
                    ),
                    'settings' => [
                        'groups' => $groups,
                        'ignoreFallbackLanguage' => $ignoreFallbackLanguage,
                    ],
                ]);
            }
        );
    }

    /**
     * Check if an object can be scored i.e. if it is configured.
     */
    #[Route('/check', options: ['expose' => true])]
    public function checkAction(
        Request $request,
        ConfigurationRepository $configurationRepository,
        TagAwareCacheInterface $cache,
        CacheService $cacheService,
        UserSettingsService $settingsService,
    ): JsonResponse {
        $obj = Concrete::getById($request->query->getInt('id'));

        if (!$obj instanceof Concrete) {
            return $this->json([
                'status' => false,
            ]);
        }

        $config = $configurationRepository->getForClass($obj::class);

        /** @var User */
        $user = $this->getUser();
        $userConfig = $settingsService->get($obj->getClassName(), (string) $user->getId());

        return $cache->get(
            $this->getCacheKey($request, $config, $userConfig),
            function(ItemInterface $item) use ($obj, $cacheService, $configurationRepository) {
                $item->tag($cacheService->getTags($obj));

                return $this->json([
                    'status' => $configurationRepository->isClassConfigured($obj::class),
                ]);
            }
        );
    }

    protected function getCacheKey(Request $request, ?array $config = null, ?array $userConfig = null): string
    {
        $cacheKey = json_encode($request->getRequestUri(), flags: JSON_THROW_ON_ERROR);

        if (!empty($config)) {
            $cacheKey .= '_' . json_encode($config, flags: JSON_THROW_ON_ERROR);
        }

        if (!empty($userConfig)) {
            $cacheKey .= '_' . json_encode($userConfig, flags: JSON_THROW_ON_ERROR);
        }

        return md5($cacheKey);
    }
}
