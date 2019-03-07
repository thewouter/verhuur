<?php

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
use App\Repository\TagRepository;
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



/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("")
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class BlogController extends AbstractController{
    private $passwordEncoder;
    private $mailer;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, \Swift_Mailer $mailer) {
        $this->passwordEncoder = $passwordEncoder;
        $this->mailer = $mailer;
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
    public function index(Request $request, int $page, string $_format, LeaseRequestRepository $posts, TagRepository $tags, AuthenticationUtils $helper, EventDispatcherInterface $dispatcher): Response{
        if ($this->getUser()){
            return $this->redirectToRoute('lease_overview');
        }

        $last_username = $helper->getLastUsername();
        $error = $helper->getLastAuthenticationError();
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        if ($request->getMethod() == "POST"){
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                $em = $this->getDoctrine()->getManager();
                $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));
                $user->setRoles($user->getRoles());
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'account.succesfull');
                /*$token = new UsernamePasswordToken($user->getUsername(), $user->getPassword(), "main", $user->getRoles());
                $event = new InteractiveLoginEvent($request, $token);
                $dispatcher->dispatch("security.interactive_login", $event);
                return $this->redirectToRoute('lease_overview');*/
            }
        }

        return $this->render('blog/index.html.twig', array(
            'last_username' => $last_username,
            'error' => $error,
            'new_user_form' => $form->createView()));
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
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
        if( (is_null($leases) || $leases->isEmpty()) && !in_array('ROLE_ADMIN', $this->getUser()->getRoles())){
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
    public function leaseAdd(Request $request): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();

        $leaseRequest = new LeaseRequest();
        $form = $this->createForm(LeaseRequestType::class, $leaseRequest);
        if ($request->getMethod() == "POST"){
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                $em = $this->getDoctrine()->getManager();
                $leaseRequest->setAuthor($user);
                $leaseRequest->setSlug(Slugger::slugify($user->getFullName().'-'.$leaseRequest->getStartDate()->format("Y-m-d")));
                $user->addLease($leaseRequest);
                $leaseRequest->setPrice($leaseRequest->guessPrice());
                $em->persist($leaseRequest);
                $em->flush();

                return $this->redirectToRoute('lease_overview');
            }
        }
        return $this->render('blog/add.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    /**
     * @Route("/edit/{slug}", methods={"GET", "POST"}, name="lease_edit")
     */
    public function editLease(Request $request, LeaseRequest $leaseRequest): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        $form = $this->createForm(LeaseRequestEditType::class, $leaseRequest, array('signed_uploaded' => !is_null($leaseRequest->getContractSigned())));
        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == "POST"){
            $oldSigned = $leaseRequest->getContractSigned();
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
               $leaseRequest->setAuthor($user);
               $leaseRequest->setSlug(Slugger::slugify($user->getFullName().'-'.$leaseRequest->getStartDate()->format("Y-m-d")));

               $file = $form->get('contract_signed')->getData();
               if ($file){
                   $fileName = '/signed/contract_' . $this->generateUniqueFileName().'.'.$file->guessExtension();

                   try {
                       $file->move(
                           $this->getParameter('contract_directory').'/signed/',
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
               return $this->redirectToRoute('lease_edit', ['slug' => $leaseRequest->getSlug()]);
           }
        }
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);
        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setAuthor($this->getUser());
            $leaseRequest->addComment($comment);
            $em->persist($comment);
            $em->flush();
            $this->addFlash('success', 'post.commented');

            return $this->redirectToRoute('lease_edit', ['slug' => $leaseRequest->getSlug()]);
        }
        return $this->render('blog/edit.html.twig', array(
           'form' => $form->createView(),
           'leaseRequest' => $leaseRequest,
           'commentForm' => $commentForm->createView(),
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
    public function leaseRequestShow(LeaseRequest $post): Response
    {
        return $this->render('blog/post_show.html.twig', ['post' => $post]);
    }

    /**
     * NOTE: The ParamConverter mapping is required because the route parameter
     * (postSlug) doesn't match any of the Doctrine entity properties (slug).
     * See https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html#doctrine-converter
     */
    public function commentNew(Request $request, LeaseRequest $post, EventDispatcherInterface $eventDispatcher): Response
    {
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
    public function commentForm(LeaseRequest $post): Response
    {
        $form = $this->createForm(CommentType::class);

        return $this->render('blog/_comment_form.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/search", methods={"GET"}, name="blog_search")
     */
    public function search(Request $request, LeaseRequestRepository $posts): Response
    {
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
    print("adaaaaaaaaaaaafad:");
        $leaseRequests = $repository->findUpcomingAndLastYear();
        $response = $this->render('calendar/ical.ics.twig', array('leaseRequests' => $leaseRequests));
        $response->setContent(trim($response->getContent()));
        $response->headers->set('Content-Type', "text/calendar");
        $response->setPublic();
        $response->setMaxAge(7200);
        print("adfad");
        return $response;
    }
}
