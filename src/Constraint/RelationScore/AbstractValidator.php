<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Constraint\RelationScore;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Valantic\DataQualityBundle\Validation\BaseColorableInterface;
use Valantic\DataQualityBundle\Validation\DataObject\Validate;

abstract class AbstractValidator extends ConstraintValidator
{
    protected Validate $validate;
    protected bool $skipConstraintOnFurtherValidation = true;

    /**
     * Validation passes if all relations have a green score.
     */
    public function validate($value, Constraint $constraint): void
    {
        if ($constraint::class !== $this->getConstraint()) {
            throw new UnexpectedTypeException($constraint, $this->getConstraint());
        }

        $this->validate = $constraint->container->get('valantic_dataquality_validate_dataobject');

        if ($value === null || $value === '') {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $validCount = 0;
        $totalCount = 0;
        $failedIds = [];

        foreach ($value as $id) {
            $validation = clone $this->validate;
            $validation->setObject(Concrete::getById($id));
            if ($this->skipConstraintOnFurtherValidation) {
                $validation->addSkippedConstraint(AbstractConstraint::class);
            }
            $validation->validate();
            $totalCount++;

            if ($validation->color() === $this->getThresholdKey() || ($this->getThresholdKey() === BaseColorableInterface::COLOR_ORANGE && $validation->color() === BaseColorableInterface::COLOR_GREEN)) {
                $validCount++;
            } else {
                $failedIds[] = $id;
            }
        }

        if ($validCount !== $totalCount) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ ids }}', implode(', ', $failedIds))
                ->addViolation();
        }
    }

    /**
     * Get the Colorable threshold for this validator.
     */
    abstract protected function getThresholdKey(): string;

    /**
     * Get the constraint this validator expects.
     */
    abstract protected function getConstraint(): string;
}
