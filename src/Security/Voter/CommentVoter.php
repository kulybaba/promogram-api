<?php

namespace App\Security\Voter;

use App\Entity\Comment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class CommentVoter extends Voter
{
    const COMMENT_ADD = 'COMMENT_ADD';

    const COMMENT_VIEW = 'COMMENT_VIEW';

    const COMMENT_DELETE = 'COMMENT_DELETE';

    const COMMENT_EDIT = 'COMMENT_EDIT';

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::COMMENT_ADD, self::COMMENT_VIEW])
            && $subject instanceof Comment;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::COMMENT_ADD:
                return true;
            case self::COMMENT_VIEW:
                return true;
            case self::COMMENT_DELETE:
                return true;
            case self::COMMENT_EDIT:
                return true;
        }

        return false;
    }
}
