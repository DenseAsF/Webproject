<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Points;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
        UserRepository $userRepo
    ): Response {

        $user = new User();

        // default role
        $user->setRoles(['ROLE_USER']);

        // generate account number
        $user->setAccountNumber($this->generateUniqueAccountNumber($userRepo));

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            if (!$form->isValid()) {
                // show errors clearly
                foreach ($form->getErrors(true) as $err) {
                    $this->addFlash("error", $err->getOrigin()->getName() . ": " . $err->getMessage());
                }
            } else {
                // hash password
                $plain = $form->get('plainPassword')->getData();
                $user->setPassword($hasher->hashPassword($user, $plain));

                // created at
                $user->setCreatedAt(new \DateTime());

                // points
                $points = new Points();
                $points->setPoints(0);
                $points->setUser($user);

                $em->persist($user);
                $em->persist($points);
                $em->flush();

                $this->addFlash("success", "User created successfully!");
                return $this->redirectToRoute('user_index');
            }
        }

        return $this->render('user/new.html.twig', [
            'form' => $form->createView(),
            'accountNumber' => $user->getAccountNumber()
        ]);
    }


    private function generateUniqueAccountNumber(UserRepository $repo): string
    {
        do {
            $num = 'ACC' . mt_rand(100000,999999);
        } while ($repo->findOneBy(['accountNumber'=>$num]));

        return $num;
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        // Keep the original hash in case password not changed
        $originalHash = $user->getPassword();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If plainPassword provided in edit, hash it; otherwise keep original
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($hasher->hashPassword($user, $plainPassword));
            } else {
                $user->setPassword($originalHash);
            }

            // Roles from the form
            $roles = $form->get('roles')->getData();
            if (empty($roles)) {
                $user->setRoles(['ROLE_USER']);
            } else {
                $user->setRoles($roles);
            }

            $em->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
