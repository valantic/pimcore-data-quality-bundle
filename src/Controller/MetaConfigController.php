<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Valantic\DataQualityBundle\Enum\ThresholdEnum;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Service\Locales\LocalesList;

#[Route('/admin/valantic/data-quality/meta-config')]
class MetaConfigController extends BaseController
{
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
                'threshold_green' => $configurationRepository->getConfiguredThreshold($className, ThresholdEnum::green()) * 100,
                'threshold_orange' => $configurationRepository->getConfiguredThreshold($className, ThresholdEnum::orange()) * 100,
            ];
        }

        return $this->json($entries);
    }

    /**
     * Return a list of possible classes to configure.
     */
    #[Route('/classes', options: ['expose' => true], methods: ['GET'])]
    public function listClassesAction(
        ConfigurationRepository $configurationRepository,
    ): JsonResponse {
        $this->checkPermission(self::CONFIG_NAME);

        return $this->json([
            'classes' => array_map(fn($name): array => ['name' => $name], $configurationRepository->getConfiguredClasses()),
        ]);
    }

    /**
     * Return a list of possible locales to configure.
     */
    #[Route('/locales', options: ['expose' => true], methods: ['GET'])]
    public function listLocalesAction(LocalesList $localesList): JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        $localeNames = [];
        foreach ($localesList->all() as $locale) {
            $localeNames[] = ['locale' => $locale];
        }

        return $this->json(['locales' => $localeNames]);
    }

    /**
     * Adds or updates the locale config for a class.
     */
    #[Route('/modify', options: ['expose' => true], methods: ['POST'])]
    public function modifyAction(Request $request, ConfigurationRepository $configurationRepository): JsonResponse
    {
        if (empty($request->request->get('classname'))) {
            return $this->json(['status' => false]);
        }
        $configurationRepository->setClassConfig(
            (string) $request->request->get('classname'),
            $request->request->get('locales', []),
            $request->request->getInt('threshold_green'),
            $request->request->getInt('threshold_orange'),
            $request->request->getInt('nesting_limit', 1)
        );

        return $this->json([
            'status' => true,
        ]);
    }

    /**
     * Deletes a config entry for a class.
     */
    #[Route('/modify', options: ['expose' => true], methods: ['DELETE'])]
    public function deleteAction(Request $request, ConfigurationRepository $configurationRepository): JsonResponse
    {
        if (empty($request->request->get('classname'))) {
            return $this->json(['status' => false]);
        }
        $configurationRepository->deleteClassConfig((string) $request->request->get('classname'));

        return $this->json([
            'status' => true,
        ]);
    }
}
