<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Repository\ConstraintDefinitions;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;

#[Route('/admin/valantic/data-quality/constraint-config')]
class ConstraintConfigController extends BaseController
{
    /**
     * Returns the config for the admin editor.
     */
    #[Route('/list', options: ['expose' => true], methods: ['GET', 'POST'])]
    public function listAction(
        Request $request,
        ConstraintDefinitions $definitions,
        ConfigurationRepository $configurationRepository
    ): JsonResponse {
        $constraintDefinitions = $definitions->all();
        $this->checkPermission(self::CONFIG_NAME);

        $filter = $request->get('filterText');

        $entries = [];
        if (empty($filter)) {
            return $this->json($entries);
        }
        foreach ($configurationRepository->getConfiguredClasses() as $className) {
            if (stripos($className, (string) $filter) === false) {
                continue;
            }

            foreach ($configurationRepository->getConfiguredAttributes($className) as $attribute) {
                if (stripos($attribute, (string) $filter) === false) {
                    continue;
                }

                $transformedRules = [];
                foreach ($configurationRepository->getRulesForAttribute($className, $attribute) as $constraint => $args) {
                    $transformedRules[] = [
                        'constraint' => $constraint,
                        'label' => array_key_exists($constraint, $constraintDefinitions) ? ($constraintDefinitions[$constraint]['label'] ?? $constraint) : $constraint,
                        'args' => $args,
                    ];
                }
                $entries[] = [
                    'classname' => $className,
                    'attributename' => $attribute,
                    'rules' => $transformedRules,
                    'rules_count' => count($transformedRules),
                    'note' => $configurationRepository->getNoteForAttribute($className, $attribute),
                ];
            }
        }

        return $this->json($entries);
    }

    /**
     * Return a list of possible classes to configure.
     */
    #[Route('/classes', options: ['expose' => true], methods: ['GET'])]
    public function listClassesAction(): JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        $classNames = [];
        foreach ($this->getClassNames() as $name) {
            $classNames[] = ['name' => $name];
        }

        return $this->json(['classes' => $classNames]);
    }

    /**
     * Return a list of possible attributes to configure for a class (?classname=x).
     */
    #[Route('/attributes', options: ['expose' => true], methods: ['GET'])]
    public function listAttributesAction(
        Request $request,
        ConfigurationRepository $configurationRepository,
        DefinitionInformationFactory $definitionInformationFactory
    ): JsonResponse {
        $this->checkPermission(self::CONFIG_NAME);

        if (!$request->query->has('classname')) {
            return $this->json(['attributes' => []]);
        }

        try {
            $classInformation = $definitionInformationFactory->make($request->query->get('classname'));
            $attributes = array_keys($classInformation->getAllAttributes());
        } catch (Throwable) {
            return $this->json(['attributes' => []]);
        }

        $names = array_diff($attributes, $configurationRepository->getConfiguredAttributes($classInformation->getName()));

        $attributeNames = [];
        foreach ($names as $name) {
            $attributeNames[] = [
                'name' => $name,
                'type' => $classInformation->getAttributeType($name),
            ];
        }

        return $this->json(['attributes' => $attributeNames]);
    }

    /**
     * Adds a new classname-attributename pair to the config.
     */
    #[Route('/attributes', options: ['expose' => true], methods: ['POST'])]
    public function addAttributeAction(Request $request, ConfigurationRepository $configurationRepository): JsonResponse
    {
        if (empty($request->request->get('classname')) || empty($request->request->get('attributename'))) {
            return $this->json(['status' => false]);
        }

        $this->checkPermission(self::CONFIG_NAME);
        $configurationRepository->addClassAttribute(
            (string) $request->request->get('classname'),
            (string) $request->request->get('attributename')
        );
        $configurationRepository->modifyNote(
            (string) $request->request->get('classname'),
            (string) $request->request->get('attributename'),
            $request->request->get('note'),
        );

        return $this->json([
            'status' => true,
        ]);
    }

    /**
     * Deletes a classname-attributename pair from the config.
     */
    #[Route('/attributes', options: ['expose' => true], methods: ['DELETE'])]
    public function deleteAttributeAction(Request $request, ConfigurationRepository $configurationRepository): JsonResponse
    {
        if (empty($request->request->get('classname')) || empty($request->request->get('attributename'))) {
            return $this->json(['status' => false]);
        }

        $this->checkPermission(self::CONFIG_NAME);
        $configurationRepository->deleteClassAttribute(
            (string) $request->request->get('classname'),
            (string) $request->request->get('attributename')
        );

        return $this->json([
            'status' => true,
        ]);
    }

    /**
     * Returns a list of possible constraints.
     */
    #[Route('/constraints', options: ['expose' => true], methods: ['GET'])]
    public function listConstraintsAction(ConstraintDefinitions $definitions): JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        $names = $definitions->all();
        $constraints = [];
        foreach ($names as $name => $data) {
            $constraints[] = [
                'name' => $name,
                'label' => $data['label'] ?? $name,
                'default_parameter' => $data['parameters']['default'] ?? false,
                'required_parameters' => $data['parameters']['required'] ?? [],
                'optional_parameters' => $data['parameters']['optional'] ?? [],
            ];
        }

        return $this->json(['constraints' => $constraints]);
    }

    /**
     * Adds a new constraint for a class attribute to the config.
     */
    #[Route('/constraints', options: ['expose' => true], methods: ['POST'])]
    public function addConstraintAction(Request $request, ConfigurationRepository $configurationRepository): JsonResponse
    {
        if (empty($request->request->get('classname')) || empty($request->request->get('attributename')) || empty($request->request->get('constraint'))) {
            return $this->json(['status' => false]);
        }

        $this->checkPermission(self::CONFIG_NAME);
        $configurationRepository->modifyRule(
            (string) $request->request->get('classname'),
            (string) $request->request->get('attributename'),
            (string) $request->request->get('constraint'),
            $request->request->get('params')
        );

        return $this->json([
            'status' => true,
        ]);
    }

    /**
     * Delete a constraint for a class attribute from the config.
     */
    #[Route('/constraints', options: ['expose' => true], methods: ['DELETE'])]
    public function deleteConstraintAction(Request $request, ConfigurationRepository $configurationRepository): JsonResponse
    {
        if (empty($request->request->get('classname')) || empty($request->request->get('attributename')) || empty($request->request->get('constraint'))) {
            return $this->json(['status' => false]);
        }

        $this->checkPermission(self::CONFIG_NAME);
        $configurationRepository->deleteRule(
            (string) $request->request->get('classname'),
            (string) $request->request->get('attributename'),
            (string) $request->request->get('constraint')
        );

        return $this->json([
            'status' => true,
        ]);
    }
}
