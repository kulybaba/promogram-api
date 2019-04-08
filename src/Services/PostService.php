<?php

namespace App\Services;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PostService extends AbstractController
{
    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    /**
     * PostService constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getPostsByUsers($users)
    {
        $posts = [];

        foreach ($users as $user) {
            foreach ($this->em->getRepository(Post::class)->findBy(['author' => $user]) as $post) {
                $posts[] = $post;
            }
        }

        return $posts;
    }
}
