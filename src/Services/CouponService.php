<?php

namespace App\Services;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;

class CouponService
{
    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    /**
     * @var EmailService $emailService
     */
    private $emailService;

    /**
     * CouponService constructor.
     * @param EntityManagerInterface $em
     * @param EmailService $emailService
     */
    public function __construct(EntityManagerInterface $em, EmailService $emailService)
    {
        $this->em = $em;
        $this->emailService = $emailService;
    }

    public function checkCountLikes(Post $post)
    {
        $user = $post->getAuthor();

        foreach ($this->getCouponsByCompanies($post->getCompany()) as $coupon) {
            if ($post->getLikes()->count() === $coupon->getCountLikes() && !$this->userHasCoupon($user->getCoupons(), $coupon)) {
                $user->addCoupon($coupon);
                $this->em->persist($user);
                $this->em->flush();

                $this->emailService->sendCouponEmail($user, $coupon);
            }
        }
    }

    public function userHasCoupon($coupons, $userCoupon)
    {
        foreach ($coupons as $coupon) {
            if ($coupon === $userCoupon) {
                return true;
            }
        }

        return false;
    }

    public function getCouponsByCompanies($companies)
    {
        $coupons = [];

        foreach ($companies as $company) {
            foreach ($company->getCoupons() as $coupon) {
                $coupons[] = $coupon;
            }
        }

        return $coupons;
    }
}
