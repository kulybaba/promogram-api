<?php

namespace App\Controller;

use App\Entity\Post;
use App\Normalizer\PostNormalizer;
use App\Security\Voter\PostVoter;
use App\Services\ValidateService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api")
 */
class PostController extends AbstractController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ValidateService
     */
    private $validateService;

    public function __construct(SerializerInterface $serializer, ValidateService $validateService)
    {
        $this->serializer = $serializer;
        $this->validateService = $validateService;
    }

    /**
     * @Route("/posts/", methods={"GET"})
     */
    public function listPostsAction(Request $request, PaginatorInterface $paginator)
    {
        $this->denyAccessUnlessGranted(PostVoter::POST_VIEW);

        $startIdParam = $request->query->has('start_id') ?: 0;
        $startId = $startIdParam > 0 ?: 1;
        $postsByPageParam = $request->query->has('posts_by_page') ?: 0;
        $postsByPage = $postsByPageParam > 0 ?: 10;

        $posts = $this->getDoctrine()->getRepository(Post::class)->selectWhereIdLargerThan($startId);

        return $this->json($paginator->paginate($posts, 1, $postsByPage));
    }

    /**
     * @Route("/posts/{id}", methods={"GET"})
     */
    public function showPostByIdAction(Post $post)
    {
        $this->denyAccessUnlessGranted(PostVoter::POST_VIEW, $post);

        return $this->json($post,200,[],[AbstractNormalizer::GROUPS => [PostNormalizer::DETAILED_GROUP]]);
    }

    /**
     * @Route("/posts/", methods={"POST"})
     */
    public function addPostAction(Request $request)
    {
        /* @var Post $post */
        $post = new Post();

        $this->denyAccessUnlessGranted(PostVoter::POST_ADD, $post);

        $post = $this->serializer->deserialize($request->getContent(), $post, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE]);
        $post->setUser($this->getUser());
        $this->validateService->validate($post);

        $this->getDoctrine()->getManager()->persist($post);
        $this->getDoctrine()->getManager()->flush();

        return $this->json($post);
    }
}