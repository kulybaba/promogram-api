<?php

namespace App\Controller;

use App\Services\PostService;
use App\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class FeedController extends AbstractController
{
    /**
     * @var PostService $feedService
     */
    private $postService;

    /**
     * @var UserService $userService
     */
    private $userService;

    /**
     * FeedController constructor.
     * @param PostService $postService
     * @param UserService $userService
     */
    public function __construct(PostService $postService, UserService $userService)
    {
        $this->postService = $postService;
        $this->userService = $userService;
    }

    /**
     * @Route("/feed", methods={"GET"})
     */
    public function listAction()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->json([
            'posts' => $this->postService->getPostsByUsers($this->getUser()->getFollowing())
        ]);
    }

    /**
     * @Route("/feed/retailers", methods={"GET"})
     */
    public function listByRetailersAction()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->json([
            'posts' => $this->postService->getPostsByUsers($this->userService->filterUsersOnRoleRetailer($this->getUser()->getFollowing()))
        ]);
    }

    /**
     * @Route("/feed/users", methods={"GET"})
     */
    public function listByUsersAction()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->json([
            'posts' => $this->postService->getPostsByUsers($this->userService->filterUsersOnRoleUser($this->getUser()->getFollowing()))
        ]);
    }
}
