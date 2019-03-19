<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Exception\JsonHttpException;
use App\Security\Voter\CommentVoter;
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
        if (!$post) {
            throw new JsonHttpException(400, JsonHttpException::REQUEST_ERROR);
        }

        $comment = $this->serializer->deserialize($request->getContent(), $comment, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE]);
        $comment->setPost($post)
            ->setUser($this->getUser());
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
    public function showCommentByPostAction(Request $request, PaginatorInterface $paginator, Post $post)
    {
        $this->denyAccessUnlessGranted(CommentVoter::COMMENT_VIEW);

        $startId = $request->query->has('start_id') && ($request->query->get('start_id') > 0) ? $request->query->get('start_id') : 1;
        $commentsByPage = $request->query->has('comments_by_page') && ($request->query->get('comments_by_page') > 0) ? $request->query->get('comments_by_page') : 10;

        $commentsRepo = $this->getDoctrine()->getRepository(Comment::class);
        $comments = $commentsRepo->selectByPostId($post->getId(), $startId);

        return $this->json($paginator->paginate($comments, 1, $commentsByPage));
    }

    /**
     * @Route("/comments/{id}", methods={"DELETE"})
     */
    public function deleteCommentByIdAction(Comment $comment)
    {
        $this->denyAccessUnlessGranted(CommentVoter::COMMENT_DELETE, $comment);

        $this->getDoctrine()->getManager()->remove($comment);
        $this->getDoctrine()->getManager()->flush();

        return $this->json('ok');
    }

    /**
     * @Route("/comments/{id}", methods={"PUT"})
     */
    public function editCommentAction(Request $request, Comment $comment)
    {
        $this->denyAccessUnlessGranted(CommentVoter::COMMENT_EDIT, $comment);

        $comment = $this->serializer->deserialize($request->getContent(), $comment, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE]);
        $this->validateService->validate($comment);

        $this->getDoctrine()->getManager()->flush();

        return $this->json('ok');
    }
}