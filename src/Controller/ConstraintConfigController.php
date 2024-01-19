<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Repository\ConstraintDefinitions;
use Valantic\DataQualityBundle\Service\CacheService;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Shared\SortOrderTrait;

#[Route('/constraint-config')]
class ConstraintConfigController extends BaseController
{
    use SortOrderTrait;

    /**
     * Returns the config for the admin editor.
     */
    #[Route('/list', options: ['expose' => true], methods: ['GET', 'POST'])]
    public function listAction(
        Request $request,
        ConstraintDefinitions $definitions,
        ConfigurationRepository $configurationRepository,
    ): JsonResponse {
        $constraintDefinitions = $definitions->all();
        $this->checkPermission(self::CONFIG_NAME);

        $filter = $request->get('filterText');

        $entries = [];

        foreach ($configurationRepository->getConfiguredClasses() as $className) {
            foreach ($configurationRepository->getConfiguredAttributes($className) as $attribute) {
                if (stripos($className, (string) $filter) === false && stripos((string) $attribute, (string) $filter) === false) {
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
                    'sort' => $className.'::'.$attribute,
                ];
            }
        }

        return $this->json($this->sortBySortOrder($entries, 'sort'));
    }

    /**
     * Return a list of possible classes to configure.
     */
    #[Route('/classes', options: ['expose' => true], methods: ['GET'])]
    public function listClassesAction(): JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        $classNames = array_map(
            fn ($name): array => ['name' => $name, 'short' => self::classBasename($name)],
            $this->getClassNames()
        );

        return $this->json(['classes' => $this->sortBySortOrder($classNames, 'name')]);
    }

    /**
     * Return a list of possible attributes to configure for a class (?classname=x).
     */
    #[Route('/attributes', options: ['expose' => true], methods: ['GET'])]
    public function listAttributesAction(
        Request $request,
        ConfigurationRepository $configurationRepository,
        DefinitionInformationFactory $definitionInformationFactory,
    ): JsonResponse {
        $this->checkPermission(self::CONFIG_NAME);

        if (!$request->query->has('classname')) {
            return $this->json(['attributes' => []]);
        }

        try {
            $classInformation = $definitionInformationFactory->make($request->query->get('classname'));
            $attributes = array_keys($classInformation->getAllAttributes());
        } catch (\Throwable) {
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

        return $this->json([
            'attributes' => $this->sortBySortOrder($attributeNames, 'name'),
        ]);
    }

    /**
     * Adds a new classname-attributename pair to the config.
     */
    #[Route('/attributes', options: ['expose' => true], methods: ['POST'])]
    public function addAttributeAction(Request $request, ConfigurationRepository $configurationRepository, CacheService $cacheService): JsonResponse
    {
        /** @var class-string $className */
        $className = (string) $request->request->get('classname');
        $attributeName = (string) $request->request->get('attributename');
        $note = $request->request->get('note');

        if (empty($className) || empty($attributeName)) {
            return $this->json(['status' => false]);
        }

        $this->checkPermission(self::CONFIG_NAME);
        $configurationRepository->addClassAttribute(
            $className,
            $attributeName
        );
        $configurationRepository->modifyNote(
            $className,
            $attributeName,
            $note,
        );
        $configurationRepository->persist();

        $cacheService->clearTag(sprintf('%s_%s', CacheService::DATA_QUALITY_CACHE_KEY, self::classBasename($className)));

        return $this->json([
            'status' => true,
        ]);
    }

    /**
     * Deletes a classname-attributename pair from the config.
     */
    #[Route('/attributes', options: ['expose' => true], methods: ['DELETE'])]
    public function deleteAttributeAction(
        Request $request,
        ConfigurationRepository $configurationRepository,
        CacheService $cacheService,
    ): JsonResponse {
        /** @var class-string $className */
        $className = (string) $request->request->get('classname');
        $attributeName = (string) $request->request->get('attributename');

        if (empty($className) || empty($attributeName)) {
            return $this->json(['status' => false]);
        }

        $this->checkPermission(self::CONFIG_NAME);
        $configurationRepository->deleteClassAttribute(
            $className,
            $attributeName
        );
        $configurationRepository->persist();

        $cacheService->clearTag(sprintf('%s_%s', CacheService::DATA_QUALITY_CACHE_KEY, self::classBasename($className)));

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

        return $this->json([
            'constraints' => $this->sortBySortOrder($constraints, 'name'),
        ]);
    }

    /**
     * Adds a new constraint for a class attribute to the config.
     */
    #[Route('/constraints', options: ['expose' => true], methods: ['POST'])]
    public function addConstraintAction(
        Request $request,
        ConfigurationRepository $configurationRepository,
        CacheService $cacheService,
    ): JsonResponse {
        /** @var class-string $className */
        $className = (string) $request->request->get('classname');
        $attributeName = (string) $request->request->get('attributename');
        $constraint = (string) $request->request->get('constraint');
        $params = $request->request->get('params');

        if (empty($className) || empty($attributeName) || empty($constraint)) {
            return $this->json(['status' => false]);
        }

        $this->checkPermission(self::CONFIG_NAME);
        $configurationRepository->modifyRule(
            $className,
            $attributeName,
            $constraint,
            $params
        );
        $configurationRepository->persist();

        $cacheService->clearTag(sprintf('%s_%s', CacheService::DATA_QUALITY_CACHE_KEY, self::classBasename($className)));

        return $this->json([
            'status' => true,
        ]);
    }

    /**
     * Delete a constraint for a class attribute from the config.
     */
    #[Route('/constraints', options: ['expose' => true], methods: ['DELETE'])]
    public function deleteConstraintAction(
        Request $request,
        ConfigurationRepository $configurationRepository,
        CacheService $cacheService,
    ): JsonResponse {
        /** @var class-string $className */
        $className = (string) $request->request->get('classname');
        $attributeName = (string) $request->request->get('attributename');
        $constraint = (string) $request->request->get('constraint');

        if (empty($className) || empty($attributeName) || empty($constraint)) {
            return $this->json(['status' => false]);
        }

        $this->checkPermission(self::CONFIG_NAME);
        $configurationRepository->deleteRule(
            $className,
            $attributeName,
            $constraint
        );
        $configurationRepository->persist();

        $cacheService->clearTag(sprintf('%s_%s', CacheService::DATA_QUALITY_CACHE_KEY, self::classBasename($className)));

        return $this->json([
            'status' => true,
        ]);
    }
}
