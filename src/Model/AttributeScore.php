<?php

namespace Valantic\DataQualityBundle\Model;

use JsonSerializable;

class AttributeScore implements JsonSerializable
{
    public function __construct(
        public ?string $color = null,
        public array $colors = [],
        public null|float|int $score = null,
        public array $scores = [],
        public mixed $value = null,
        public bool $passes = false,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'color' => $this->color,
            'colors' => $this->colors,
            'score' => $this->score,
            'scores' => $this->scores,
            'passes' => $this->passes,
            'value' => $this->value,
        ];
    }
}
