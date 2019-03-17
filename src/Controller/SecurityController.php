<?php

namespace App\Controller;

use App\Aws\S3Manager;
use App\Entity\User;
use App\Services\LoginService;
use App\Services\UserService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use KnpU\OAuth2ClientBundle\Client\Provider\InstagramClient;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\InstagramResourceOwner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api")
 */
class SecurityController extends AbstractController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * SecurityController constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->serializer = $serializer;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/login", methods={"POST"})
     */
    public function loginAction(Request $request)
    {
        if (!$request->getContent()) {
            throw new HttpException('400', 'Bad request');
        }

        $data = $this->serializer->deserialize($request->getContent(), User::class, JsonEncoder::FORMAT);

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $data->getEmail()]);

        if ($user instanceof User) {
            if ($this->passwordEncoder->isPasswordValid($user, $data->getPassword())) {
                $user->setApiToken($this->userService->generateApiToken());

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                return $this->json($user);
            }
        }

        throw new HttpException('400', 'Bad request');
    }

    /**
     * @Route("/login/google", name="login_google_connect")
     */
    public function connectGoogleAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile']);
    }

    /**
     * @Route("login/google/check", name="login_google_check")
     */
    public function connectGoogleCheckAction(ClientRegistry $clientRegistry, UserService $userService, LoginService $loginService, S3Manager $s3Manager)
    {
        /** @var GoogleClient $client */
        $client = $clientRegistry->getClient('google');

        /** @var GoogleUser $googleUser */
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
    }

    /**
     * @Route("/logout/google", methods={"PUT"})
     */
    public function disconnectGoogleAction()
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

    /**
     * @Route("/login/instagram", name="login_instagram_connect")
     */
    public function connectInstagramAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('instagram')
            ->redirect(['basic']);
    }

    /**
     * @Route("/login/instagram/check", name="login_instagram_check")
     */
    public function connectInstagramCheckAction(ClientRegistry $clientRegistry, UserService $userService, LoginService $loginService, S3Manager $s3Manager)
    {
        /** @var InstagramClient $client */
        $client = $clientRegistry->getClient('instagram');

        /** @var InstagramResourceOwner $instagramUser */
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
    }

    /**
     * @Route("/logout/instagram", methods={"PUT"})
     */
    public function disconnectInstagramAction()
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
