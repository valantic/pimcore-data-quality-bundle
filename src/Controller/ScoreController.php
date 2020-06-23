<?php

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Valantic\DataQualityBundle\Validation\ValidateObject;
use Valantic\DataQualityBundle\Config\V1\Constraints\Reader as ConstraintsConfig;
use Valantic\DataQualityBundle\Config\V1\Locales\Reader as LocalesConfig;

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
     * @param ConstraintsConfig $constraintsConfig
     *
     * @param LocalesConfig $localesConfig
     * @return JsonResponse
     *
     */
    public function showAction(Request $request, ConstraintsConfig $constraintsConfig, LocalesConfig $localesConfig): JsonResponse
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

        $validation = new ValidateObject($obj, $constraintsConfig, $localesConfig);
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
