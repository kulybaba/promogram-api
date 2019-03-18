<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\User;
use App\Services\EmailService;
use App\Services\LoginService;
use App\Services\UserService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\FacebookClient;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use KnpU\OAuth2ClientBundle\Client\Provider\InstagramClient;
use League\OAuth2\Client\Provider\FacebookUser;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api")
 */
class UserController extends AbstractController
{
    /**
     * @var SerializerInterface $serializer
     */
    private $serializer;

    /**
     * @var ValidatorInterface $validator
     */
    private $validator;

    /**
     * @var UserService $userService
     */
    private $userService;

    /**
     * @var EmailService $emailService
     */
    private $emailService;

    /**
     * @var UserPasswordEncoderInterface $passwordEncoder
     */
    private $passwordEncoder;

    /**
     * @var LoginService $loginService
     */
    private $loginService;

    /**
     * UserController constructor.
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param UserService $userService
     * @param EmailService $emailService
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param LoginService $loginService
     */
    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, UserService $userService, EmailService $emailService, UserPasswordEncoderInterface $passwordEncoder, LoginService $loginService)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->userService = $userService;
        $this->emailService = $emailService;
        $this->passwordEncoder = $passwordEncoder;
        $this->loginService = $loginService;
    }

    /**
     * @Route("/registration", methods={"POST"})
     */
    public function registrationAction(Request $request)
    {
        if (!$request->getContent()) {
            throw new HttpException('400', 'Bad request');
        }

        $user = $this->serializer->deserialize($request->getContent(), User::class, JsonEncoder::FORMAT);
        $user->setPassword($this->userService->encodePassword($user));
        $user->setApiToken($this->userService->generateApiToken());
        $user->setApproved(false);

        $company = $this->serializer->deserialize($request->getContent(), Company::class, JsonEncoder::FORMAT);
        $company->setUser($user);

        if (count($this->validator->validate($company)) || count($this->validator->validate($user))) {
            throw new HttpException('400', 'Bad request');
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($company);
        $em->flush();

        $this->emailService->sendRegistrationEmail($user);

        return $this->json($user);
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

                $this->getDoctrine()->getManager()->flush();

                return $this->json($user);
            }
        }

        throw new HttpException('400', 'Bad request');
    }

    /**
     * @Route("/login/google", name="login_google")
     */
    public function loginGoogleAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile']);
    }

    /**
     * @Route("/login/google/check", name="login_google_check")
     */
    public function loginGoogleCheckAction(ClientRegistry $clientRegistry)
    {
        /** @var GoogleClient $client */
        $client = $clientRegistry->getClient('google');

        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUser();

        /** @var User $user */
        if ($user = $this->getUser()) {
            $user->setGoogleId($googleUser->getId());

            $this->getDoctrine()->getManager()->flush();

            return $this->json($user);
        }

        if ($user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $googleUser->getEmail()])) {
            if (!$user->getPictureKey()) {
                $user->setPicture($googleUser->getAvatar());
            }

            $user->setApiToken($this->userService->generateApiToken());
            $user->setGoogleId($googleUser->getId());

            $this->getDoctrine()->getManager()->flush();

            return $this->json($user);
        }

        return $this->json($this->loginService->googleLogin($googleUser));
    }

    /**
     * @Route("/disconnect/google", methods={"PUT"})
     */
    public function disconnectGoogleAction()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getGoogleId()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Google account is not connected');
        }

        if ($user->getRoles() == ["ROLE_USER"]) {
            if (!$user->getInstagramId() && !$user->getFacebookId()) {
                throw new HttpException(Response::HTTP_FORBIDDEN, 'Forbidden to disconnect from only one connected social network');
            }
        }

        $user->setGoogleId(null);

        $this->getDoctrine()->getManager()->flush();

        return $this->json([
            'success' => true,
            'message' => 'Disconnected from Google account'
        ]);
    }

    /**
     * @Route("/login/instagram", name="login_instagram")
     */
    public function loginInstagramAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('instagram')
            ->redirect(['basic']);
    }

    /**
     * @Route("/login/instagram/check", name="login_instagram_check")
     */
    public function loginInstagramCheckAction(ClientRegistry $clientRegistry)
    {
        /** @var InstagramClient $client */
        $client = $clientRegistry->getClient('instagram');

        /** @var InstagramResourceOwner $instagramUser */
        $instagramUser = $client->fetchUser();

        /** @var User $user */
        if ($user = $this->getUser()) {
            $user->setInstagramId($instagramUser->getId());

            $this->getDoctrine()->getManager()->flush();

            return $this->json($user);
        }

        if ($user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['instagramId' => $instagramUser->getId()])) {
            if (!$user->getPictureKey()) {
                $user->setPicture($instagramUser->getImageurl());
            }

            $user->setApiToken($this->userService->generateApiToken());
            $user->setInstagramId($instagramUser->getId());

            $this->getDoctrine()->getManager()->flush();

            return $this->json($user);
        }

        return $this->json($this->loginService->instagramLogin($instagramUser));
    }

    /**
     * @Route("/disconnect/instagram", methods={"PUT"})
     */
    public function disconnectInstagramAction()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getInstagramId()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Instagram account is not connected');
        }

        if ($user->getRoles() == ["ROLE_USER"]) {
            if (!$user->getGoogleId() && !$user->getFacebookId()) {
                throw new HttpException(Response::HTTP_FORBIDDEN, 'Forbidden to disconnect from only one connected social network');
            }
        }

        $user->setInstagramId(null);

        $this->getDoctrine()->getManager()->flush();

        return $this->json([
            'success' => true,
            'message' => 'Disconnected from Instagram account'
        ]);
    }

    /**
     * @Route("/login/facebook", name="login_facebook")
     */
    public function loginFacebookAction(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('facebook')
            ->redirect([
                'public_profile', 'email'
            ]);
    }

    /**
     * @Route("/login/facebook/check", name="login_facebook_check")
     */
    public function loginFacebookCheckAction(ClientRegistry $clientRegistry)
    {
        /** @var FacebookClient $client */
        $client = $clientRegistry->getClient('facebook');

        /** @var FacebookUser $facebookUser */
        $facebookUser = $client->fetchUser();

        /** @var User $user */
        if ($user = $this->getUser()) {
            $user->setFacebookId($facebookUser->getId());

            $this->getDoctrine()->getManager()->flush();

            return $this->json($user);
        }

        if ($user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $facebookUser->getEmail()])) {
            if (!$user->getPictureKey()) {
                $user->setPicture($facebookUser->getPictureUrl());
            }

            $user->setApiToken($this->userService->generateApiToken());
            $user->setFacebookId($facebookUser->getId());

            $this->getDoctrine()->getManager()->flush();

            return $this->json($user);
        }

        return $this->json($this->loginService->facebookLogin($facebookUser));
    }

    /**
     * @Route("/disconnect/facebook", methods={"PUT"})
     */
    public function disconnectFacebookAction()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->getFacebookId()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Facebook account is not connected');
        }

        if ($user->getRoles() == ["ROLE_USER"]) {
            if (!$user->getGoogleId() && !$user->getInstagramId()) {
                throw new HttpException(Response::HTTP_FORBIDDEN, 'Forbidden to disconnect from only one connected social network');
            }
        }

        $user->setFacebookId(null);

        $this->getDoctrine()->getManager()->flush();

        return $this->json([
            'success' => true,
            'message' => 'Disconnected from Facebook account'
        ]);
    }
}
