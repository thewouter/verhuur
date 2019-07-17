<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Custom time class used to add the NULL option to a form.
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class Time {
    protected $hour;

    protected $minutes;

    public function getHour() {
        return $this->hour;
    }

    public function getMinute() {
        return $this->minutes;
    }

    public function setHour($hour) {
        $this->hour = $hour;
    }

    public function setMinute($minutes) {
        $this->minutes = $minutes;
    }
}
