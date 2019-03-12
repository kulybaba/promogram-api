<?php

namespace App\Security\Voter;

use App\Entity\Post;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PostVoter extends Voter
{
    const POST_ADD = 'POST_ADD';

    const POST_VIEW = 'POST_VIEW';

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::POST_ADD, self::POST_VIEW])
            && $subject instanceof Post;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::POST_ADD:
                return true;
            case self::POST_VIEW:
                return true;
        }

        return false;
    }
}
