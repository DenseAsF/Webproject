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

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserAuthenticatorInterface $userAuthenticator,
        CustomAuthenticator $authenticator
    ): Response {
        // Redirect if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('user_profile');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validate unique fields manually for better error messages
            $hasErrors = false;
            
            // Check username uniqueness
            $existingUser = $userRepository->findOneBy(['username' => $user->getUsername()]);
            if ($existingUser) {
                $form->get('username')->addError(new \Symfony\Component\Form\FormError('Username already exists'));
                $hasErrors = true;
            }

            // Check email uniqueness
            $existingEmail = $userRepository->findOneBy(['email' => $user->getEmail()]);
            if ($existingEmail) {
                $form->get('email')->addError(new \Symfony\Component\Form\FormError('Email already registered'));
                $hasErrors = true;
            }

            if (!$hasErrors && $form->isValid()) {
                // Generate unique account number
                $user->setAccountNumber($this->generateUniqueAccountNumber($userRepository));
                
                // Hash the password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                // Set USER role only
                $user->setRoles(['ROLE_USER']);
                $user->setEnabled(true);

                // Create associated points entity
                $points = new Points();
                $points->setTotalPoints(0);
                $points->setUser($user);

                // Persist to database
                $entityManager->persist($user);
                $entityManager->persist($points);
                $entityManager->flush();

                // Auto-login the user
                $userAuthenticator->authenticateUser(
                    $user,
                    $authenticator,
                    $request
                );

                // Redirect to profile page
                return new RedirectResponse($this->generateUrl('user_profile'));
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
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
