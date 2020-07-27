<?php


namespace Valantic\DataQualityBundle\Service\Formatters;


interface Formatter
{
    /**
     * Formats $input and returns the formatted value.
     *
     * @param mixed $input
     *
     * @return mixed
     */
    public function format($input);
}
