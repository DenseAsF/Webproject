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
        $userSearch = $request->query->get('userSearch');
        $action = $request->query->get('action');
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;

        $qb = $repo->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.createdAt', 'DESC');

        if ($userSearch) {
            $qb->andWhere('l.username LIKE :userSearch')
               ->setParameter('userSearch', '%' . $userSearch . '%');
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
                
            }
        }

      
        $total = (int) (clone $qb)->select('COUNT(l.id)')->getQuery()->getSingleScalarResult();


        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $logs = $qb->getQuery()->getResult();
        $users = $userRepo->findAll();

        $pages = ceil($total / $limit);

        return $this->render('activity_log/index.html.twig', [
            'logs' => $logs,
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => $pages,
            'total' => $total,
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
