<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Task {
    protected $description;

    protected $requests;

    public function __construct() {
        $this->requests = new ArrayCollection();
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getRequests() {
        return $this->requests;
    }
}
