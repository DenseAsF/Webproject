<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerificationService
{
    public function __construct(
                private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer
    ) {}

    public function sendVerificationEmail(User $user, string $verifyUrl): void
    {
        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Please verify your email address')
            ->htmlTemplate('emails/confirmation.html.twig')
            ->context(['verifyUrl' => $verifyUrl, 'user' => $user]);

        $this->mailer->send($email);
    }

    public function generateVerifyUrl(User $user, string $routeName, array $routeParams): string
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $routeName,
            (string) $user->getId(),
            $user->getEmail(),
            $routeParams
        );
        return $signatureComponents->getSignedUrl();
    }
}

