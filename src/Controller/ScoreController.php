<?php

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Valantic\DataQualityBundle\Validation\ValidateObject;
use Valantic\DataQualityBundle\Config\V1\Constraints\Reader as ConfigReader;

/**
 * @Route("/admin/valantic/data-quality/score")
 */
class ScoreController extends BaseController
{

    /**
     * Show score of an object (passed via ?id=n) for the admin backend.
     *
     * @Route("/show/", options={"expose"=true})
     *
     * @param Request $request
     * @param ConfigReader $config
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function showAction(Request $request, ConfigReader $config): JsonResponse
    {
        // check permissions
        $this->checkPermission(self::CONFIG_NAME);

        $obj = DataObject::getById($request->query->getInt('id'));
        if (!$obj) {
            return $this->json([
                'scores' => [],
                'score' => -1,
            ]);
        }

        $validation = new ValidateObject($obj, $config);
        $validation->validate();

        $scores = [];
        foreach ($validation->attributeScores() as $attribute => $score) {

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
