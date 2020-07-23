<?php


namespace Valantic\DataQualityBundle\Constraints;


use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Valantic\DataQualityBundle\Validation\Colorable;
use Valantic\DataQualityBundle\Validation\DataObject\Validate;

class RelationScoreValidator extends ConstraintValidator
{
    /**
     * @var Validate
     */
    protected $validate;


    /**
     * Validation passes if all relations have a green score.
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof RelationScore) {
            throw new UnexpectedTypeException($constraint, RelationScore::class);
        }

        $this->validate = $constraint->container->get('valantic_dataquality_validate_dataobject');

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $greenCount = 0;
        $validationCount = 0;

        foreach ($value as $id) {
            $validation = clone $this->validate;
            $validation->setObject(Concrete::getById($id));
            $validation->addSkippedConstraint(RelationScore::class);
            $validation->validate();
            $validationCount++;

            if ($validation->color() === Colorable::COLOR_GREEN) {
                $greenCount++;
            }
        }

        if ($greenCount !== $validationCount) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
