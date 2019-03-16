<?php

namespace App\EventSubscriber;

use App\Aws\S3Manager;
use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class ProfilePictureSubscriber implements EventSubscriber
{
    private $s3Manager;

    public function __construct(S3Manager $s3Manager)
    {
        $this->s3Manager = $s3Manager;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::preUpdate
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        /** @var User $user */
        $user = $args->getObject();

        if ($user instanceof User && $user->getContent()) {
            if ($user->getPicture() && $user->getPicture() != '/images/profile/default_picture.png') {
                $this->s3Manager->deletePicture($user->getPicture());
            }
            $this->s3Manager->uploadPicture($user);
        }
    }
}
