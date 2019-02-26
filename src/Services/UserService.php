<?php

namespace App\Services;

use App\Entity\User;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserService
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function encodePassword(User $user)
    {
        return $this->passwordEncoder->encodePassword($user, $user->getPlainPassword());
    }

    public function generateApiToken()
    {
        return Uuid::uuid4()->toString();
    }
}
