<?php

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Listing as ClassDefinitionListing;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Valantic\DataQualityBundle\Config\V1\Reader as ConfigReader;
use Valantic\DataQualityBundle\Config\V1\Writer as ConfigWriter;
use Valantic\DataQualityBundle\Validation\ConstraintDefinitions;

/**
 * @Route("/admin/valantic/data-quality/config")
 */
class ConfigController extends BaseController
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
        // check permissions
        $this->checkPermission(self::CONFIG_NAME);

        $filter = $request->get('filterText');

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
                if($filter){
                    if(stripos($className, $filter) === false && stripos($attribute, $filter)===false){
                        continue;
                    }
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
     * @Route("/classes", options={"expose"=true}, methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listClassesAction(): JsonResponse
    {
        // check permissions
        $this->checkPermission(self::CONFIG_NAME);

        $classesList = new ClassDefinitionListing();
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
     * @Route("/attributes", options={"expose"=true}, methods={"GET"})
     *
     * @param Request $request
     * @param ConfigReader $config
     *
     * @return JsonResponse
     */
    public function listAttributesAction(Request $request, ConfigReader $config): JsonResponse
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
     * @Route("/attributes", options={"expose"=true}, methods={"POST"})
     *
     * @param Request $request
     * @param ConfigWriter $config
     *
     * @return JsonResponse
     */
    public function addAttributeAction(Request $request, ConfigWriter $config): JsonResponse
    {
        return $this->json([
            'status' => $config->addClassAttribute(
                $request->request->get('classname'),
                $request->request->get('attributename')
            ),
        ]);
    }

    /**
     * Deletes a classname-attributename pair from the config.
     *
     * @Route("/attributes", options={"expose"=true}, methods={"DELETE"})
     *
     * @param Request $request
     * @param ConfigWriter $config
     *
     * @return JsonResponse
     */
    public function deleteAttributeAction(Request $request, ConfigWriter $config): JsonResponse
    {
        return $this->json([
            'status' => $config->removeClassAttribute(
                $request->request->get('classname'),
                $request->request->get('attributename')
            ),
        ]);
    }

    /**
     * Returns a list of possible constraints.
     *
     * @Route("/constraints", options={"expose"=true}, methods={"GET"})
     *
     * @param ConstraintDefinitions $definitions
     *
     * @return JsonResponse
     */
    public function listConstraintsAction(ConstraintDefinitions $definitions): JsonResponse
    {
        $symfonyNames = $definitions->symfony();
        $constraints = [];
        foreach ($symfonyNames as $name) {
            $constraints[] = ['name' => $name];
        }

        return $this->json(['constraints' => $constraints]);
    }

    /**
     * Adds a new constraint for a class attribute to the config.
     *
     * @Route("/constraints", options={"expose"=true}, methods={"POST"})
     *
     * @param Request $request
     * @param ConfigWriter $config
     *
     * @return JsonResponse
     */
    public function addConstraintAction(Request $request, ConfigWriter $config): JsonResponse
    {
        return $this->json([
            'status' => $config->addConstraint(
                $request->request->get('classname'),
                $request->request->get('attributename'),
                $request->request->get('constraint'),
                $request->request->get('params')
            ),
        ]);
    }

    /**
     * Delete a constraint for a class attribute from the config.
     *
     * @Route("/constraints", options={"expose"=true}, methods={"DELETE"})
     *
     * @param Request $request
     * @param ConfigWriter $config
     *
     * @return JsonResponse
     */
    public function deleteConstraintAction(Request $request, ConfigWriter $config): JsonResponse
    {
        return $this->json([
            'status' => $config->deleteConstraint(
                $request->request->get('classname'),
                $request->request->get('attributename'),
                $request->request->get('constraint')
            ),
        ]);
    }
}
