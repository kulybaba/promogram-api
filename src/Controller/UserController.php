<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\User;
use App\Services\EmailService;
use App\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api")
 */
class UserController extends AbstractController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var EmailService
     */
    private $emailService;

    /**
     * UserController constructor.
     *
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param UserService $userService
     * @param EmailService $emailService
     */
    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, UserService $userService, EmailService $emailService)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->userService = $userService;
        $this->emailService = $emailService;
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
}
