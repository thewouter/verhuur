<?php

declare(strict_types=1);
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Doctrine\ORM\EntityManagerInterface;

class DateRangeValidator extends ConstraintValidator {
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function validate($value, Constraint $constraint) {
        $entity = $value;
        $occupied = $this->em->getRepository('App:LeaseRequest')->findInDateRange($entity->getStartDate(), $entity->getEndDate());
        if (!empty($occupied)) {
            if ($occupied[0]->getId() != $entity->getId()) {
                $this->context->buildViolation($constraint->messageOccupied)
                     ->setParameter('{{ string }}', serialize($value))
                     ->addViolation();
            }
        }
        if ($entity->getEndDate()->getTimestamp() - $entity->getStartDate()->getTimestamp() < 0) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', serialize($value))
                ->addViolation();
        }
    }
}
