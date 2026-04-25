<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/users')]
class ApiUsersController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('/{id}', name: 'api_users_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showUser(User $user, #[CurrentUser] ?User $currentUser = null): JsonResponse
    {
        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        // Only admin or the user themselves can view
        if (!in_array('ROLE_ADMIN', $currentUser->getRoles()) && $currentUser->getId() !== $user->getId()) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/{id}', name: 'api_users_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(User $user, EntityManagerInterface $em, #[CurrentUser] ?User $currentUser = null): JsonResponse
    {
 
        error_log('API DELETE: Current user = ' . ($currentUser ? $currentUser->getEmail() : 'NULL'));
        if ($currentUser) {
            error_log('API DELETE: User roles = ' . json_encode($currentUser->getRoles()));
        }
        
        if (!$currentUser) {
            return $this->json(['error' => 'Authentication required'], 401);
        }


        if (!in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            error_log('API DELETE: ACCESS DENIED - User is not ADMIN');
            return $this->json(['error' => 'Only administrators can delete users'], 403);
        }


        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['error' => 'Cannot delete administrator accounts'], 403);
        }


        if ($currentUser->getId() === $user->getId()) {
            return $this->json(['error' => 'Cannot delete your own account'], 403);
        }

        try {
            $deletedUserId = $user->getId();
            $deletedUsername = $user->getUsername();
            
            $em->remove($user);
            $em->flush();

  
            $this->activityLogger->log(
                action: 'USER_DELETE',
                entityType: 'User',
                entityId: $deletedUserId,
                description: 'Admin ' . $currentUser->getUsername() . ' deleted user ' . $deletedUsername
            );

            return $this->json([
                'success' => true,
                'message' => 'User deleted successfully',
                'deleted_user' => [
                    'id' => $deletedUserId,
                    'username' => $deletedUsername
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }
}
