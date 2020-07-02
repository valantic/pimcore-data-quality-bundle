<?php


namespace Valantic\DataQualityBundle\Service\Formatters;


interface Formatter
{
    /**
     * Formats $input and returns the formatted value.
     * @param $input
     * @return mixed
     */
    public function format($input);
}
