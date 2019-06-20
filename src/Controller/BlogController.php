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

use App\Entity\Comment;
use App\Entity\User;
use App\Entity\LeaseRequest;
use App\Events;
use App\Form\CommentType;
use App\Form\UserType;
use App\Form\LeaseRequestType;
use App\Form\LeaseRequestEditType;
use App\Repository\LeaseRequestRepository;
use App\Repository\UserRepository;
use App\Repository\TagRepository;
use App\Repository\PriceRepository;
use App\Utils\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use \Datetime;

/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("")
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class BlogController extends AbstractController {
    private $passwordEncoder;
    private $mailer;
    private $translator;
    private $google_service;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer, TranslatorInterface $translator, \Google_Client $client) {
        $this->passwordEncoder = $passwordEncoder;
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->google_service = new \Google_Service_Gmail($client);
    }

    /**
     * @Route("/", defaults={"page": "1", "_format"="html"}, methods={"GET", "POST"}, name="homepage")
     * @Route("/rss.xml", defaults={"page": "1", "_format"="xml"}, methods={"GET"}, name="blog_rss")
     * @Route("/page/{page<[1-9]\d*>}", defaults={"_format"="html"}, methods={"GET"}, name="blog_index_paginated")
     * @Cache(smaxage="10")
     *
     * NOTE: For standard formats, Symfony will also automatically choose the best
     * Content-Type header for the response.
     * See https://symfony.com/doc/current/quick_tour/the_controller.html#using-formats
     */
    public function index(Request $request, int $page, string $_format, LeaseRequestRepository $posts, TagRepository $tags, AuthenticationUtils $helper, EventDispatcherInterface $dispatcher): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('lease_overview');
        }

        $last_username = $helper->getLastUsername();
        $error = $helper->getLastAuthenticationError();
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));
                $resetLink = substr(md5((string)rand()), 0, 30);
                $user->setPasswordReset($resetLink);
                $em->persist($user);
                $em->flush();

                $message = (new \Swift_Message('Radix Lambarene'))
                    ->setFrom('verhuurder@radixenschede.nl')
                    ->setTo($user->getEmail())
                    ->setBody(
                        $this->renderView(
                            'email/new_account.html.twig',
                            ['user' => $user]
                        ),
                        'text/html'
                    );
                $this->mailer->send($message);

                $this->addFlash('success', 'account.succesfull.confirm');
                return $this->redirectToRoute('lease_overview');
            }
        }

        return $this->render('blog/index.html.twig', array(
            'last_username' => $last_username,
            'error' => $error,
            'new_user_form' => $form->createView(), ));
    }

    /**
     * @return string
     */
    private function generateUniqueFileName() {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }

    /**
     * @Route("/overview", methods={"GET", "POST"}, name="lease_overview")
     *
     */
    public function leaseStatus(Request $request): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        $repository = $this->getDoctrine()->getRepository('App:LeaseRequest');

        $leases = $user->getLeases();
        if ((is_null($leases) || $leases->isEmpty()) && !in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('lease_add');
        }

        return $this->render('blog/overview.html.twig', array(
            'leases' => $leases,
        ));
    }

    /**
     * @Route("/addlease", methods={"GET", "POST"}, name="lease_add")
     *
     */
    public function leaseAdd(Request $request, PriceRepository $repository): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        $txt = $this->translator->trans('label.checked_calendar', ['%url%' => $this->get('router')->generate('calendar_show')]);
        $leaseRequest = new LeaseRequest();
        $leaseRequest->setPriceRepository($repository);
        $form = $this->createForm(LeaseRequestType::class, $leaseRequest, array('label' => $this->translator->trans('label.checked_calendar', ['%url%' => $this->get('router')->generate('calendar_show')])));
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $leaseRequest->setAuthor($user);
                $leaseRequest->setSlug(Slugger::slugify($user->getFullName() . '-' . $leaseRequest->getStartDate()->format("Y-m-d")));
                $user->addLease($leaseRequest);
                $leaseRequest->setPrice($leaseRequest->guessPrice());
                $em->persist($leaseRequest);
                $em->flush();

                return $this->redirectToRoute('lease_overview');
            }
        }
        return $this->render('blog/add.html.twig', array(
            'form' => $form->createView(),
            'txt' => $txt,
        ));
    }

    /**
     * @Route("/remove/{id<\d+>}", methods={"GET", "POST"}, name="lease_remove")
     */
    public function removeLease(Request $request, LeaseRequest $leaseRequest): Response {
        $status = $leaseRequest->getStatusText();
        dump($leaseRequest);
        dump($leaseRequest->getStartDate());
        $now = new DateTime();
        if (($status === 'status.placed' || $status === 'status.contract') && $leaseRequest->getStartDate() > $now) {
            $leaseRequest->setStatus(6);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'post.removed_succesfully');
        } else {
            $this->addFlash('error', 'post.removed_not_allowed');
        }
        return $this->redirectToRoute('lease_overview');
    }

    /**
     * @Route("/edit/{id<\d+>}", methods={"GET", "POST"}, name="lease_edit")
     */
    public function editLease(Request $request, LeaseRequest $leaseRequest): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        if (!$user->hasLease($leaseRequest)) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }
        $form = $this->createForm(LeaseRequestEditType::class, $leaseRequest, array(
            'signed_uploaded' => !is_null($leaseRequest->getContractSigned()),
            'editKeyTimes' => (is_null($leaseRequest->getKeyDeliver()) || is_null($leaseRequest->getKeyReturn())), ));
        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == "POST") {
            $oldSigned = $leaseRequest->getContractSigned();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $leaseRequest->setAuthor($user);
                $leaseRequest->setSlug(Slugger::slugify($user->getFullName() . '-' . $leaseRequest->getStartDate()->format("Y-m-d")));

                $file = $form->get('contract_signed')->getData();
                if ($file) {
                    $fileName = '/signed/contract_' . $this->generateUniqueFileName() . '.' . $file->guessExtension();

                    try {
                        $file->move(
                           $this->getParameter('contract_directory') . '/signed/',
                           $fileName
                       );
                    } catch (FileException $e) {
                        $this->addFlash('error', 'post.updated_unsuccessfully');
                        return $this->redirectToRoute('admin_post_edit', ['id' => $leaseRequest->getId()]);
                    }
                    $leaseRequest->setContractSigned($fileName);
                    $leaseRequest->setStatus(2);
                } else {
                    $leaseRequest->setContractSigned($oldSigned);
                }

                $em->flush();
                return $this->redirectToRoute('lease_edit', ['id' => $leaseRequest->getId()]);
            }
        }
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);
        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setAuthor($this->getUser());
            $leaseRequest->addComment($comment);
            $leaseRequest->setRead(false);
            $em->persist($comment);
            $em->flush();
            $this->addFlash('success', 'post.commented');

            return $this->redirectToRoute('lease_edit', ['id' => $leaseRequest->getId()]);
        }
        return $this->render('blog/edit.html.twig', array(
           'form' => $form->createView(),
           'leaseRequest' => $leaseRequest,
           'commentForm' => $commentForm->createView(),
           'admin' => false,
        ));
    }

    /**
     * @Route("/posts/{slug}", methods={"GET"}, name="blog_post")
     *
     * NOTE: The $post controller argument is automatically injected by Symfony
     * after performing a database query looking for a Post with the 'slug'
     * value given in the route.
     * See https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html
     */
    public function leaseRequestShow(LeaseRequest $post): Response {
        return $this->render('blog/post_show.html.twig', ['post' => $post]);
    }

    /**
     * NOTE: The ParamConverter mapping is required because the route parameter
     * (postSlug) doesn't match any of the Doctrine entity properties (slug).
     * See https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html#doctrine-converter
     */
    public function commentNew(Request $request, LeaseRequest $post, EventDispatcherInterface $eventDispatcher): Response {
        $comment = new Comment();
        $comment->setAuthor($this->getUser());
        $post->addComment($comment);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();

            // When triggering an event, you can optionally pass some information.
            // For simple applications, use the GenericEvent object provided by Symfony
            // to pass some PHP variables. For more complex applications, define your
            // own event object classes.
            // See https://symfony.com/doc/current/components/event_dispatcher/generic_event.html
            $event = new GenericEvent($comment);

            // When an event is dispatched, Symfony notifies it to all the listeners
            // and subscribers registered to it. Listeners can modify the information
            // passed in the event and they can even modify the execution flow, so
            // there's no guarantee that the rest of this controller will be executed.
            // See https://symfony.com/doc/current/components/event_dispatcher.html
            $eventDispatcher->dispatch(Events::COMMENT_CREATED, $event);

            return $this->redirectToRoute('blog_post', ['slug' => $post->getSlug()]);
        }

        return $this->render('blog/comment_form_error.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * This controller is called directly via the render() function in the
     * blog/post_show.html.twig template. That's why it's not needed to define
     * a route name for it.
     *
     * The "id" of the Post is passed in and then turned into a Post object
     * automatically by the ParamConverter.
     */
    public function commentForm(LeaseRequest $post): Response {
        $form = $this->createForm(CommentType::class);

        return $this->render('blog/_comment_form.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/search", methods={"GET"}, name="blog_search")
     */
    public function search(Request $request, LeaseRequestRepository $posts): Response {
        if (!$request->isXmlHttpRequest()) {
            return $this->render('blog/search.html.twig');
        }

        $query = $request->query->get('q', '');
        $limit = $request->query->get('l', 10);
        $foundPosts = $posts->findBySearchQuery($query, $limit);

        $results = [];
        foreach ($foundPosts as $post) {
            $results[] = [
                'title' => htmlspecialchars($post->getTitle(), ENT_COMPAT | ENT_HTML5),
                'date' => $post->getPublishedAt()->format('M d, Y'),
                'author' => htmlspecialchars($post->getAuthor()->getFullName(), ENT_COMPAT | ENT_HTML5),
                'summary' => htmlspecialchars($post->getSummary(), ENT_COMPAT | ENT_HTML5),
                'url' => $this->generateUrl('blog_post', ['slug' => $post->getSlug()]),
            ];
        }

        return $this->json($results);
    }

    /**
     * @Route("/ical.ics", methods={"GET"}, name="ical")
     */
    public function ical(Request $request, LeaseRequestRepository $repository): Response {
        $leaseRequests = $repository->findUpcomingAndLastYear();
        $response = $this->render('calendar/ical.ics.twig', array('leaseRequests' => $leaseRequests));
        $response->setContent(trim($response->getContent()));
        $response->headers->set('Content-Type', "text/calendar");
        $response->setPublic();
        $response->setMaxAge(7200);
        return $response;
    }

    /**
     * @Route("/ical_admin.ics", methods={"GET"}, name="ical_admin")
     */
    public function icalAdmin(Request $request, LeaseRequestRepository $repository): Response {
        $leaseRequests = $repository->findUpcomingAndLastYear();
        $response = $this->render('calendar/ical_admin.ics.twig', array('leaseRequests' => $leaseRequests));
        $response->setContent(trim($response->getContent()));
        $response->headers->set('Content-Type', "text/calendar");
        $response->setPublic();
        $response->setMaxAge(7200);
        return $response;
    }

    /**
     * @Route("/calendar", methods={"GET"}, name="calendar_show")
     */
    public function leaseCalendar(Request $request): Response {
        return $this->render('calendar/show.html.twig', array());
    }

    /**
     * @Route("/ical/help", methods={"GET"}, name="ical_help")
     */
    public function icalHelp(Request $request): Response {
        return $this->render('calendar/help.html.twig', array());
    }

    /**
     * @Route("/mail/update", methods={"POST"}, name="email_update_hook")
     */
    public function updateMail(Request $request, UserRepository $userRepository): Response {
        $gmail = $this->google_service;
        $data = json_decode($request->getContent(), true);
        $payload = json_decode(base64_decode($data['message']['data']), true);
        $messages = $gmail->users_messages->listUsersMessages('me', ['maxResults' => 1]);
        if (count($messages) > 0) {
            $id = $messages[0]->getId();
            $message = $gmail->users_messages->get('me', $id, array('format' => 'full'));
            $fromAddress = false;
            foreach ($message->getPayload()->getHeaders() as $header) {
                if ($header->getName() == 'From') {
                    if (strpos($header->getValue(), '<') !== false) {
                        $fromAddress = explode('<', $header->getValue())[1];
                        $fromAddress = explode('>', $fromAddress)[0];
                    }
                }
            }
            $user = $userRepository->findByEmail($fromAddress);
            if ($user) {
                $parts = $message->getPayload();
                if (count($parts->getParts()) > 0) {
                    $parts = $parts->getParts();
                } else {
                    $parts = [$parts];
                }
                $containsHTML = false;
                $containsTXT = false;
                foreach ($parts as $key => $part) {
                    if ($part->getMimeType() == 'text/plain') {
                        $containsTXT = $key + 1;
                    }
                    if ($part->getMimeType() == 'text/html') {
                        $containsHTML = $key + 1;
                    }
                }
                $leaseRequest = $user[0]->getLeases()[0];
                $comment = new Comment();
                if ($containsTXT) {
                    $cont = base64_decode($parts[$containsTXT - 1]->getBody()->getData());
                } elseif ($containsHTML) {
                    $cont = base64_decode($parts[$containsHTML - 1]->getBody()->getData());
                } else {
                    $cont = 'email without TXT or HTML';
                }
                if ($leaseRequest->getComments()[0]->getContent() != $cont) {
                    $comment->setContent(($cont));
                    $comment->setAuthor($user[0]);
                    $leaseRequest->addComment($comment);
                    $leaseRequest->setRead(false);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($comment);
                    $em->flush();
                }
            }
        }
        return new Response(serialize($data));
    }

    /**
     * @Route("/mail/watch", methods={"GET", "POST"}, name="email_update_watch")
     */
    public function updateMailWatch(Request $request, UserRepository $userRepository): Response {
        $watchreq = new \Google_Service_Gmail_WatchRequest();
        $watchreq->setLabelIds(array('INBOX'));
        $watchreq->setTopicName('projects/verhuursite-1553976897434/topics/gmailpush');
        $res = $this->google_service->users->watch('me', $watchreq);
        dump($res);
        return $this->redirectToRoute('homepage');
    }
}
