<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;


class DateRange extends Constraint {

    public $message = "daterange.violation.crossing";
    public $emptyStartDate = "daterange.violation.startDate";
    public $emptyEndDate = "daterange.violation.endDate";

    public $hasEndDate = true;

    public function getTargets() {
        return self::CLASS_CONSTRAINT;
    }
}
