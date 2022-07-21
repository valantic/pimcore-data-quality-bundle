<?php

namespace Valantic\DataQualityBundle\Model;

use JsonSerializable;

class ObjectScore implements JsonSerializable
{
    public function __construct(
        public ?string $color = null,
        public null|float|int $score = null,
        public array $scores = [],
        public bool $passes = false,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'color' => $this->color,
            'passes' => $this->passes,
            'score' => $this->score,
            'scores' => $this->scores,
        ];
    }
}
