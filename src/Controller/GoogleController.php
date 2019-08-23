<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\UserType;

class GoogleController extends AbstractController {

    /**
     * Google redirects to back here afterwards
     *
     * @Route("/connect/google/check", name="connect_google_check")
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function connectCheckAction(Request $request) {
        if (!$this->getUser()) {
            return new JsonResponse(array('status' => false, 'message' => "User not found!"));
        } else {
            $user = $this->getUser();
            if ($user->getAddress() != '' && $user->getPhone() != '') {
                return $this->redirectToRoute('homepage');
            } else {
                return $this->redirectToRoute('google_additional_info');
            }
        }
    }

    /**
     * Add additional info to the account
     *
     * @Route("/profile/additional", name="google_additional_info")
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addAddressPhone(Request $request) {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user, array('google_additional_info' => true));
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager()->flush();
                return $this->redirectToRoute('homepage');
            }
        }
        return $this->render('google/additional_info.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
