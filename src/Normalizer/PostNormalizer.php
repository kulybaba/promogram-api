<?php

namespace App\Normalizer;

use App\Entity\Post;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PostNormalizer implements NormalizerInterface
{
    const DETAILED_GROUP = 'Detailed group';

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

        if (isset($context[AbstractNormalizer::GROUPS]) && in_array($this::GROUP_DETAILS, $context[AbstractNormalizer::GROUPS])) {
            $data['text'] = $post->getText();
            $data['user'] = $post->getUser();
            if ($post->getPicture()) {
                $data['picture'] = $post->getPicture();
            }
            $data['type'] = $post->getType();
            $data['createdAt'] = $post->getCreatedAt();
        }

        return $data;
    }

    public function supportsNormalization($post, $format = null)
    {
        return $post instanceof Post;
    }
}