<?php

namespace App\Normalizer;

use App\Entity\Coupon;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CouponNormalizer implements NormalizerInterface
{
    public function normalize($coupon, $format = null, array $context = [])
    {
        if (!isset($context['normalization'])) {
            return $coupon;
        }

        if ($context['normalization'] === 'coupon') {
            return [
                'id' => $coupon->getId(),
                'name' => $coupon->getName(),
                'code' => $coupon->getCode(),
                'expire' => $coupon->getExpire(),
                'companyId' => $coupon->getCompany()->getId(),
                'ratailerId' => $coupon->getRetailer()->getId(),
                'createdAt' => $coupon->getCreatedAt(),
                'updatedAt' => $coupon->getUpdatedAt()
            ];
        }
    }

    public function supportsNormalization($coupon, $format = null)
    {
        return $coupon instanceof Coupon;
    }
}
