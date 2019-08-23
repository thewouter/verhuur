<?php

declare(strict_types=1);

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;

class UserProvider implements OAuthAwareUserProviderInterface {
    private $entityManager;

    /**
     * UserProvider constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response) {
        $data = $response;
        dump($data);
        $username = $response->getUsername();
        $email = $response->getEmail() ? $response->getEmail() : $username;
        $service = $response->getResourceOwner()->getName();
        dump($service);
        dump($email);
        $user = $this->loadUserByUsername($email);
        //$user = $this->userManager->findUserBy(array($service.'Id' => $username));
        //when the user is registrating
        if (!$user) { // make new user if user was not found
            $user = new User();
            $user->setUsername(strtolower($username) . date("d-m-Y"));
            $user->setEmail($email);
            $user->setFullname($googleUser->getName());
            $user->setPassword('Useless');
            $user->setAddress('');
            $user->setPhone('');
            $this->em->persist($user);
            $this->em->flush();
        }
        //if user exists - go with the HWIOAuth way
        // $user = parent::loadUserByOAuthUserResponse($response);
        // $serviceName = $response->getResourceOwner()->getName();
        // $setter = 'set' . ucfirst($serviceName) . 'AccessToken';
        //update access token
        // $user->{$setter}($response->getAccessToken());
        return $user;
    }

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loadUserByUsername($email) {
        $repository = $this->entityManager->getRepository('App:User');
        return $repository->findOneBy(['email' => $email]);
    }

    /**
     * Refreshes the user.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @param UserInterface $user
     * @return UserInterface
     *
     */
    public function refreshUser(UserInterface $user) {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }
        return $user;
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class) {
        return $class === 'App\Entity\User';
    }
}
