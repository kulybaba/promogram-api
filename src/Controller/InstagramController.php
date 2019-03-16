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
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry, UserService $userService, LoginService $loginService, S3Manager $s3Manager)
    {
        /** @var \KnpU\OAuth2ClientBundle\Client\Provider\InstagramClient $client */
        $client = $clientRegistry->getClient('instagram');

        try {
            /** @var \League\OAuth2\Client\Provider\InstagramResourceOwner $instagramUser */
            $instagramUser = $client->fetchUser();

            if ($user = $this->getUser()) {
                $user->setInstagramId($instagramUser->getId());

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                return $this->json($user);
            }

            if ($user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['instagramId' => $instagramUser->getId()])) {
                if ($user->getPictureKey()) {
                    $s3Manager->deletePicture($user->getPictureKey());
                    $user->setPictureKey(null);
                }

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

    /**
     * @Route("/disconnect/instagram", methods={"PUT"})
     */
    public function disconnectAction()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        if (!$user->getInstagramId()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Instagram account is not connected.');
        }

        if (!$user->getGoogleId()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Forbidden to disconnect from only one connected social network.');
        }

        $user->setInstagramId(null);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Disconnected from Instagram account'
        ]);
    }
}
