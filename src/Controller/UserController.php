<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Points;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'user_index', methods: ['GET'])]
    public function index(UserRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search');
        $role = $request->query->get('role');

        $qb = $repo->createQueryBuilder('u');

        if ($search) {
            $qb->andWhere('u.username LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($role) {
            $qb->andWhere('u.roles LIKE :role')
               ->setParameter('role', '%"' . $role . '"%');
        }

        $qb->orderBy('u.createdAt', 'DESC');

        $users = $qb->getQuery()->getResult();

        return $this->render('user/index.html.twig', [
            'users' => $users
        ]);
    }
#[Route('/new', name: 'user_new')]
public function new(
    Request $request,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $hasher,
    UserRepository $userRepo,
    ActivityLogger $activityLogger
): Response {
    $user = new User();
    $user->setAccountNumber($this->generateUniqueAccountNumber($userRepo));

    $form = $this->createFormBuilder($user)
        ->add('username', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => false])
        ->add('name', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => false])
        ->add('email', \Symfony\Component\Form\Extension\Core\Type\EmailType::class, ['required' => false])
        ->add('phone', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => false,
            'attr' => [
                'maxlength' => 11,
                'minlength' => 11,
                'inputmode' => 'numeric',
                'pattern' => '\\d*',
            ],
        ])
        ->add('age', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, ['required' => false])
        ->add('roles', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'choices' => [
                'User' => 'ROLE_USER',
                'Staff' => 'ROLE_STAFF',
                'Admin' => 'ROLE_ADMIN'
            ],
            'multiple' => true,
            'required' => false,
        ])
        ->add('plainPassword', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class, [
            'mapped' => false,
            'required' => false,
        ])
        ->getForm();

    $form->handleRequest($request);


    if ($form->isSubmitted()) {

        $hasErrors = false;

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $username = trim($form->get('username')->getData() ?? '');
        $name     = trim($form->get('name')->getData() ?? '');
        $email    = trim($form->get('email')->getData() ?? '');
        $phone    = trim($form->get('phone')->getData() ?? '');
        $age      = $form->get('age')->getData();
        $roles    = $form->get('roles')->getData();
        $password = $form->get('plainPassword')->getData() ?? '';

     
        if ($username === '') {
            $form->get('username')->addError(new FormError('Username is required'));
            $hasErrors = true;
        } elseif (strlen($username) < 3) {
            $form->get('username')->addError(new FormError('Username must be at least 3 characters'));
            $hasErrors = true;
        } elseif ($userRepo->findOneBy(['username' => $username])) {
            $form->get('username')->addError(new FormError('Username already exists'));
            $hasErrors = true;
        }

        if ($name === '') {
            $form->get('name')->addError(new FormError('Name is required'));
            $hasErrors = true;
        }

   
        if ($email === '') {
            $form->get('email')->addError(new FormError('Email is required'));
            $hasErrors = true;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form->get('email')->addError(new FormError('Invalid email format'));
            $hasErrors = true;
        } elseif ($userRepo->findOneBy(['email' => $email])) {
            $form->get('email')->addError(new FormError('Email already registered'));
            $hasErrors = true;
        }

       
        if ($phone === '') {
            $form->get('phone')->addError(new FormError('Phone is required'));
            $hasErrors = true;
        } elseif (!preg_match('/^09\d{9}$/', $phone)) {
            $form->get('phone')->addError(new FormError('Phone must start with 09 and be 11 digits'));
            $hasErrors = true;
        }

     
        if ($age === null || $age === '') {
            $form->get('age')->addError(new FormError('Age is required'));
            $hasErrors = true;
        } elseif (!is_numeric($age)) {
            $form->get('age')->addError(new FormError('Age must be a number'));
            $hasErrors = true;
        } elseif ($age < 18 || $age > 120) {
            $form->get('age')->addError(new FormError('Age must be between 18 and 120'));
            $hasErrors = true;
        }
     
        // Roles validation: only admins choose roles.
        // For non-admins (e.g. staff), silently default new users to ROLE_USER.
        if ($isAdmin) {
            if (empty($roles)) {
                $form->get('roles')->addError(new FormError('Select at least one role'));
                $hasErrors = true;
            }
        } else {
            if (empty($roles)) {
                $roles = ['ROLE_USER'];
            }
        }

        if ($password === '') {
            $form->get('plainPassword')->addError(new FormError('Password is required'));
            $hasErrors = true;
        } elseif (strlen($password) < 6) {
            $form->get('plainPassword')->addError(new FormError('Password must be at least 6 characters'));
            $hasErrors = true;
        }

        if (!$hasErrors) {
            try {
                $user->setPassword($hasher->hashPassword($user, $password));
                // Only admins control roles; non-admins always create regular users.
                if (!$isAdmin) {
                    $roles = ['ROLE_USER'];
                }
                if (!is_array($roles)) {
                    $roles = [$roles];
                }
                $user->setRoles($roles);

                $user->setCreatedAt(new \DateTime());

                $points = new Points();
                $points->setTotalPoints(0);
                $points->setUser($user);

                $em->persist($user);
                $em->persist($points);
                $em->flush();

                // Log admin creates a user
                $activityLogger->log(
                    action: 'USER_CREATE',
                    entityType: 'User',
                    entityId: $user->getId(),
                    description: 'Admin created user ' . $user->getUsername()
                );

                // $this->addFlash('success', 'User created successfully!');

                if ($this->isGranted('ROLE_ADMIN')) {
                    return $this->redirectToRoute('user_index');
                }

                return $this->redirectToRoute('customer_index');

            } catch (\Exception $e) {
                $this->addFlash('error', 'Error: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Please fix the errors below.');
        }
    }

    return $this->render('user/new.html.twig', [
        'form' => $form->createView(),
        'accountNumber' => $user->getAccountNumber(),
    ]);
}


    private function generateUniqueAccountNumber(UserRepository $repo): string
    {
        do {
            $num = 'ACC' . mt_rand(100000,999999);
        } while ($repo->findOneBy(['accountNumber'=>$num]));

        return $num;
    }

    #[Route('/{id}', name: 'user_show', requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        // Prevent staff from viewing other users' profiles
        $currentUser = $this->getUser();
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN') && $currentUser->getId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to view other users\' profiles.');
            return $this->redirectToRoute('customer_index');
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

  #[Route('/{id}/edit', name: 'user_edit', requirements: ['id' => '\d+'])]
