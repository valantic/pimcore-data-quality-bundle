<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Valantic\DataQualityBundle\Config\DataObjectConfigInterface;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Service\Formatters\ValueFormatter;
use Valantic\DataQualityBundle\Service\Formatters\ValuePreviewFormatter;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Shared\SortOrderTrait;
use Valantic\DataQualityBundle\Validation\DataObject\Validate;

#[Route('/admin/valantic/data-quality/score')]
class ScoreController extends BaseController
{
    use SortOrderTrait;

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
        ConfigurationRepository $configurationRepository,
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
        $validation->setGroups($request->query->all('groups'));
        $validation->validate();
        $filter = $request->get('filterText');

        $attributes = [];

        foreach ($validation->attributeScores() as $attribute => $score) {
            if (stripos($attribute, (string) $filter) === false) {
                continue;
            }

            $attributes[] = array_merge(
                [
                    'attribute' => $attribute,
                    'label' => $classInformation->getAttributeLabel($attribute),
                    'note' => $configurationRepository->getNoteForAttribute($obj::class, $attribute),
                    'type' => $classInformation->getAttributeType($attribute),
                ],
                $score->jsonSerialize(),
                [
                    'value' => $valueFormatter->format($score->getValue()),
                    'value_preview' => $valuePreviewFormatter->format($score->getValue()),
                ]
            );
        }

        $groups = [];
        foreach ($configurationRepository->getConfiguredAttributes($obj::class) as $attribute) {
            foreach ($configurationRepository->getRulesForAttribute($obj::class, $attribute) as $rule) {
                foreach ($rule['groups'] ?? [] as $group) {
                    $groups[] = $group;
                }
            }
        }

        return $this->json([
            'object' => $validation->objectScore(),
            'attributes' => $this->sortBySortOrder($attributes, 'label'),
            'groups' => array_map(
                fn (string $group): array => ['group' => $group],
                array_unique([DataObjectConfigInterface::VALIDATION_GROUP_DEFAULT, ...$groups])
            ),
        ]);
    }

    /**
     * Check if an object can be scored i.e. if it is configured.
     */
    #[Route('/check', options: ['expose' => true])]
    public function checkAction(Request $request, ConfigurationRepository $configurationRepository): JsonResponse
    {
        $obj = Concrete::getById($request->query->getInt('id'));
        if (!$obj instanceof Concrete) {
            return $this->json([
                'status' => false,
            ]);
        }

        return $this->json([
            'status' => $configurationRepository->isClassConfigured($obj::class),
        ]);
    }
}
