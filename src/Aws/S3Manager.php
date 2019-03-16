<?php

namespace App\Aws;

use App\Entity\User;
use App\Services\PictureService;
use Aws\S3\S3Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class S3Manager extends AbstractController
{
    private $s3;

    private $bucket;

    private $prefix;

    private $pictureService;

    public function __construct(S3Client $s3, $bucket, $prefix, PictureService $pictureService)
    {
        $this->s3 = $s3;
        $this->bucket = $bucket;
        $this->prefix = $prefix;
        $this->pictureService = $pictureService;
    }

    public function uploadPicture(User $user)
    {
        try {
            $result = $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key' => $this->prefix . '/' .  md5(uniqid()) . '.' . $this->pictureService->getPictureExtensionFromBinary($user->getContent()),
                'Body' => $user->getContent(),
                'ACL' => 'public-read-write',
                'ContentType' => $this->pictureService->getPictureExtensionFromBinary($user->getContent()),
            ]);

            $user->setPicture($result->get('ObjectURL'));
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function deletePicture(string $pictureUrl)
    {
        try {
            $this->s3->deleteMatchingObjects($this->bucket, $this->prefix . '/' . $this->pictureService->getPictureName($pictureUrl));
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }
}