public function edit(
    User $user,
    Request $request,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $hasher,
    UserRepository $userRepo,
    ActivityLogger $activityLogger
): Response {
    // Prevent staff from editing other users' profiles
    $currentUser = $this->getUser();
    if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN') && $currentUser->getId() !== $user->getId()) {
        $this->addFlash('error', 'You do not have permission to edit other users\' profiles.');
        return $this->redirectToRoute('customer_index');
    }
    $originalHash = $user->getPassword();
    $originalUsername = $user->getUsername();
    $originalEmail = $user->getEmail();

    
    $form = $this->createFormBuilder($user)
        ->add('username', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => false])
        ->add('name', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => false])
        ->add('email', \Symfony\Component\Form\Extension\Core\Type\EmailType::class, ['required' => false])
        ->add('phone', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => false,
            'attr' => [
                'maxlength' => 11,
                'minlength' => 11,
                'inputmode' => 'numeric',
                'pattern' => '\\d*',
            ],
        ])
        ->add('age', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, ['required' => false])
        ->add('roles', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'choices' => [
                'User' => 'ROLE_USER',
                'Staff' => 'ROLE_STAFF',
                'Admin' => 'ROLE_ADMIN'
            ],
            'multiple' => true,
            'required' => false,
        ])
        ->add('plainPassword', \Symfony\Component\Form\Extension\Core\Type\PasswordType::class, [
            'mapped' => false,
            'required' => false,
        ])
        ->getForm();
    
    $form->handleRequest($request);
    
    if ($form->isSubmitted()) {
        $hasErrors = false;
        
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $username = trim($form->get('username')->getData() ?? '');
        $name = trim($form->get('name')->getData() ?? '');
        $email = trim($form->get('email')->getData() ?? '');
        $phone = trim($form->get('phone')->getData() ?? '');
        $age = $form->get('age')->getData();
        $roles = $form->get('roles')->getData();
        $password = $form->get('plainPassword')->getData() ?? '';
        
      
        if (empty($username)) {
            $form->get('username')->addError(new \Symfony\Component\Form\FormError('Username is required'));
            $hasErrors = true;
        } elseif (strlen($username) < 3) {
            $form->get('username')->addError(new \Symfony\Component\Form\FormError('Username must be at least 3 characters'));
            $hasErrors = true;
        } elseif ($username !== $originalUsername && $userRepo->findOneBy(['username' => $username])) {
            $form->get('username')->addError(new \Symfony\Component\Form\FormError('Username already exists'));
            $hasErrors = true;
        }
        
       
        if (empty($name)) {
            $form->get('name')->addError(new \Symfony\Component\Form\FormError('Name is required'));
            $hasErrors = true;
        }
        
      
        if (empty($email)) {
            $form->get('email')->addError(new \Symfony\Component\Form\FormError('Email is required'));
            $hasErrors = true;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form->get('email')->addError(new \Symfony\Component\Form\FormError('Invalid email format'));
            $hasErrors = true;
        } elseif ($email !== $originalEmail && $userRepo->findOneBy(['email' => $email])) {
            $form->get('email')->addError(new \Symfony\Component\Form\FormError('Email already registered'));
            $hasErrors = true;
        }
        
     
        if (empty($phone)) {
            $form->get('phone')->addError(new \Symfony\Component\Form\FormError('Phone is required'));
            $hasErrors = true;
        } elseif (!preg_match('/^09\d{9}$/', $phone)) {
            $form->get('phone')->addError(new \Symfony\Component\Form\FormError('Phone must start with 09 and be 11 digits'));
            $hasErrors = true;
        }
        
       
        if (empty($age)) {
            $form->get('age')->addError(new \Symfony\Component\Form\FormError('Age is required'));
            $hasErrors = true;
        } elseif (!is_numeric($age)) {
            $form->get('age')->addError(new \Symfony\Component\Form\FormError('Age must be a number'));
            $hasErrors = true;
        } elseif ($age < 18 || $age > 120) {
            $form->get('age')->addError(new \Symfony\Component\Form\FormError('Age must be between 18 and 120'));
            $hasErrors = true;
        }
        
        // Roles validation: only admins are required to select roles and can modify them.
        // For non-admins (e.g. staff), keep existing roles and do not validate this field.
        if ($isAdmin) {
            if (empty($roles)) {
                $form->get('roles')->addError(new \Symfony\Component\Form\FormError('Select at least one role'));
                $hasErrors = true;
            }
        }
        
       
        if (!empty($password) && strlen($password) < 6) {
            $form->get('plainPassword')->addError(new \Symfony\Component\Form\FormError('Password must be at least 6 characters'));
            $hasErrors = true;
        }
        
       
        if (!$hasErrors) {
            try {
               
                if (!empty($password)) {
                    $user->setPassword($hasher->hashPassword($user, $password));
                } else {
                    $user->setPassword($originalHash);
                }
               
                if ($isAdmin) {
                    if (!is_array($roles)) {
                        $roles = [$roles];
                    }
                    $user->setRoles($roles);
                } else {

                    $user->setRoles(['ROLE_USER']);
                }
                
                $em->flush();

                // Log the edit
                $activityLogger->log(
                    action: 'USER_EDIT',
                    entityType: 'User',
                    entityId: $user->getId(),
                    description: 'Edited user ' . $user->getUsername()
                );
                
                $this->addFlash('success', 'User updated successfully!');

                if ($this->isGranted('ROLE_ADMIN')) {
                    return $this->redirectToRoute('user_index');
                }

                return $this->redirectToRoute('customer_index');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error: ' . $e->getMessage());
            }
        } else {
            // $this->addFlash('error', 'Please fix the errors below');
        }
    }
    
    return $this->render('user/edit.html.twig', [
        'form' => $form->createView(),
        'user' => $user,
    ]);
}

   #[Route('/customer', name: 'customer_index', methods: ['GET'])]
