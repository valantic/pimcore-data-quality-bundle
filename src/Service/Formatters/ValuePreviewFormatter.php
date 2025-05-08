<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Service\Formatters;

use Pimcore\Model\User as PimcoreUser;
use Pimcore\Security\User\TokenStorageUserResolver;

class ValuePreviewFormatter extends ValueFormatter
{
    public function __construct(
        protected TokenStorageUserResolver $userResolver,
    ) {
    }

    public function format(mixed $input): string|array
    {
        $output = parent::format($input);
        $threshold = 50;

        if (!is_array($output)) {
            return $this->shorten($output, $threshold);
        }

        /** @var PimcoreUser $user */
        $user = $this->userResolver->getUser();
        $primaryLanguage = $user->getLanguage();
        if (array_key_exists($primaryLanguage, $output) && !empty($output[$primaryLanguage])) {
            return $this->shorten($output[$primaryLanguage], $threshold);
        }

        if (array_key_exists($primaryLanguage, $output) && empty($output[$primaryLanguage]) && count(array_filter($output)) > 0) {
            return $this->shorten(array_values(array_filter($output))[0], $threshold);
        }

        return $this->shorten(implode(', ', array_filter($output)), $threshold);
    }
}
