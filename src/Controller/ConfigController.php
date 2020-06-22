<?php

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Listing;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Valantic\DataQualityBundle\Config\V1\Reader as ConfigReader;
use Valantic\DataQualityBundle\Config\V1\Writer as ConfigWriter;

/**
 * @Route("/admin/valantic/data-quality/config")
 */
class ConfigController extends BaseController
{
    /**
     * Returns the config for the admin editor.
     *
     * @Route("/list", options={"expose"=true})
     *
     * @param Request $request
     * @param ConfigReader $config
     *
     * @return JsonResponse
     */
    public function listAction(Request $request, ConfigReader $config): JsonResponse
    {
        // check permissions
        $this->checkPermission(self::CONFIG_NAME);

        $entries = [];
        foreach ($config->getConfiguredClasses() as $className) {
            foreach ($config->getForClass($className) as $attribute => $rules) {
                $transformedRules = [];
                foreach ($rules as $constraint => $args) {
                    $transformedRules[] = [
                        'constraint' => $constraint,
                        'args' => $args ? [$args] : null,
                    ];
                }
                $entries[] = [
                    'classname' => $className,
                    'attributename' => $attribute,
                    'rules' => $transformedRules,
                ];
            }
        }

        return $this->json($entries);
    }

    /**
     * Return a list of possible classes to configure.
     *
     * @Route("/classes", options={"expose"=true})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function classesAction(Request $request): JsonResponse
    {
        // check permissions
        $this->checkPermission(self::CONFIG_NAME);

        $classesList = new Listing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');
        $classes = $classesList->load();

        $classNames = [];
        foreach (array_column($classes, 'name') as $name) {
            $classNames[] = ['name' => $name];
        }

        return $this->json(['classes' => $classNames]);
    }

    /**
     * Return a list of possible attributes to configure for a class (?classname=x).
     *
     * @Route("/attributes", options={"expose"=true})
     *
     * @param Request $request
     * @param ConfigReader $config
     *
     * @return JsonResponse
     */
    public function attributesAction(Request $request, ConfigReader $config): JsonResponse
    {
        if (!$request->query->has('classname')) {
            return $this->json(['attributes' => []]);
        }
        // check permissions
        $this->checkPermission(self::CONFIG_NAME);

        try {
            $definition = ClassDefinition::getByName($request->query->get('classname'));
            $attributes = $definition->getFieldDefinitions();
        } catch (Throwable $throwable) {
            return $this->json(['attributes' => []]);
        }

        $names = array_diff(array_keys($attributes), $config->getConfiguredClassAttributes($definition->getName()));

        $attributeNames = [];
        foreach ($names as $name) {
            $attributeNames[] = ['name' => $name];
        }

        return $this->json(['attributes' => $attributeNames]);
    }

    /**
     * Adds a new classname-attributename pair to the config.
     *
     * @Route("/add", options={"expose"=true}, methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAction(Request $request, ConfigWriter $config): JsonResponse
    {
        return $this->json([
            'status' => $config->addClassAttribute($request->request->get('classname'), $request->request->get('attributename')),
        ]);
    }
}
