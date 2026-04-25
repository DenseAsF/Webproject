<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Points;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\CustomAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Service\EmailVerificationService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class WebRegistrationController extends AbstractController
{
    public function __construct(
        private EmailVerificationService $verificationService,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserAuthenticatorInterface $userAuthenticator,
        CustomAuthenticator $authenticator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('user_profile');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $hasErrors = false;
            
            $existingUser = $userRepository->findOneBy(['username' => $user->getUsername()]);
            if ($existingUser) {
                $form->get('username')->addError(new \Symfony\Component\Form\FormError('Username already exists'));
                $hasErrors = true;
            }

            $existingEmail = $userRepository->findOneBy(['email' => $user->getEmail()]);
            if ($existingEmail) {
                $form->get('email')->addError(new \Symfony\Component\Form\FormError('Email already registered'));
                $hasErrors = true;
            }

            if (!$hasErrors && $form->isValid()) {
                $user->setAccountNumber($this->generateUniqueAccountNumber($userRepository));
                
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $user->setRoles(['ROLE_USER']);
                $user->setEnabled(true);
                $user->setIsVerified(false);

                $points = new Points();
                $points->setTotalPoints(0);
                $points->setUser($user);

                $entityManager->persist($user);
                $entityManager->persist($points);
                $entityManager->flush();

                // Send verification email
                try {
                    $verifyUrl = $this->verificationService->generateVerifyUrl(
                        $user,
                        'app_verify_email',
                        ['id' => $user->getId()]
                    );
                    $this->verificationService->sendVerificationEmail($user, $verifyUrl);
                } catch (\Exception $e) {
                    // Log error but don't fail registration
                    $this->addFlash('error', 'Registration successful but we couldn\'t send the verification email. Please contact support.');
                }

                return $this->render('registration/verification_needed.html.twig', [
                    'user_email' => $user->getEmail()
                ]);
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email/{id}', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        int $id,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        VerifyEmailHelperInterface $verifyEmailHelper
    ): Response {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        try {
            $verifyEmailHelper->validateEmailConfirmation(
                $request->getUri(),
                (string) $user->getId(),
                $user->getEmail()
            );
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', 'Verification link is invalid or has expired.');
            return $this->redirectToRoute('app_register');
        }

        $user->setIsVerified(true);
        $em->flush();

        $this->addFlash('success', 'Your email has been verified! You can now log in.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend-verification/{email}', name: 'app_resend_verification')]
    public function resendVerification(
        string $email,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $userRepository->findOneBy(['email' => $email]);
        
        if (!$user || $user->isVerified()) {
            $this->addFlash('error', 'Invalid request or user already verified.');
            return $this->redirectToRoute('app_login');
        }

        try {
            $verifyUrl = $this->verificationService->generateVerifyUrl(
                $user,
                'app_verify_email',
                ['id' => $user->getId()]
            );
            $this->verificationService->sendVerificationEmail($user, $verifyUrl);
            $this->addFlash('success', 'Verification email has been resent. Please check your inbox.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to resend verification email. Please try again later.');
        }

        return $this->render('registration/verification_needed.html.twig', [
            'user_email' => $user->getEmail()
        ]);
    }

    private function generateUniqueAccountNumber(UserRepository $repository): string
    {
        do {
            $accountNumber = 'ACC' . mt_rand(100000, 999999);
        } while ($repository->findOneBy(['accountNumber' => $accountNumber]));

        return $accountNumber;
    }
}