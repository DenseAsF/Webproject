<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function log(
        string $action,
        ?User $actor = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null
    ): void {
        if ($actor === null) {
            $user = $this->security->getUser();
            $actor = $user instanceof User ? $user : null;
        }

        $log = new ActivityLog();
        $log->setAction($action)
            ->setEntityType($entityType)
            ->setEntityId($entityId)
            ->setDescription($description);

        if ($actor instanceof User) {
            $log->setUser($actor);
            $log->setUsername($actor->getUsername());

            $roles = $actor->getRoles();
            $primaryRole = $roles[0] ?? null;

            if (is_string($primaryRole) && str_starts_with($primaryRole, 'ROLE_')) {
                $primaryRole = substr($primaryRole, 5); // e.g. ROLE_ADMIN -> ADMIN
            }

            $log->setRole($primaryRole);
        }

        $this->em->persist($log);
        $this->em->flush();
    }
}
