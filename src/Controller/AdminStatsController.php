<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\BookingRepository;
use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminStatsController extends AbstractController
{
    #[Route('/stats', name: 'admin_stats')]
    public function index(UserRepository $userRepository, BookingRepository $bookingRepository, ActivityLogRepository $activityLogRepository): Response
    {
        $users = $userRepository->findAll();

        $totalUsers = count($users);
        $totalAdmins = 0;
        $totalStaff  = 0;
        $totalRegularUsers = 0;

        foreach ($users as $user) {
            $roles = $user->getRoles();

            if (in_array('ROLE_ADMIN', $roles, true)) {
                $totalAdmins++;
            } elseif (in_array('ROLE_STAFF', $roles, true)) {
                $totalStaff++;
            } else {
                $totalRegularUsers++;
            }
        }

        $totalBookings = $bookingRepository->count([]);

        $recentActivity = $activityLogRepository
            ->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/stats.html.twig', [
            'total_users'          => $totalUsers,
            'total_admins'         => $totalAdmins,
            'total_staff'          => $totalStaff,
            'total_regular_users'  => $totalRegularUsers,
            'total_bookings'       => $totalBookings,
            'recent_activity'      => $recentActivity,
        ]);
    }
}
