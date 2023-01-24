<?php

namespace Valantic\DataQualityBundle\Model;

class ObjectScore implements \JsonSerializable
{
    public function __construct(
        private ?string $color = null,
        private null|float|int $score = null,
        private array $scores = [],
        private bool $passes = false,
        private array $colors = [],
    ) {
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): ObjectScore
    {
        $this->color = $color;

        return $this;
    }

    public function getColors(): array
    {
        return $this->colors;
    }

    public function setColors(array $colors): ObjectScore
    {
        $this->colors = $colors;

        return $this;
    }

    public function getScore(): float|int|null
    {
        return $this->score;
    }

    public function setScore(float|int|null $score): ObjectScore
    {
        $this->score = $score;

        return $this;
    }

    public function getScores(): array
    {
        return $this->scores;
    }

    public function setScores(array $scores): ObjectScore
    {
        $this->scores = $scores;

        return $this;
    }

    public function getPasses(): bool
    {
        return $this->passes;
    }

    public function setPasses(bool $passes): ObjectScore
    {
        $this->passes = $passes;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'color' => $this->getColor(),
            'passes' => $this->getPasses(),
            'score' => $this->getScore(),
            'scores' => $this->getScores(),
            'colors' => $this->getColors(),
        ];
    }
}
