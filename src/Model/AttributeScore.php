<?php

namespace Valantic\DataQualityBundle\Model;

use JsonSerializable;

class AttributeScore implements JsonSerializable
{
    public function __construct(
        private ?string $color = null,
        private array $colors = [],
        private null|float|int $score = null,
        private array $scores = [],
        private mixed $value = null,
        private bool $passes = false,
    ) {
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): AttributeScore
    {
        $this->color = $color;

        return $this;
    }

    public function getColors(): array
    {
        return $this->colors;
    }

    public function setColors(array $colors): AttributeScore
    {
        $this->colors = $colors;

        return $this;
    }

    public function getScore(): float|int|null
    {
        return $this->score;
    }

    public function setScore(float|int|null $score): AttributeScore
    {
        $this->score = $score;

        return $this;
    }

    public function getScores(): array
    {
        return $this->scores;
    }

    public function setScores(array $scores): AttributeScore
    {
        $this->scores = $scores;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): AttributeScore
    {
        $this->value = $value;

        return $this;
    }

    public function getPasses(): bool
    {
        return $this->passes;
    }

    public function setPasses(bool $passes): AttributeScore
    {
        $this->passes = $passes;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'color' => $this->getColor(),
            'colors' => $this->getColors(),
            'score' => $this->getScore(),
            'scores' => $this->getScores(),
            'passes' => $this->getPasses(),
            'value' => $this->getValue(),
        ];
    }
}
