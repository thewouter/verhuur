<?php
namespace App\EventListener;

use App\Entity\Comment;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use App\Entity\LeaseRequest;

class RequestDeniedListener {

    public function preUpdate(LifecycleEventArgs $args) {
        $entity = $args->getObject();
        // only act on some "LeaseRequest" entity
        if (!$entity instanceof LeaseRequest or !in_array('status', array_keys($args->getEntityChangeSet()))) {
              return;
        }

        if (false and !in_array([5, 6, 7], $args->getEntityChangeSet()['status'][1]) ){
            return;
        }

        $entityManager = $args->getObjectManager();
        $repository = $entityManager->getRepository('App:LeaseRequest');
        $requestsToUpdate = $repository->findOccupiedByDateRange($entity->getStartDate(), $entity->getEndDate());

        $remove = false;
        foreach ($requestsToUpdate as $key => $other) {
            if ($other->getId() == $entity->getId()) {
                $remove = $key;
            }
        }
        if ($remove) {
            unset($requestsToUpdate[$remove]); // remove itself
        }

        foreach ($requestsToUpdate as $request) {
            $remove = false;
            $others = $repository->findInDateRange($request->getStartDate(), $request->getEndDate(), $visible=true);
            foreach ($others as $key => $other) {
                if ($other->getId() == $request->getId()) {
                    $remove = $key;
                }
            }
            if ($remove) { // remove itself
                unset($others[$remove]);
            }
            if (count($others) == 1 ) { // Only one other request on that date
                if ($request->getStatus() == 7) { // other request was occupied
                    $request->setStatus(8);
                    $comment = new Comment();
                    $comment->setAuthor($request->getAuthor());
                    $comment->setContent("Datum weer beschikbaar gekomen");
                    $comment->setPublishedAt(new \DateTime());
                    $request->addComment($comment);
                    $entityManager->flush();
                }
            }
        }
    }
}
