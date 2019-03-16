<?php

namespace App\Controller;

use App\Aws\S3Manager;
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

    private $s3Manager;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em, S3Manager $s3Manager)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->em = $em;
        $this->s3Manager = $s3Manager;
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
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Bad request');
        }

        $this->serializer->deserialize($request->getContent(), User::class, JsonEncoder::FORMAT, ['object_to_populate' => $user]);

        if (count($this->validator->validate($user, null, ['update_profile']))) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Bad request');
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user, Response::HTTP_OK, [], ['normalization' => 'profile']);
    }

    /**
     * @Route("/profile/{id}/change-picture", requirements={"id"="\d+"}, methods={"PUT"})
     */
    public function changePictureAction(Request $request, User $user)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('edit', $user);

        if (!$request->getContent()) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Bad request');
        }

        $picture = new \Imagick();

        if (!$picture->readImageBlob($request->getContent())) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Bad request');
        }

        $user->setContent($request->getContent());

        $this->em->getUnitOfWork()->scheduleForUpdate($user);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Profile picture changed'
        ]);
    }

    /**
     * @Route("/profile/{id}/delete-picture", requirements={"id"="\d+"}, methods={"PUT"})
     */
    public function deletePictureAction(User $user)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('edit', $user);

        if ($user->getPicture() == $this->getParameter('default_profile_picture')) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'No profile picture');
        }

        if ($user->getPictureKey()) {
            $this->s3Manager->deletePicture($user->getPictureKey());
            $user->setPictureKey(null);
        }

        $user->setPicture($this->getParameter('default_profile_picture'));

        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Profile picture deleted'
        ]);
    }
}
