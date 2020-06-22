<?php

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\Admin\External\AdminerController;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Yaml\Yaml;
use Valantic\DataQualityBundle\Config\V1\Config;
use Valantic\DataQualityBundle\ValanticDataQualityBundle;
use Valantic\DataQualityBundle\Validation\ValidateObject;

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
     * @param Config $config
     *
     * @return JsonResponse
     */
    public function listAction(Request $request, Config $config): JsonResponse
    {
        // check permissions
        $this->checkPermission(self::CONFIG_NAME);

        $entries = [];
        foreach ($config->getValidatableClasses() as $className) {
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
                    'attribute' => $attribute,
                    'rules' => $transformedRules,
                ];
            }
        }

        return $this->json($entries);
    }

    /**
     * @Route("/show/", options={"expose"=true})
     *
     * @param Request $request
     * @param Config $config
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function showAction(Request $request, Config $config): JsonResponse
    {
        // check permissions
        $this->checkPermission(self::CONFIG_NAME);

        $obj = DataObject::getById($request->query->getInt('id'));
        if(!$obj){
            return $this->json([
                'scores' => [],
                'score' => -1,
            ]);
        }

        $validation = new ValidateObject($obj, $config);
        $validation->validate();

        $scores=[];
        foreach ($validation->attributeScores() as $attribute=>$score){

            $scores[] = [
                'attribute' => $attribute,
                'score' => $score,
            ];
        }

        return $this->json([
            'scores' => $scores,
            'score' => $validation->score(),
        ]);
    }
}
