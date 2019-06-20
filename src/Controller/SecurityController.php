<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Form\ResetPasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Controller used to manage the application security.
 * See https://symfony.com/doc/current/cookbook/security/form_login_setup.html.
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class SecurityController extends AbstractController {
    private $mailer;

    public function __construct(\Swift_Mailer $mailer) {
        $this->mailer = $mailer;
    }

    /**
     * @Route("/login", name="security_login")
     */
    public function login(AuthenticationUtils $helper): Response {
        return $this->redirectToRoute('lease_overview');
    }

    /**
     * This is the route the user can use to logout.
     *
     * But, this will never be executed. Symfony will intercept this first
     * and handle the logout automatically. See logout in config/packages/security.yaml
     *
     * @Route("/logout", name="security_logout")
     */
    public function logout(): void {
        throw new \Exception('This should never be reached!');
    }

    /**
     * @Route("/reset/{password_reset}", name="security_reset")
     */
    public function resetPassword(Request $request, User $user, UserPasswordEncoderInterface $encoder): Response {
        $form = $this->createForm(ResetPasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($encoder->encodePassword($user, $form->get('password')->getData()));
            $user->setPasswordReset(null);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'reset.succesfull');
            return $this->redirectToRoute('homepage');
        }
        return $this->render('security/password_reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/reset", name="security_reset_page")
     */
    public function resetPasswordGenerate(Request $request): Response {
        $form = $this->createFormBuilder()
            ->add('email', TextType::class)
            ->add('submit', SubmitType::class, ['label' => 'submit'])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getDoctrine()->getRepository('App:User')->findByEmail($form->getData()['email']);
            $resetLink = substr(md5((string)rand()), 0, 30);
            if (empty($user)) {
                $this->addFlash('error', 'user.not_found');
                return $this->redirectToRoute('homepage');
            }
            $user = $user[0];
            $user->setPasswordReset($resetLink);
            $this->getDoctrine()->getManager()->flush();

            $message = (new \Swift_Message('Radix Lambarene'))
                ->setFrom('verhuurder@radixenschede.nl')
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        'email/password_reset.html.twig',
                        ['password_reset' => $resetLink]
                    ),
                    'text/html'
                );
            $this->mailer->send($message);

            return $this->redirectToRoute('homepage');
        }
        return $this->render('security/password_reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @route("/confirm/{password_reset}", name="confirm_account")
     *
     */
    public function confirmAccount(Request $request, User $user): Response {
        $user->setRoles($user->getRoles()); // give user access to website
        $user->setPasswordReset(null);
        $user->setConfirmed(1);
        $this->getDoctrine()->getManager()->flush();

        $token = new UsernamePasswordToken($user, null, "main", $user->getRoles());
        $this->container->get('security.token_storage')->setToken($token);
        $this->container->get('session')->set('_security_main', serialize($token));
        return $this->redirectToRoute("homepage");
    }
}
