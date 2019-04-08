<?php

namespace App\Controller;

use App\Entity\Likes;
use App\Entity\Post;
use App\Entity\User;
use App\Normalizer\PostNormalizer;
use App\Security\Voter\PostVoter;
use App\Services\CouponService;
use App\Services\ValidateService;
use Imagick;
use Symfony\Component\HttpFoundation\Response;
use App\Exception\JsonHttpException;
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
     * @var SerializerInterface $serializer
     */
    private $serializer;

    /**
     * @var ValidateService $validateService
     */
    private $validateService;

    /**
     * @var CouponService $couponService
     */
    private $couponService;

    /**
     * PostController constructor.
     * @param SerializerInterface $serializer
     * @param ValidateService $validateService
     * @param CouponService $couponService
     */
    public function __construct(SerializerInterface $serializer, ValidateService $validateService, CouponService $couponService)
    {
        $this->serializer = $serializer;
        $this->validateService = $validateService;
        $this->couponService = $couponService;
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
        $post->setAuthor($this->getUser());
        $this->validateService->validate($post);

        $this->getDoctrine()->getManager()->persist($post);
        $this->getDoctrine()->getManager()->flush();

        return $this->json($post);
    }

    /**
     * @Route("/user/{id}/posts", methods={"GET"})
     */
    public function showPostsByUserAction(User $user)
    {
        /** @var Post $post */
        $posts = $this->getDoctrine()->getRepository(Post::class)->findBy(['author' => $user]);

        $this->denyAccessUnlessGranted(PostVoter::POST_VIEW);

        return $this->json($posts,200,[]);
    }

    /**
     * @Route("/posts/{id}", methods={"PUT"})
     */
    public function updatePictureAction(Request $request, Post $post)
    {
        $this->denyAccessUnlessGranted(PostVoter::POST_EDIT, $post);

        if (!$request->getContent()) {
            throw new JsonHttpException(Response::HTTP_BAD_REQUEST, 'Bad request');
        }

        $picture = new Imagick();

        if (!$picture->readImageBlob($request->getContent())) {
            throw new JsonHttpException(Response::HTTP_BAD_REQUEST, 'Bad request');
        }

        $post->setPictureContent($request->getContent());

        $this->getDoctrine()->getManager()->flush();

        return $this->json([
            'success' => true,
            'message' => 'Profile picture has been changed successfully.'
        ]);
    }

    /**
     * @Route("/posts/{id}/like", requirements={"id"="\d+"}, methods={"POST"})
     */
    public function likeAction(Post $post)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        if ($this->getDoctrine()->getRepository(Likes::class)->findOneBy(['post' => $post->getId(), 'user' => $user->getId()])) {
            throw new JsonHttpException(Response::HTTP_FORBIDDEN, 'You are already liked this post');
        }

        $like = new Likes();
        $like->setPost($post);
        $like->setUser($user);

        $this->getDoctrine()->getManager()->persist($like);
        $this->getDoctrine()->getManager()->flush();

        $this->couponService->checkCountLikes($post);

        return $this->json([
            'success' => true,
            'message' => 'Post is liked'
        ]);
    }

    /**
     * @Route("/posts/{id}/unlike", requirements={"id"="\d+"}, methods={"DELETE"})
     */
    public function unlikeAction(Post $post)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        $like = $this->getDoctrine()->getRepository(Likes::class)->findOneBy(['post' => $post->getId(), 'user' => $user->getId()]);

        if (!$like) {
            throw new JsonHttpException(Response::HTTP_FORBIDDEN, "You don't liked this post");
        }

        $this->getDoctrine()->getManager()->remove($like);
        $this->getDoctrine()->getManager()->flush();

        return $this->json([
            'success' => true,
            'message' => 'Post is unliked'
        ]);
    }
}
