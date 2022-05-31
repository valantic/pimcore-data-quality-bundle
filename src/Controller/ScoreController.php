<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Service\Formatters\ValueFormatter;
use Valantic\DataQualityBundle\Service\Formatters\ValuePreviewFormatter;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Validation\DataObject\Validate;

#[Route('/admin/valantic/data-quality/score')]
class ScoreController extends BaseController
{
    /**
     * Show score of an object (passed via ?id=n) for the admin backend.
     */
    #[Route('/show/', options: ['expose' => true])]
    public function showAction(
        Request $request,
        Validate $validation,
        DefinitionInformationFactory $definitionInformationFactory,
        ValueFormatter $valueFormatter,
        ValuePreviewFormatter $valuePreviewFormatter,
        ConfigurationRepository $configurationRepository
    ): JsonResponse {
        $obj = Concrete::getById($request->query->getInt('id'));

        if (!$obj) {
            return $this->json([
                'score' => -1,
                'scores' => [],
                'attributes' => [],
                'color' => null,
            ]);
        }

        $classInformation = $definitionInformationFactory->make($obj::class);

        $validation->setObject($obj);
        $validation->validate();
        $filter = $request->get('filterText');

        $attributes = [];
        foreach ($validation->attributeScores() as $attribute => $score) {
            if ($filter && stripos($attribute, (string) $filter) === false) {
                continue;
            }

            $attributes[] = array_merge(
                [
                    'attribute' => $attribute,
                    'label' => $classInformation->getAttributeLabel($attribute),
                    'note' => $configurationRepository->getNoteForAttribute($obj::class, $attribute),
                    'type' => $classInformation->getAttributeType($attribute),
                ],
                $score,
                [
                    'value' => $valueFormatter->format($score['value']),
                    'value_preview' => $valuePreviewFormatter->format($score['value']),
                ]
            );
        }

        return $this->json([
            'object' => [
                'score' => $validation->score(),
                'color' => $validation->color(),
                'scores' => $validation->scores(),
            ],
            'attributes' => $attributes,
        ]);
    }

    /**
     * Check if an object can be scored i.e. if it is configured.
     */
    #[Route('/check', options: ['expose' => true])]
    public function checkAction(Request $request, ConfigurationRepository $configurationRepository): JsonResponse
    {
        $obj = Concrete::getById($request->query->getInt('id'));
        if (!$obj || !($obj instanceof Concrete)) {
            return $this->json([
                'status' => false,
            ]);
        }

        return $this->json([
            'status' => $configurationRepository->isClassConfigured($obj::class),
        ]);
    }
}
