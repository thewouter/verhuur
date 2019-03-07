<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class DateRangeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $entity = $value;
        $hasEndDate = true;
       if ($constraint->hasEndDate !== null) {
           $hasEndDate = $constraint->hasEndDate;
       }
       if($entity->getEndDate()->getTimestamp()-$entity->getStartDate()->getTimestamp() < 0){
           $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', serialize($value))
                ->addViolation();
       }

       if ($entity->getStartDate() !== null) {
           if ($hasEndDate) {
               if ($entity->getEndDate() !== null) {
                   if ($entity->getStartDate() > $entity->getEndDate()) {
                       return false;
                   }
                   return true;
               } else {
                   return false;
               }
           } else {
               if ($entity->getEndDate() !== null) {
                   if ($entity->getStartDate() > $entity->getEndDate()) {
                       return false;
                   }
               }
               return true;
           }
        } else {
           return false;
        }
    }
}
