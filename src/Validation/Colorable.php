<?php


namespace Valantic\DataQualityBundle\Validation;


interface Colorable
{
    public const COLOR_RED = 'red';
    public const COLOR_ORANGE = 'orange';
    public const COLOR_GREEN = 'green';

    /**
     * Returns a color (red, orange, green) depending on the class configuration
     * and the score of the object being validated.
     * @return string
     */
    public function color(): string;
}
