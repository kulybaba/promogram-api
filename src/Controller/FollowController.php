<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class FollowController extends AbstractController
{
    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    /**
     * FollowController constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/profile/{id}/follow", requirements={"id"="\d+"}, methods={"POST"})
     */
    public function followAction(User $followingUser)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('follow', $followingUser);

        /** @var User $user */
        $user = $this->getUser();

        if ($this->em->getRepository(User::class)->findFollow($user->getId(), $followingUser->getId())) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'You are already followed');
        }

        $user->addFollowing($followingUser);

        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'You are successful followed',
        ]);
    }

    /**
     * @Route("/profile/{id}/unfollow", requirements={"id"="\d+"}, methods={"DELETE"})
     */
    public function unfollowAction(User $unfollowingUser)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->denyAccessUnlessGranted('unfollow', $unfollowingUser);

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->em->getRepository(User::class)->findFollow($user->getId(), $unfollowingUser->getId())) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "You don't follow on this user");
        }

        $user->removeFollowing($unfollowingUser);

        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'You are successful unfollowed',
        ]);
    }
}
