<?php

namespace App\Aws;

use Exception;
use App\Services\PictureService;
use Aws\S3\S3Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class S3Manager extends AbstractController
{
    /**
     * @var S3Client $s3
     */
    private $s3;

    /**
     * @var string $bucket
     */
    private $bucket;

    /**
     * @var string $prefix
     */
    private $prefix;

    /**
     * @var PictureService $pictureService
     */
    private $pictureService;

    /**
     * S3Manager constructor.
     * @param S3Client $s3
     * @param $bucket
     * @param $prefix
     * @param PictureService $pictureService
     */
    public function __construct(S3Client $s3, $bucket, $prefix, PictureService $pictureService)
    {
        $this->s3 = $s3;
        $this->bucket = $bucket;
        $this->prefix = $prefix;
        $this->pictureService = $pictureService;
    }

    public function uploadPicture(string $picture)
    {
        try {
            $key = $this->prefix . '/' .  md5(uniqid()) . '.' . $this->pictureService->getPictureExtensionFromBinary($picture);

            $result = $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $picture,
                'ACL' => 'public-read-write',
                'ContentType' => $this->pictureService->getPictureExtensionFromBinary($picture),
            ]);

            return [
                'pictureUrl' => $result->get('ObjectURL'),
                'key' => $key
            ];
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function deletePicture(string $pictureKey)
    {
        if (!$pictureKey) {
            return $this->json([
                'success' => false,
                'code' => 400,
                'message' => 'Picture not found'
            ]);
        }

        try {
            $this->s3->deleteMatchingObjects($this->bucket, $pictureKey);
        } catch (Exception $e) {
            return $this->json([
                'success' => false,
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }
}
