<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class FollowVoter extends Voter
{
    const FOLLOW = 'follow';

    const UNFOLLOW = 'unfollow';

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::FOLLOW, self::UNFOLLOW]) && $subject instanceof User;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::FOLLOW:
                return $this->canFollow($subject, $user);
            case self::UNFOLLOW:
                return $this->canUnfollow($subject, $user);
        }

        return false;
    }

    public function canFollow(User $subject, User $user)
    {
        return $subject !== $user;
    }

    public function canUnfollow(User $subject, User $user)
    {
        return $subject !== $user;
    }
}
