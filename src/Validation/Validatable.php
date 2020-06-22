<?php


namespace Valantic\DataQualityBundle\Validation;


interface Validatable
{
    /**
     * Run validation based on its configuration.
     * @return void
     */
    public function validate();
}
