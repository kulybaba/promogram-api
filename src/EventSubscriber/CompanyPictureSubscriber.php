<?php


namespace App\EventSubscriber;

use App\Aws\S3Manager;
use App\Entity\Company;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class CompanyPictureSubscriber implements EventSubscriber
{
    /**
     * @var S3Manager $s3Manager
     */
    private $s3Manager;

    /**
     * ProfilePictureSubscriber constructor.
     * @param S3Manager $s3Manager
     */
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
        /** @var Company $company */
        $company = $args->getObject();

        if ($company instanceof Company && $company->getPictureContent()) {
            if ($company->getPictureKey()) {
                $this->s3Manager->deletePicture($company->getPictureKey());
            }

            $result = $this->s3Manager->uploadPicture($company->getPictureContent());

            $company->setPicture($result['pictureUrl']);
            $company->setPictureKey($result['key']);
        }
    }
}
