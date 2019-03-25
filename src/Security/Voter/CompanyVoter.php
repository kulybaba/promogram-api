<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class CompanyVoter extends Voter
{
    const CREATE = 'company_create';

    const UPDATE = 'company_update';

    const DELETE = 'company_delete';

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::CREATE, self::UPDATE, self::DELETE])  && $subject instanceof User;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate($subject, $user);
            case self::UPDATE:
                return $this->canUpdate($subject, $user);
            case self::DELETE:
                return $this->canDelete($subject, $user);
        }

        return false;
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
