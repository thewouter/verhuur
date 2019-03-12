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
use App\Form\PostType;
use App\Repository\LeaseRequestRepository;
use App\Utils\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Form\LeaseRequestType;
use App\Form\LeaseRequestAdminType;
use App\Form\CommentType;
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
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class BlogController extends AbstractController
{
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
     * @Route("/", methods={"GET"}, name="admin_index")
     * @Route("/", methods={"GET"}, name="admin_post_index")
     */
    public function index(LeaseRequestRepository $posts): Response
    {
        $requests = $posts->findLatest();
        return $this->render('admin/blog/index.html.twig', ['posts' => $requests]);
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
    public function edit(Request $request, LeaseRequest $leaseRequest): Response {
        $form = $this->createForm(LeaseRequestAdminType::class, $leaseRequest, array('signed_uploaded' => !is_null($leaseRequest->getContractSigned())));

        $em = $this->getDoctrine()->getManager();
        $leaseRequest->setRead(true);
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
                    if ($file){
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
                        ['content' => $comment->getContent()]
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
    private function generateUniqueFileName()
    {
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
    public function delete(Request $request, LeaseRequest $post): Response
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_post_index');
        }

        // Delete the tags associated with this blog post. This is done automatically
        // by Doctrine, except for SQLite (the database used in this application)
        // because foreign key support is not enabled by default in SQLite
        $post->getTags()->clear();

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
     public function contractHtml(Request $request, LeaseRequest $leaseRequest): Response {
         return $this->render('pdf/contract.html.twig', array(
             'leaseRequest' => $leaseRequest, ));
     }

     /**
      *
      *
      *@Route("/{id}/contract.pdf", methods={"GET"}, name="admin_contract_pdf")
      */
      public function contractPdf(Request $request, LeaseRequest $leaseRequest): Response {

            // Configure Dompdf according to your needs
            $pdfOptions = new Options();
            $pdfOptions->set('defaultFont', 'Arial');
            $pdfOptions->set('enable_remote', true);

            // Instantiate Dompdf with our options
            $dompdf = new Dompdf($pdfOptions);

            // Retrieve the HTML generated in our twig file
            $html = $this->renderView('pdf/contract.html.twig', [
            'title' => "Contract Radix Enschede Verhuur",
            'leaseRequest' => $leaseRequest,
            ]);

            // Load HTML to Dompdf
            $dompdf->loadHtml($html);

            // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
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

            return $this->edit($request, $leaseRequest);
      }

      /**
       *
       *
       *@Route("/{id}/contract/send", methods={"GET"}, name="admin_contract_email")
       */
       public function sendContract(Request $request, LeaseRequest $leaseRequest): Response {
           if(is_null($leaseRequest->getContract())) {
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
           return $this->edit($request, $leaseRequest);
       }
}
