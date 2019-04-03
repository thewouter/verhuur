<?php

declare(strict_types=1);

namespace App\Entity;

class Price {
    private $id;

    private $price;

    public function getId(): ?string {
        return $this->id;
    }

    public function getPrice(): ?float {
        return $this->price;
    }

    public function setPrice(float $price): self {
        $this->price = $price;

        return $this;
    }
}
