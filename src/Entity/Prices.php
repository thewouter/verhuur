<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Prices {
    private $prices;

    public function __construct() {
        $this->prices = new ArrayCollection();
    }

    /**
     * @return ArrayCollection|Price[]
     */
    public function getPrices(): ArrayCollection {
        return $this->prices;
    }

    public function addPrice(Price $price): self {
        if (!$this->prices->contains($price)) {
            $this->prices[$price->getId()] = $price;
        }
        return $this;
    }

    public function removePrice(Price $price): self {
        if ($this->prices->contains($price)) {
            $this->prices->removeElement($price);
        }

        return $this;
    }
}
