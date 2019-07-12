<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\PriceRepository;
use App\Entity\Prices;

class PublicController extends AbstractController {
    /**
     *@Route("/prices/show", methods={"GET"}, name="prices_show")
     */
    public function prices(Request $request, PriceRepository $prices): Response {
        $allPrices = $prices->findAll();
        $prices = new Prices();
        foreach ($allPrices as $value) {
            $prices->addPrice($value);
        }

        return $this->render('public/prices.html.twig', array(
             'prices' => $prices,
         ));
    }

    /**
     *@Route("/photos/show", methods={"GET"}, name="photos_show")
     */
    public function photos(Request $request, PriceRepository $prices): Response {
        return $this->render('public/photos.html.twig');
    }
}
