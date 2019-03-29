<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class CouponVoter extends Voter
{
    const VIEW = 'coupon_view';

    const CREATE = 'coupon_create';

    const UPDATE = 'coupon_update';

    const DELETE = 'coupon_delete';

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::VIEW, self::CREATE, self::UPDATE, self::DELETE]) && $subject instanceof User;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($subject, $user);
            case self::CREATE:
                return $this->canCreate($subject, $user);
            case self::UPDATE:
                return $this->canUpdate($subject, $user);
            case self::DELETE:
                return $this->canDelete($subject, $user);
        }

        return false;
    }

    public function canView(User $subject, User $user)
    {
        return $subject === $user;
    }

    public function canCreate(User $subject, User $user)
    {
        return $subject === $user;
    }

    public function canUpdate(User $subject, User $user)
    {
        return $subject === $user;
    }

    public function canDelete(User $subject, User $user)
    {
        return $subject === $user;
    }
}
