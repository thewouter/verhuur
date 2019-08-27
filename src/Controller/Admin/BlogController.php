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

namespace App\Controller\Admin;

use App\Entity\LeaseRequest;
use App\Entity\Comment;
use App\Entity\Prices;
use App\Entity\Task;
use App\Repository\LeaseRequestRepository;
use App\Repository\PriceRepository;
use App\Repository\UserRepository;
use App\Utils\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Form\LeaseRequestAdminType;
use App\Form\CommentType;
use App\Form\PricesType;
use App\Form\TaskType;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Controller used to manage blog contents in the backend.
 *
 * Please note that the application backend is developed manually for learning
 * purposes. However, in your real Symfony application you should use any of the
 * existing bundles that let you generate ready-to-use backends without effort.
 *
 * See http://knpbundles.com/keyword/admin
 *
 * @Route("/admin/post")
 * @IsGranted("ROLE_ADMIN")
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class BlogController extends AbstractController {
    private $mailer;

    public const RULES_FILE = '/rules.pdf';
    public const REQUIREMENTS_FILE = '/requirements.pdf';

    public function __construct(\Swift_Mailer $mailer) {
        $this->mailer = $mailer;
    }

    /**
     * Lists all Post entities.
     *
     * This controller responds to two different routes with the same URL:
     *   * 'admin_post_index' is the route with a name that follows the same
     *     structure as the rest of the controllers of this class.
     *   * 'admin_index' is a nice shortcut to the backend homepage. This allows
     *     to create simpler links in the templates. Moreover, in the future we
     *     could move this annotation to any other controller while maintaining
     *     the route name and therefore, without breaking any existing link.
     *
     * @Route("/", defaults={"page": "1"}, methods={"GET"}, name="admin_index")
     * @Route("/page/{page<[1-9]\d*>}", defaults={"_format"="html"}, methods={"GET"}, name="admin_blog_index_paginated")
     * @Route("/", defaults={"page": "1"}, methods={"GET"}, name="admin_post_index")
     */
    public function index(LeaseRequestRepository $posts, PriceRepository $repository, int $page): Response {
        $requests = $posts->findLatest($page);
        $unreadCount = 0;
        foreach ($requests as $key => $value) {
            $value->setPriceRepository($repository);
            if (!$value->getRead()) {
                $unreadCount = $unreadCount + 1;
            }
        }
        return $this->render('admin/blog/index.html.twig', [
            'posts' => $requests,
            'unread' => $unreadCount, ]);
    }

    /**
     * Unread lease request
     *
     * @route("{id<\d+>}/unread", name="admin_post_unread")
     */
    public function unread(Request $request, LeaseRequest $leaseRequest): Response {
        $leaseRequest->setRead(false);
        $this->getDoctrine()->getManager()->flush();
        return $this->redirectToRoute('admin_index');
    }

    /**
     * Displays a form to edit an existing LeaseRequest entity.
     *
     * @Route("/{id<\d+>}/edit",methods={"GET", "POST"}, name="admin_post_edit")
     */
    public function edit(Request $request, LeaseRequest $leaseRequest, PriceRepository $repository): Response {
        $form = $this->createForm(LeaseRequestAdminType::class, $leaseRequest, array('signed_uploaded' => !is_null($leaseRequest->getContractSigned())));

        $em = $this->getDoctrine()->getManager();
        $leaseRequest->setRead(true);
        $leaseRequest->setPriceRepository($repository);
        $em->flush();

        $oldSigned = $leaseRequest->getContractSigned();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($oldSigned !== null && $form->get('remove_signed_contract')->isClicked()) {
                unlink($this->getParameter('contract_directory') . $oldSigned);
                $leaseRequest->setContractSigned(null);
            } else {
                $leaseRequest->setSlug(Slugger::slugify($leaseRequest->getTitle()));
                if ($oldSigned == null) {
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
                    } else {
                        $leaseRequest->setContractSigned($oldSigned);
                    }
                }
            }
            $em->flush();
            $this->addFlash('success', 'post.updated_successfully');
            return $this->redirectToRoute('admin_post_edit', ['id' => $leaseRequest->getId()]);
        }

        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment, array('is_admin' => true));
        $commentForm->handleRequest($request);
        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setAuthor($this->getUser());
            $leaseRequest->addComment($comment);
            $em->persist($comment);
            $em->flush();
            $this->addFlash('success', 'post.commented');

            $message = (new \Swift_Message('Radix Lambarene'))
                ->setFrom('verhuurder@radixenschede.nl')
                ->setTo($leaseRequest->getAuthor()->getEmail())
                ->setBody(
                    $this->renderView(
                        'email/new_message.html.twig',
                        ['content' => $comment->getContent(),
                         'leaseRequest' => $leaseRequest, ]
                    ),
                    'text/html'
                );
            $this->mailer->send($message);
            return $this->redirectToRoute('admin_post_edit', ['id' => $leaseRequest->getId()]);
        }

        return $this->render('admin/blog/edit.html.twig', [
            'leaseRequest' => $leaseRequest,
            'form' => $form->createView(),
            'commentForm' => $commentForm->createView(),
            'admin' => true,
        ]);
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
     * Deletes a Post entity.
     *
     * @Route("/{id}/delete", methods={"POST"}, name="admin_post_delete")
     * @IsGranted("delete", subject="post")
     */
    public function delete(Request $request, LeaseRequest $post): Response {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_post_index');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($post);
        $em->flush();

        $this->addFlash('success', 'post.deleted_successfully');

        return $this->redirectToRoute('admin_post_index');
    }

    /**
     *
     *
     *@Route("/{id}/contract.html", methods={"GET"}, name="admin_contract_html")
     */
    public function contractHtml(Request $request, LeaseRequest $leaseRequest, PriceRepository $repository): Response {
        $leaseRequest->setPriceRepository($repository);
        return $this->render('pdf/contract.html.twig', array(
             'leaseRequest' => $leaseRequest, ));
    }

    /**
     *@Route("/{id}/contract.pdf", methods={"GET"}, name="admin_contract_pdf")
     */
    public function contractPdf(Request $request, LeaseRequest $leaseRequest, PriceRepository $repository): Response {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('enable_remote', true);


        $leaseRequest->setPriceRepository($repository);

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);

        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('pdf/contract.html.twig', [
            'title' => "Contract Radix Enschede Verhuur",
            'leaseRequest' => $leaseRequest,
            ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        $output = $dompdf->output();
        $publicDirectory = $this->getParameter('contract_directory');
        $uid = $this->generateUniqueFileName();
        $pdfFilepath = $publicDirectory . '/unsigned/contract_' . $uid . '.pdf';
        file_put_contents($pdfFilepath, $output);
        $leaseRequest->setContract('/unsigned/contract_' . $uid . '.pdf');
        $this->getDoctrine()->getManager()->flush();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("mypdf.pdf", [
            "Attachment" => true,
            ]);

        return $this->redirectToRoute('admin_post_edit', ['id' => $leaseRequest->getId()]);
    }

    /**
     *
     *
     *@Route("/{id}/contract/send", methods={"GET"}, name="admin_contract_email")
     */
    public function sendContract(Request $request, LeaseRequest $leaseRequest, PriceRepository $repository): Response {

        $leaseRequest->setPriceRepository($repository);
        if (is_null($leaseRequest->getContract())) {
            $this->addFlash('error', 'contract.no_available');
            return $this->edit($request, $leaseRequest);
        }
        $publicDirectory = $this->getParameter('contract_directory');
        $message = (new \Swift_Message('Radix Lambarene'))
               ->setFrom('verhuurder@radixenschede.nl')
               ->setTo($leaseRequest->getAuthor()->getEmail())
               ->setBody(
                   $this->renderView(
                       'email/contract.html.twig',
                       ['leaseRequest' => $leaseRequest]
                   ),
                   'text/html'
               )
               ->attach(\Swift_Attachment::fromPath($publicDirectory . $leaseRequest->getContract())->setFilename('contract.pdf'))
               ->attach(\Swift_Attachment::fromPath($publicDirectory . self::RULES_FILE)->setFilename('kampregels.pdf'))
               ->attach(\Swift_Attachment::fromPath($publicDirectory . self::REQUIREMENTS_FILE)->setFilename('huurvoorwaarden.pdf'));
        $this->mailer->send($message);
        $this->addFlash('success', 'contract.emailed');
        $leaseRequest->setStatus(1);
        $this->getDoctrine()->getManager()->flush();
        return $this->redirectToRoute('admin_post_edit', ['id' => $leaseRequest->getId()]);
    }

    /**
     *@Route("/statistics", methods={"GET"}, name="admin_statistics")
     */
    public function statistics(Request $request, LeaseRequestRepository $posts, PriceRepository $priceRepository): Response {
        $allRequests = $posts->findAll();
        $perYear = [];
        foreach ($allRequests as $request) {
            $status = $request->getStatusText();
            if ($status != 'status.rejected' && $status != 'status.retracted') {
                $request->setPriceRepository($priceRepository);
                if (!in_array($request->getStartDate()->format('Y'), array_keys($perYear))) {
                    $perYear[$request->getStartDate()->format('Y')] = array($request);
                } else {
                    array_push($perYear[$request->getStartDate()->format('Y')], $request);
                }
            }
        }
        $stats = [];
        foreach ($perYear as $year => $requests) {
            $stats[$year] = 0;
            foreach ($requests as $request) {
                $stats[$year] += $request->getPrice();
            }
        }
        krsort($perYear);
        return $this->render('admin/statistics.html.twig', array(
             'years' => $perYear,
             'stats' => $stats,
         ));
    }

    /**
     *@Route("/payments/{year}", methods={"GET", "POST"}, name="admin_payments_overview")
     */
    public function payments(Request $webRequest, LeaseRequestRepository $posts, PriceRepository $priceRepository, int $year): Response {
        $allRequests = $posts->findAll();
        $years = [];

        foreach ($allRequests as $key => $request) {
            $status = $request->getStatusText();
            if ($status != 'status.rejected' && $status != 'status.retracted') {
                $request->setPriceRepository($priceRepository);
                if (!in_array($request->getStartDate()->format('Y'), $years)) {
                    array_push($years, $request->getStartDate()->format('Y'));
                }
                if ($request->getStartDate()->format('Y') != $year) {
                    unset($allRequests[$key]);
                }
            } else {
                unset($allRequests[$key]);
            }
        }
        sort($years);

        $task = new Task();
        foreach ($allRequests as $request) {
            $task->getRequests()->add($request);
        }

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($webRequest);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
        }

        return $this->render('admin/payments.html.twig', array(
             'task' => $task,
             'form' => $form->createView(),
             'years' => $years,
         ));
    }

    /**
     *@Route("/prices/edit", methods={"GET", "POST"}, name="prices_edit")
     */
    public function prices(Request $request, PriceRepository $prices, UserRepository $userRepository): Response {
        $allPrices = $prices->findAll();
        $prices = new Prices();
        foreach ($allPrices as $value) {
            $prices->addPrice($value);
        }
        $form = $this->createForm(PricesType::class, $prices);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->flush();
            return $this->redirectToRoute('prices_edit');
        }

        $adminForm = $this->createFormBuilder()
            ->add('username', TextType::class)
            ->add('submit', SubmitType::class, array('attr' => array('class' => 'btn btn-primary')))
            ->getForm();
        $adminForm->handleRequest($request);

        if ($adminForm->isSubmitted() && $adminForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $data = $adminForm->getData();
            $user = $userRepository->findOneBy(array('username' => $data['username']));
            if (is_null($user)) {
                $this->addFlash('error', 'admin.user_not_found');
            } else {
                $user->addRole('ROLE_ADMIN');
                $em->flush();
                $this->addFlash('success', 'admin.admin_added');
            }
            return $this->redirectToRoute('prices_edit');
        }

        return $this->render('admin/prices.html.twig', array(
             'form' => $form->createView(),
             'adminForm' => $adminForm->createView(),
         ));
    }
}
