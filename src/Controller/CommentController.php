<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Exception\JsonHttpException;
use App\Security\Voter\CommentVoter;
use App\Security\Voter\PostVoter;
use App\Services\ValidateService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api")
 */
class CommentController extends AbstractController
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
     * @Route("/comments/", methods={"POST"})
     */
    public function addCommentAction(Request $request)
    {
        /* @var Comment $comment */
        $comment = new Comment();

        $this->denyAccessUnlessGranted(CommentVoter::COMMENT_ADD, $comment);

        $postId = $request->query->has('post_id') ?: 0;
        if (!$postId) {
            throw new JsonHttpException(400, JsonHttpException::REQUEST_ERROR);
        }
        $post = $this->getDoctrine()->getRepository(Post::class)->findOneBy(['id' => $postId]);

        $comment = $this->serializer->deserialize($request->getContent(), $comment, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE]);
        $comment->setAuthor($this->getUser())
            ->setPost($post);
        $this->validateService->validate($comment);

        $this->getDoctrine()->getManager()->persist($comment);
        $this->getDoctrine()->getManager()->flush();

        return $this->json($comment);
    }

    /**
     * @Route("/comments/{id}", methods={"GET"})
     */
    public function showCommentByIdAction(Comment $comment)
    {
        $this->denyAccessUnlessGranted(CommentVoter::COMMENT_VIEW, $comment);

        return $this->json($comment);
    }

    /**
     * @Route("/posts/{post}/comments/", methods={"GET"})
     */
    public function showCommentByPostAction(Request $request, Post $post, PaginatorInterface $paginator)
    {
        $this->denyAccessUnlessGranted(CommentVoter::COMMENT_VIEW);

        $startIdParam = $request->query->has('start_id') ?: 0;
        $startId = $startIdParam > 0 ?: 1;
        $commentsByPageParam = $request->query->has('comments_by_page') ?: 0;
        $commentsByPage = $commentsByPageParam > 0 ?: 10;

        $commentsRepo = $this->getDoctrine()->getRepository(Comment::class);
        $comments = $commentsRepo->selectByPostId($post->getId(), $startId);

        return $this->json($paginator->paginate($comments, 1, $commentsByPage));
    }
}