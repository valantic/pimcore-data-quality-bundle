<?php

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Valantic\DataQualityBundle\Validation\ValidateDataObject;
use Valantic\DataQualityBundle\Config\V1\Constraints\Reader as ConstraintsConfig;
use Valantic\DataQualityBundle\Config\V1\Meta\Reader as MetaConfig;

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
     * @param ValidateDataObject $validation
     *
     * @return JsonResponse
     */
    public function showAction(Request $request, ValidateDataObject $validation): JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        $obj = DataObject::getById($request->query->getInt('id'));
        if (!$obj) {
            return $this->json([
                'score' => -1,
                'scores' => [],
                'attributes' => [],
            ]);
        }

        $validation->setObject($obj);
        $validation->validate();

        $scores = [];
        foreach ($validation->attributeScores() as $attribute => $score) {

            $scores[] = array_merge_recursive(
                [
                    'attribute' => $attribute,
                ],
                $score
            );
        }

        return $this->json([
            'score' => $validation->score(),
            'scores' => $validation->scores(),
            'attributes' => $scores,
        ]);
    }

    /**
     * Check if an object can be scored i.e. if it is configured.
     *
     * @Route("/check/", options={"expose"=true})
     *
     * @param Request $request
     * @param ConstraintsConfig $constraintsConfig
     *
     * @return JsonResponse
     */
    public function checkAction(Request $request, ConstraintsConfig $constraintsConfig): JsonResponse
    {
        $this->checkPermission(self::CONFIG_NAME);

        $obj = DataObject::getById($request->query->getInt('id'));
        if (!$obj) {
            return $this->json([
                'status' => false,
            ]);
        }

        return $this->json([
            'status' => $constraintsConfig->isObjectConfigured($obj),
        ]);

    }
}
