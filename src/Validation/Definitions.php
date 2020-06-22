<?php

namespace Valantic\DataQualityBundle\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validation;

class Definitions
{
    public function symfony()
    {
        return [
            'All',
            'Bic',
            'Blank',
            'Callback',
            'CardScheme',
            'Choice',
            'Collection',
            'Count',
            'Country',
            'Currency',
            'Date',
            'DateTime',
            'DivisibleBy',
            'Email',
            'EqualTo',
            'Expression',
            'File',
            'GreaterThan',
            'GreaterThanOrEqual',
            'Iban',
            'IdenticalTo',
            'Image',
            'Ip',
            'IsFalse',
            'IsNull',
            'IsTrue',
            'Isbn',
            'Issn',
            'Json',
            'Language',
            'Length',
            'LessThan',
            'LessThanOrEqual',
            'Locale',
            'Luhn',
            'Negative',
            'NegativeOrZero',
            'NotBlank',
            'NotCompromisedPassword',
            'NotEqualTo',
            'NotIdenticalTo',
            'NotNull',
            'Positive',
            'PositiveOrZero',
            'Range',
            'Regex',
            'Time',
            'Timezone',
            'Traverse',
            'Type',
            'Unique',
            'UniqueEntity',
            'Url',
            'UserPassword',
            'Uuid',
            'Valid',
        ];
    }
}
