<?php

namespace Valantic\DataQualityBundle\Service\Formatters;

class ValueFormatter implements Formatter
{
    /**
     * {@inheritDoc}
     */
    public function format($input)
    {
        $output = $this->stripTags($input);
        $output = $this->trim($output);
        $output = $this->shorten($output);

        return $output;
    }

    /**
     * Strips HTML tags.
     * @param $input
     * @return array|string|string[]
     */
    protected function stripTags($input)
    {
        if (is_array($input)) {
            return array_map(function ($value) {
                return $this->stripTags($value);
            }, $input);
        }

        return strip_tags($input);
    }

    /**
     * Trims the input.
     * @param $input
     * @return array|string|string[]
     */
    protected function trim($input)
    {
        if (is_array($input)) {
            return array_map(function ($value) {
                return $this->trim($value);
            }, $input);
        }

        return trim($input);
    }

    /**
     * Shortens the input if longer than $threshold.
     * @param $input
     * @param int $threshold
     * @return array|string|string[]
     */
    protected function shorten($input, $threshold = 80)
    {
        if (is_array($input)) {
            return array_map(function ($value) use ($threshold) {
                return $this->shorten($value, $threshold);
            }, $input);
        }

        if (strlen($input) <= $threshold) {
            return $input;
        }

        return substr($input, 0, $threshold) . ' […]';
    }
}
