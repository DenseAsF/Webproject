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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use App\Service\EmailVerificationService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
  
    public function __construct(
        private EmailVerificationService $verificationService,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $user = new User();
        $user->setUsername($data['username'] ?? '');
        $user->setEmail($data['email'] ?? '');
        $user->setPhone($data['phone'] ?? '');
        $user->setName($data['name'] ?? '');
        $user->setAge($data['age'] ?? 0);
        $user->setPlainPassword($data['password'] ?? '');

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        $existingUser = $userRepository->findOneBy(['username' => $user->getUsername()]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Username already exists'], 400);
        }

        $existingEmail = $userRepository->findOneBy(['email' => $user->getEmail()]);
        if ($existingEmail) {
            return new JsonResponse(['error' => 'Email already registered'], 400);
        }

        $user->setAccountNumber($this->generateUniqueAccountNumber($userRepository));
        
        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $data['password']
            )
        );

        $user->setRoles(['ROLE_USER']);
        $user->setEnabled(true);
        // ADD THIS — explicitly mark as not verified:
        $user->setIsVerified(false);
        $user->setCreatedAt(new \DateTime());

        $points = new Points();
        $points->setTotalPoints(0);
        $points->setUser($user);

        $entityManager->persist($user);
        $entityManager->persist($points);
        $entityManager->flush();

        // ADD THIS BLOCK — send verification email:
        try {
            $verifyUrl = $this->verificationService->generateVerifyUrl(
                $user,
                'app_verify_email',
                ['id' => $user->getId()]
            );
            $this->verificationService->sendVerificationEmail($user, $verifyUrl);
        } catch (\Exception $e) {
            // Don't fail registration if email sending fails
        }

        return new JsonResponse([
            'message' => 'User registered successfully. Please check your email to verify your account.',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'accountNumber' => $user->getAccountNumber(),
                'isVerified' => $user->isVerified()
            ]
        ], 201);
    }

    private function generateUniqueAccountNumber(UserRepository $repository): string
    {
        do {
            $accountNumber = 'ACC' . mt_rand(100000, 999999);
        } while ($repository->findOneBy(['accountNumber' => $accountNumber]));

        return $accountNumber;
    }
}