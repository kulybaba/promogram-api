<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function selectByPostId(int $postId, int $startId = 1): ?Query
    {
        return $this->createQueryBuilder('comment')
            ->join('comment.post', 'post')
            ->where("post.id >= $postId")
            ->andWhere("comment.id >= $startId")
            ->orderBy('comment.id', 'ASC')
            ->getQuery();
    }
}
