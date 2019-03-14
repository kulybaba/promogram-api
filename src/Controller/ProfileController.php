<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api")
 */
class ProfileController extends AbstractController
{
    private $serializer;

    private $validator;

    private $em;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->em = $em;
    }

    /**
     * @Route("/profile/{id}/view", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function viewAction(User $user)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->json($user, Response::HTTP_OK, [], ['normalization' => 'profile']);
    }

    /**
     * @Route("/profile/{id}/update", requirements={"id"="\d+"}, methods={"PUT"})
     */
    public function updateAction(Request $request, User $user)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('edit', $user);

        if (!$request->getContent()) {
            throw new HttpException('400', 'Bad request');
        }

        $this->serializer->deserialize($request->getContent(), User::class, JsonEncoder::FORMAT, ['object_to_populate' => $user]);

        if (count($this->validator->validate($user, null, ['update_profile']))) {
            throw new HttpException('400', 'Bad request');
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user, Response::HTTP_OK, [], ['normalization' => 'profile']);
    }
}
