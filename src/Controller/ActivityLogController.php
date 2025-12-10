<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activity-log')]
class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'activity_log_index', methods: ['GET'])]
    public function index(ActivityLogRepository $repo, UserRepository $userRepo, Request $request): Response
    {
        $userId = $request->query->get('user');
        $action = $request->query->get('action');
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');

        $qb = $repo->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.createdAt', 'DESC');

        if ($userId) {
            $qb->andWhere('u.id = :userId')
               ->setParameter('userId', $userId);
        }

        if ($action) {
            $qb->andWhere('l.action = :action')
               ->setParameter('action', $action);
        }

        if ($startDate && $endDate) {
            try {
                $start = new \DateTimeImmutable($startDate . ' 00:00:00');
                $end = new \DateTimeImmutable($endDate . ' 23:59:59');

                $qb->andWhere('l.createdAt BETWEEN :start AND :end')
                   ->setParameter('start', $start)
                   ->setParameter('end', $end);
            } catch (\Exception $e) {
                // ignore invalid dates
            }
        }

        $logs = $qb->getQuery()->getResult();
        $users = $userRepo->findAll();

        return $this->render('activity_log/index.html.twig', [
            'logs' => $logs,
            'users' => $users,
        ]);
    }

    #[Route('/{id}', name: 'activity_log_show', methods: ['GET'])]
    public function show(int $id, ActivityLogRepository $repo): Response
    {
        $log = $repo->find($id);

        if (!$log) {
            throw $this->createNotFoundException('Activity log not found');
        }

        return $this->render('activity_log/show.html.twig', [
            'log' => $log,
        ]);
    }
}
