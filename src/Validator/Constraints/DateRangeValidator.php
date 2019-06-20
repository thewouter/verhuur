<?php

declare(strict_types=1);
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
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
                $entity->setOccupied();
            }
        }
        if ($entity->getEndDate()->getTimestamp() - $entity->getStartDate()->getTimestamp() < 0) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', serialize($value->getStartDate()))
                ->addViolation();
        }
    }
}
