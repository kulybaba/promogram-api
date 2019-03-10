<?php

namespace App\Controller;

use App\Entity\User;
use App\Services\LoginService;
use App\Services\UserService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class InstagramController extends AbstractController
{
    /**
     * @Route("/connect/instagram", name="connect_instagram_start")
     */
    public function connectAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('instagram')
            ->redirect(['basic']);
    }

    /**
     * @Route("/connect/instagram/check", name="connect_instagram_check")
     */
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry, UserService $userService, LoginService $loginService)
    {
        /** @var \KnpU\OAuth2ClientBundle\Client\Provider\InstagramClient $client */
        $client = $clientRegistry->getClient('instagram');

        try {
            /** @var \League\OAuth2\Client\Provider\InstagramResourceOwner $instagramUser */
            $instagramUser = $client->fetchUser();

            if ($user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['instagramId' => $instagramUser->getId()])) {
                $user->setPicture($instagramUser->getImageurl());
                $user->setApiToken($userService->generateApiToken());

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                return $this->json($user);
            }

            return $this->json($loginService->instagramLogin($instagramUser));

        } catch (IdentityProviderException $e) {
            return $this->json($e->getMessage());
        }
    }
}
