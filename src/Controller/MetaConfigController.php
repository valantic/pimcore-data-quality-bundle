<?php

namespace Valantic\DataQualityBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Valantic\DataQualityBundle\Config\V1\Meta\Reader as ConfigReader;
use Valantic\DataQualityBundle\Config\V1\Meta\Writer as ConfigWriter;
use Valantic\DataQualityBundle\Service\Locales\LocalesList;

/**
 * @Route("/admin/valantic/data-quality/meta-config")
 */
class MetaConfigController extends BaseController
{
    /**
     * Returns the config for the admin editor.
     *
     * @Route("/list", options={"expose"=true}, methods={"GET", "POST"})
     *
     * @param Request $request
     * @param ConfigReader $config
     *
     * @return JsonResponse
     */
    public function listAction(Request $request, ConfigReader $config): JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        $filter = $request->get('filterText');

        $entries = [];
        foreach ($config->getConfiguredClasses() as $className) {
            if ($filter) {
                if (stripos($className, $filter) === false) {
                    continue;
                }
            }
            $entries[] = [
                'classname' => $className,
                'locales' => $config->getForClass($className)[$config::KEY_LOCALES] ?? [],
                'threshold_green' => ($config->getForClass($className)[$config::KEY_THRESHOLD_GREEN] ?? 0) * 100,
                'threshold_orange' => ($config->getForClass($className)[$config::KEY_THRESHOLD_ORANGE] ?? 0) * 100,
            ];
        }

        return $this->json($entries);
    }

    /**
     * Return a list of possible classes to configure.
     *
     * @Route("/classes", options={"expose"=true}, methods={"GET"})
     *
     * @param ConfigReader $reader
     *
     * @return JsonResponse
     */
    public function listClassesAction(ConfigReader $reader): JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        $classNames = [];
        foreach ($this->getClassNames() as $name) {
            if ($reader->isClassConfigured($name)) {
                continue;
            }
            $classNames[] = ['name' => $name];
        }

        return $this->json(['classes' => $classNames]);
    }

    /**
     * Return a list of possible locales to configure.
     *
     * @Route("/locales", options={"expose"=true}, methods={"GET"})
     *
     * @param LocalesList $localesList
     *
     * @return JsonResponse
     */
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
     *
     * @Route("/modify", options={"expose"=true}, methods={"POST"})
     *
     * @param Request $request
     * @param ConfigWriter $config
     *
     * @return JsonResponse
     */
    public function modifyAction(Request $request, ConfigWriter $config): JsonResponse
    {
        if (empty($request->request->get('classname'))) {
            return $this->json(['status' => false]);
        }

        return $this->json([
            'status' => $config->update(
                $request->request->get('classname'),
                $request->request->get('locales', []),
                $request->request->getInt('threshold_green'),
                $request->request->getInt('threshold_orange')
            ),
        ]);
    }

    /**
     * Deletes a config entry for a class.
     *
     * @Route("/modify", options={"expose"=true}, methods={"DELETE"})
     *
     * @param Request $request
     * @param ConfigWriter $config
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, ConfigWriter $config): JsonResponse
    {
        if (empty($request->request->get('classname'))) {
            return $this->json(['status' => false]);
        }

        return $this->json([
            'status' => $config->delete($request->request->get('classname')),
        ]);
    }
}
