<?php

namespace App\Services;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\FacebookUser;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\InstagramResourceOwner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoginService extends AbstractController
{
    private $em;

    private $userService;

    public function __construct(EntityManagerInterface $em, UserService $userService)
    {
        $this->em = $em;
        $this->userService = $userService;
    }

    public function googleLogin(GoogleUser $googleUser)
    {
        $user = new User();
        $user->setFirstName($googleUser->getFirstName());
        $user->setLastName($googleUser->getLastName());
        $user->setPicture($googleUser->getAvatar());
        $user->setEmail($googleUser->getEmail());
        $user->setPlainPassword($this->userService->generatePassword());
        $user->setPassword($this->userService->encodePassword($user));
        $user->setRoles(['ROLE_USER']);
        $user->setApiToken($this->userService->generateApiToken());
        $user->setApproved(true);
        $user->setGoogleId($googleUser->getId());

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function instagramLogin(InstagramResourceOwner $instagramUser)
    {
        $user = new User();
        $segments = explode(' ', $instagramUser->getName());
        $user->setFirstName($segments[0]);
        $user->setLastName($segments[1]);
        $user->setPicture($instagramUser->getImageurl());
        $user->setAbout($instagramUser->getDescription());
        $user->setPlainPassword($this->userService->generatePassword());
        $user->setPassword($this->userService->encodePassword($user));
        $user->setRoles(['ROLE_USER']);
        $user->setApiToken($this->userService->generateApiToken());
        $user->setApproved(true);
        $user->setInstagramId($instagramUser->getId());

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function facebookLogin(FacebookUser $facebookUser)
    {
        $user = new User();
        $segments = explode(' ', $facebookUser->getName());
        $user->setFirstName($segments[0]);
        $user->setLastName($segments[1]);
        $user->setPicture($facebookUser->getPictureUrl());
        $user->setPlainPassword($this->userService->generatePassword());
        $user->setPassword($this->userService->encodePassword($user));
        $user->setRoles(['ROLE_USER']);
        $user->setApiToken($this->userService->generateApiToken());
        $user->setApproved(true);
        $user->setFacebookId($facebookUser->getId());

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
