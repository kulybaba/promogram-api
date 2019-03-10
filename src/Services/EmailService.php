<?php

namespace App\Services;

use App\Entity\User;

class EmailService
{
    private $mailer;

    public function __construct(\Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendRegistrationEmail(User $user)
    {
        $message = (new \Swift_Message('Registration email'))
            ->setFrom('ahurtep@gmai.com')
            ->setTo($user->getEmail())
            ->setBody('Congratulations! ' . $user->getFirstName() . ' ' . $user->getLastName() . ', you are successfully registered. ' . 'Email: ' . $user->getEmail() . '. Password: ' . $user->getPlainPassword() . '.');
        $this->mailer->send($message);
    }
}
