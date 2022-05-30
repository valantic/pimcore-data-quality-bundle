<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Service\Formatters;

class ValueFormatter implements Formatter
{
    /**
     * {@inheritDoc}
     */
    public function format(mixed $input): mixed
    {
        $output = $this->stripTags($input);
        $output = $this->trim($output);

        return $this->shorten($output);
    }

    /**
     * Strips HTML tags.
     *
     * @return array|string|string[]
     */
    protected function stripTags(mixed $input): array|string
    {
        return is_array($input) ? array_map(fn($value) => $this->stripTags($value), $input) : strip_tags((string) $input);
    }

    /**
     * Trims the input.
     *
     * @return array|string|string[]
     */
    protected function trim(mixed $input): array|string
    {
        return is_array($input) ? array_map(fn($value) => $this->trim($value), $input) : trim($input);
    }

    /**
     * Shortens the input if longer than $threshold.
     *
     * @return array|string|string[]
     */
    protected function shorten(mixed $input, int $threshold = 80): array|string
    {
        if (is_array($input)) {
            return array_map(fn($value) => $this->shorten($value, $threshold), $input);
        }

        if (strlen($input) <= $threshold) {
            return $input;
        }

        return substr($input, 0, $threshold) . ' […]';
    }
}
