<?php

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\Admin\External\AdminerController;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Yaml\Yaml;
use Valantic\DataQualityBundle\ValanticDataQualityBundle;

/**
 * @Route("/admin/valantic/data-quality")
 */
class ConfigController extends AdminerController
{
    public const CONFIG_NAME = 'plugin_valantic_dataquality_config';

    /**
     * @Route("/list", options={"expose"=true})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listAction(Request $request): JsonResponse
    {
        // check permissions
        $this->checkPermission(self::CONFIG_NAME);

        $parsed = Yaml::parseFile(ValanticDataQualityBundle::getConfigFilePath());
        $entries = [];
        foreach ($parsed as $className => $attribute) {
            foreach ($attribute as $name => $rules) {
                $r = [];
                foreach ($rules as $constraint => $args) {
                    $r[] = [
                        'constraint' => $constraint,
                        'args' => $args ? [$args] : null,
                    ];
                }
                $entries[] = [
                    'classname' => $className,
                    'attribute' => $name,
                    'rules' => $r,
                ];
            }
        }

        return $this->json($entries);
    }

    /**
     * @Route("/show/", options={"expose"=true})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function showAction(Request $request): JsonResponse
    {
        // check permissions
        $this->checkPermission(self::CONFIG_NAME);

        $obj = DataObject::getById($request->query->getInt('id'));
        $config = Yaml::parseFile(ValanticDataQualityBundle::getConfigFilePath())[$obj->getClassName()];
        $builder = Validation::createValidatorBuilder();
        $validator = $builder->getValidator();
        $attributes = [];
        foreach ($config as $field => $rules) {
            $constraints = [];
            foreach ($rules as $constraintName => $args) {
                $constraintClassName = 'Symfony\Component\Validator\Constraints\\' . $constraintName;
                $constraints[] = new $constraintClassName(...([$args ?? null]));
            }
            $violations = $validator->validate($obj->get($field), $constraints);

            $attributes[] = [
                'attribute' => $field,
                'score' => 1 - (count($violations) / count($rules)),
            ];
        }

        return $this->json(['scores' => $attributes]);
    }
}
