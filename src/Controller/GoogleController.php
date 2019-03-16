<?php

namespace App\Controller;

use App\Aws\S3Manager;
use App\Entity\User;
use App\Services\LoginService;
use App\Services\UserService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry, UserService $userService, LoginService $loginService, S3Manager $s3Manager)
    {
        /** @var \KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient $client */
        $client = $clientRegistry->getClient('google');

        try {
            /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
            $googleUser = $client->fetchUser();

            if ($user = $this->getUser()) {
                $user->setGoogleId($googleUser->getId());

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                return $this->json($user);
            }

            if ($user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $googleUser->getEmail()])) {
                if ($user->getPictureKey()) {
                    $s3Manager->deletePicture($user->getPictureKey());
                    $user->setPictureKey(null);
                }

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

    /**
     * @Route("/disconnect/google", methods={"PUT"})
     */
    public function disconnectAction()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        if (!$user->getGoogleId()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Google account is not connected.');
        }

        if (!$user->getInstagramId()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Forbidden to disconnect from only one connected social network.');
        }

        $user->setGoogleId(null);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Disconnected from Google account'
        ]);
    }
}
