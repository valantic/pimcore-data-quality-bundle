<?php

namespace Valantic\DataQualityBundle\Repository;

class ConstraintDefinitions
{
    /**
     * @var CustomConstraintParameters[]
     */
    protected $customConstraints;

    /**
     * @param iterable $taggedConstraints
     */
    public function __construct(iterable $taggedConstraints)
    {
        $customContraints = [];
        foreach ($taggedConstraints->getIterator() as $taggedConstraint) {
            if ($taggedConstraint instanceof CustomConstraintParameters) {
                $customContraints[] = $taggedConstraint;
            }
        }
        $this->customConstraints = $customContraints;
    }

    /**
     * All constraints.
     * @return array
     */
    public function all(): array
    {
        return array_merge_recursive($this->symfony(), $this->custom());
    }

    /**
     * Configuration for custom constraints.
     * @return array
     */
    protected function custom(): array
    {
        $definitions = [];

        foreach ($this->customConstraints as $constraint) {
            $definitions[get_class($constraint)] = [
                'parameters' => array_filter([
                    'default' => $constraint->defaultParameter(),
                    'optional' => $constraint->optionalParameters(),
                    'required' => $constraint->requiredParameters(),
                ]),
            ];
        }

        return $definitions;
    }

    /**
     * Configuration for Symfony 4.4 constraints.
     * @return array
     */
    protected function symfony(): array
    {
        return [
            'Bic' => [
                'parameters' => [
                    'optional' => ['iban' => null, 'ibanPropertyPath' => null],
                ],
            ],
            'Blank' => [],
            'CardScheme' => [
                'parameters' => [
                    'default' => 'schemes',
                    'required' => [
                        'schemes' => [
                            'AMEX',
                            'CHINA_UNIONPAY',
                            'DINERS',
                            'DISCOVER',
                            'INSTAPAYMENT',
                            'JCB',
                            'LASER',
                            'MAESTRO',
                            'MASTERCARD',
                            'MIR',
                            'UATP',
                            'VISA',
                        ],
                    ],
                ],
            ],
            'Choice' => [
                'parameters' => [
                    'default' => 'choices',
                    'optional' => ['callback' => [], 'max' => 0, 'min' => 0, 'multiple' => false],
                    'required' => ['choices' => [],],
                ],
            ],
            'Count' => [
                'parameters' => [
                    'optional' => ['max' => 0, 'min' => 0],
                ],
            ],
            'Country' => [],
            'Currency' => [],
            'Date' => [],
            'DateTime' => [
                'parameters' => [
                    'optional' => ['format' => 'Y-m-d H:i:s'],
                ],
            ],
            'DivisibleBy' => [
                'parameters' => [
                    'default' => 'value',
                    'required' => ['value' => 1],
                ],
            ],
            'Email' => [
                'parameters' => [
                    'optional' => ['mode' => 'loose|strict|html5'],
                ],
            ],
            'EqualTo' => [
                'parameters' => [
                    'default' => 'value',
                    'optional' => ['propertyPath' => null,],
                    'required' => ['value' => ''],
                ],
            ],
            'Expression' => [
                'parameters' => [
                    'default' => 'expression',
                    'optional' => ['expression' => '', 'values' => []],
                ],
            ],
            'File' => [
                'parameters' => [
                    'optional' => ['binaryFormat' => null, 'maxSize' => '20Mi', 'mimeTypes' => []],
                ],
            ],
            'GreaterThan' => [
                'parameters' => [
                    'default' => 'value',
                    'optional' => ['propertyPath' => null, 'value' => ''],
                ],
            ],
            'GreaterThanOrEqual' => [
                'parameters' => [
                    'default' => 'value',
                    'optional' => ['propertyPath' => null,],
                    'required' => ['value' => ''],
                ],
            ],
            'Iban' => [],
            'IdenticalTo' => [
                'parameters' => [
                    'default' => 'value',
                    'optional' => ['propertyPath' => null,],
                    'required' => ['value' => ''],
                ],
            ],
            'Image' => [
                'parameters' => [
                    'optional' => [
                        'allowLandscape' => true,
                        'allowPortrait' => true,
                        'allowSquare' => true,
                        'detectCorrupted' => false,
                        'maxHeight' => 0,
                        'maxPixels' => 0,
                        'maxRatio' => 1,
                        'maxWidth' => 0,
                        'mimeTypes' => 'image/*',
                        'minPixels' => 0,
                        'minRation' => 0,
                        'minWidth' => 0,
                    ],
                ],
            ],
            'Ip' => [
                'parameters' => [
                    'optional' => ['version' => '4'],
                ],
            ],
            'IsFalse' => [],
            'IsNull' => [],
            'IsTrue' => [],
            'Isbn' => [
                'parameters' => [
                    'optional' => ['type' => null],
                ],
            ],
            'Issn' => [
                'parameters' => [
                    'optional' => ['caseSensitive' => false, 'requireHyphen' => false],
                ],
            ],
            'Json' => [],
            'Language' => [],
            'Length' => [
                'parameters' => [
                    'optional' => ['allowEmptyString' => false, 'charset' => 'utf-8', 'max' => 0, 'min' => 0],
                ],
            ],
            'LessThan' => [
                'parameters' => [
                    'default' => 'value',
                    'optional' => ['propertyPath' => null,],
                    'required' => ['value' => ''],
                ],
            ],
            'LessThanOrEqual' => [
                'parameters' => [
                    'default' => 'value',
                    'optional' => ['propertyPath' => null,],
                    'required' => ['value' => ''],
                ],
            ],
            'Locale' => [],
            'Luhn' => [],
            'Negative' => [],
            'NegativeOrZero' => [],
            'NotBlank' => [
                'parameters' => [
                    'optional' => ['allowNull' => false],
                ],
            ],
            'NotCompromisedPassword' => [
                'parameters' => [
                    'optional' => ['skipOnError' => false, 'threshold' => 1],
                ],
            ],
            'NotEqualTo' => [
                'parameters' => [
                    'default' => 'value',
                    'optional' => ['propertyPath' => null,],
                    'required' => ['value' => ''],
                ],
            ],
            'NotIdenticalTo' => [
                'parameters' => [
                    'default' => 'value',
                    'optional' => ['propertyPath' => null],
                    'required' => ['value' => ''],
                ],
            ],
            'NotNull' => [],
            'Positive' => [],
            'PositiveOrZero' => [],
            'Range' => [
                'parameters' => [
                    'optional' => [
                        'max' => 0,
                        'maxPropertyPath' => null,
                        'min' => '1970-01-01',
                        'minPropertyPath' => null,
                    ],
                ],
            ],
            'Regex' => [
                'parameters' => [
                    'default' => 'pattern',
                    'optional' => ['htmlPattern' => null, 'match' => true],
                    'required' => ['pattern' => ''],
                ],
            ],
            'Time' => [],
            'Timezone' => [
                'parameters' => [
                    'optional' => ['countryCode' => null, 'intlCompatible' => false, 'zone' => 2047],
                ],
            ],
            'Type' => [
                'parameters' => [
                    'default' => 'type',
                    'optional' => [],
                    'required' => [
                        'type' => [
                            'array',
                            'bool',
                            'callable',
                            'float',
                            'double',
                            'int',
                            'integer',
                            'iterable',
                            'long',
                            'null',
                            'numeric',
                            'object',
                            'real',
                            'resource',
                            'scalar',
                            'string',
                            'alnum',
                            'alpha',
                            'cntrl',
                            'digit',
                            'graph',
                            'lower',
                            'print',
                            'punct',
                            'space',
                            'upper',
                            'xdigit',
                        ],
                    ],
                ],
            ],
            'Unique' => [],
            'Url' => [
                'parameters' => [
                    'optional' => ['protocols' => ['http', 'https', 'relativeProtocol' => false]],
                ],
            ],
            'Uuid' => [
                'parameters' => [
                    'optional' => ['strict' => true, 'versions' => [1, 2, 3, 4, 5]],
                ],
            ],
        ];
    }
}