public function customerIndex(UserRepository $repo, Request $request): Response
{
    $search = $request->query->get('search');

    $qb = $repo->createQueryBuilder('u')
        ->where('u.roles LIKE :role')
        ->setParameter('role', '%"ROLE_USER"%');

    if ($search) {
        $qb->andWhere('u.username LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    $customers = $qb->orderBy('u.createdAt', 'DESC')
                   ->getQuery()
                   ->getResult();

    return $this->render('user/customer_index.html.twig', [
        'customers' => $customers,
        'search' => $search,
    ]);
}

    #[Route('/{id}/delete', name: 'user_delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        // Prevent staff from deleting staff or admin users
        if (!$this->isGranted('ROLE_ADMIN')) {
            $targetRoles = $user->getRoles();
            if (in_array('ROLE_ADMIN', $targetRoles) || in_array('ROLE_STAFF', $targetRoles)) {
                $this->addFlash('error', 'You cannot delete staff or admin accounts.');
                return $this->redirectToRoute('customer_index');
            }
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $deletedUserId = $user->getId();
            $deletedUsername = $user->getUsername();
            $em->remove($user);
            $em->flush();

            // Log admin deletes a user
            $activityLogger->log(
                action: 'USER_DELETE',
                entityType: 'User',
                entityId: $deletedUserId,
                description: 'Admin deleted user ' . $deletedUsername
            );
        }
        
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('user_index');
        }
        
        return $this->redirectToRoute('customer_index');
    }

    #[Route('/{id}/disable', name: 'user_disable', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function disable(User $user, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        $currentUser = $this->getUser();

        if ($currentUser && $currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('error', 'You cannot disable your own account.');
            return $this->redirectToRoute('user_index');
        }

        $user->setEnabled(false);
        $em->flush();

        $activityLogger->log(
            action: 'USER_DISABLE',
            entityType: 'User',
            entityId: $user->getId(),
            description: 'Disabled user ' . $user->getUsername()
        );

        $this->addFlash('success', 'User account disabled successfully.');
        return $this->redirectToRoute('user_index');
    }

    #[Route('/{id}/enable', name: 'user_enable', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function enable(User $user, EntityManagerInterface $em, ActivityLogger $activityLogger): Response
    {
        $user->setEnabled(true);
        $em->flush();

        $activityLogger->log(
            action: 'USER_ENABLE',
            entityType: 'User',
            entityId: $user->getId(),
            description: 'Enabled user ' . $user->getUsername()
        );

        $this->addFlash('success', 'User account enabled successfully.');
        return $this->redirectToRoute('user_index');
    }

    #[Route('/profile', name: 'user_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException("You must be logged in.");
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/user/profile/edit', name: 'user_profile_edit')]
    public function editProfile(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $repo
    ): Response {

        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException("You must login.");
        }

        $originalEmail = $user->getEmail();
        $originalUsername = $user->getUsername();
        $data = [
            'username' => $user->getUsername(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'age' => $user->getAge(),
        ];

        $form = $this->createFormBuilder($data)
            ->add('username', TextType::class)
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('phone', TextType::class)
            ->add('age', IntegerType::class)
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = false;

            $username = trim($form->get('username')->getData());
            $name     = trim($form->get('name')->getData());
            $email    = trim($form->get('email')->getData());
            $phone    = trim($form->get('phone')->getData());
            $age      = $form->get('age')->getData();
            $password = $form->get('plainPassword')->getData();

            if (strlen($username) < 3) {
                $form->get('username')->addError(new FormError('Username must be at least 3 characters'));
                $errors = true;
            } elseif ($username !== $originalUsername && $repo->findOneBy(['username' => $username])) {
                $form->get('username')->addError(new FormError('Username already exists'));
                $errors = true;
            }

            if (empty($name)) {
                $form->get('name')->addError(new FormError('Name is required'));
                $errors = true;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $form->get('email')->addError(new FormError('Invalid email'));
                $errors = true;
            } elseif ($email !== $originalEmail && $repo->findOneBy(['email' => $email])) {
                $form->get('email')->addError(new FormError('Email already exists'));
                $errors = true;
            }

            if (!preg_match('/^09\\d{9}$/', $phone)) {
                $form->get('phone')->addError(new FormError('Phone must start with 09 and be 11 digits'));
                $errors = true;
            }

            if (!is_numeric($age) || $age < 18 || $age > 120) {
                $form->get('age')->addError(new FormError('Age must be between 18 and 120'));
                $errors = true;
            }

            if ($password && strlen($password) < 6) {
                $form->get('plainPassword')->addError(new FormError('Password must be at least 6 characters'));
                $errors = true;
            }

            if (!$errors) {
      
                $user->setUsername($username);
                $user->setName($name);
                $user->setEmail($email);
                $user->setPhone($phone);
                $user->setAge((int) $age);

                if ($password) {
                    $user->setPassword($hasher->hashPassword($user, $password));
                }

                $em->flush();

                $this->addFlash('success', 'Profile updated successfully!');
                return $this->redirectToRoute('user_profile');
            }

            // $this->addFlash('error', 'Please fix the errors.');
        }

        return $this->render('user/profile_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}