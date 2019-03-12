<?php

namespace App\Normalizer;

use App\Entity\Post;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PostNormalizer implements NormalizerInterface
{
    /**
     * @param Post $post
     * @param null $format
     * @param array $context
     * @return array|bool|float|int|string
     */
    public function normalize($post, $format = null, array $context = [])
    {
        $data = [
            "id" => $post->getId()
        ];

        return $data;
    }

    public function supportsNormalization($post, $format = null)
    {
        return $post instanceof Post;
    }
}