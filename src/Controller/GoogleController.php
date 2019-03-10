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
class GoogleController extends AbstractController
{
    /**
     * @Route("/connect/google", name="connect_google_start")
     */
    public function connectAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile']);
    }

    /**
     * @Route("/connect/google/check", name="connect_google_check")
     */
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry, UserService $userService, LoginService $loginService)
    {
        /** @var \KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient $client */
        $client = $clientRegistry->getClient('google');

        try {
            /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
            $googleUser = $client->fetchUser();

            if ($user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $googleUser->getEmail()])) {
                $user->setPicture($googleUser->getAvatar());
                $user->setApiToken($userService->generateApiToken());
                $user->setGoogleId($googleUser->getId());

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                return $this->json($user);
            }

            return $this->json($loginService->googleLogin($googleUser));

        } catch (IdentityProviderException $e) {
            return $this->json($e->getMessage());
        }
    }
}
