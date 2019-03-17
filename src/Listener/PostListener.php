<?php

namespace App\Listener;

use App\Entity\Post;
use Doctrine\ORM\Mapping as ORM;

class PostListener
{
    /** @ORM\PrePersist() */
    public function prePersisHandler(Post $post)
    {
        if(!$post->getType()) {
            $post->setType(Post::CUSTOMER_TYPE);
        }
    }
}