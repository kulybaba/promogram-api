<?php

namespace App\Listener;

use App\Aws\S3Manager;
use App\Entity\Post;
use Doctrine\ORM\Mapping as ORM;

class PostListener
{
    /**
     * @var S3Manager $s3Manager
     */
    private $s3Manager;

    /**
     * PostListener constructor.
     * @param S3Manager $s3Manager
     */
    public function __construct(S3Manager $s3Manager)
    {
        $this->s3Manager = $s3Manager;
    }

    /** @ORM\PrePersist() */
    public function prePersisHandler(Post $post)
    {
        if(!$post->getType()) {
            $post->setType(Post::CUSTOMER_TYPE);
        }

        if ($post->getPictureContent()) {
            $this->s3Manager->deletePicture($post->getPictureKey());

            $pictureParamsArray = $this->s3Manager->uploadPicture($post->getPictureContent());
            $post->setPicture($pictureParamsArray['picture']);
            $post->setPictureKey($pictureParamsArray['key']);
        }
    }
}