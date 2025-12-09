<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use App\Entity\Points; // Add this import
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
{
    echo "=== Loading fixtures ===\n";
    
    // ADMIN USER
    $admin = new User();
    $admin->setUsername('admin');
    $admin->setEmail('admin@admin.com');
    $admin->setPhone('09221221212');
    $admin->setName('Admin User'); 
    $admin->setAge(30);
    $admin->setAccountNumber('A0001');
    $admin->setRoles(['ROLE_ADMIN']);
    $admin->setPassword(
        $this->hasher->hashPassword($admin, 'admin')
    );
    // ADD THIS: Set created date
    $admin->setCreatedAt(new \DateTime());
    
    echo "Created admin user: " . $admin->getName() . "\n";
    $manager->persist($admin);

    // STAFF USER
    $staff = new User();
    $staff->setUsername('staff');
    $staff->setEmail('staff@staff.com');
    $staff->setPhone('09333333333');
    $staff->setName('Staff User'); 
    $staff->setAge(25);
    $staff->setAccountNumber('S0001');
    $staff->setRoles(['ROLE_STAFF']);
    $staff->setPassword(
        $this->hasher->hashPassword($staff, 'staff')
    );
    // ADD THIS: Set created date
    $staff->setCreatedAt(new \DateTime('-1 day')); // Created yesterday
    
    echo "Created staff user: " . $staff->getName() . "\n";
    $manager->persist($staff);

    // POINTS
    $adminPoints = new Points();
    $adminPoints->setUser($admin); // Make sure to set user
    $adminPoints->setTotalPoints(0);
    $manager->persist($adminPoints);
    $admin->setPoints($adminPoints);
    
    $staffPoints = new Points();
    $staffPoints->setUser($staff); // Make sure to set user
    $staffPoints->setTotalPoints(0);
    $manager->persist($staffPoints);
    $staff->setPoints($staffPoints);
    
    // TEST USER (optional)
    $testUser = new User();
    $testUser->setUsername('testuser');
    $testUser->setEmail('test@test.com');
    $testUser->setPhone('09123456789');
    $testUser->setName('Test User');
    $testUser->setAge(28);
    $testUser->setAccountNumber('T0001');
    $testUser->setRoles(['ROLE_USER']);
    $testUser->setPassword(
        $this->hasher->hashPassword($testUser, 'test123')
    );
    $testUser->setCreatedAt(new \DateTime('-7 days')); // Created 7 days ago
    
    $testPoints = new Points();
    $testPoints->setUser($testUser);
    $testPoints->setTotalPoints(100); // Give test user some points
    $manager->persist($testPoints);
    $testUser->setPoints($testPoints);
    
    $manager->persist($testUser);
    echo "Created test user: " . $testUser->getName() . "\n";
    
    try {
        $manager->flush();
        echo "SUCCESS: All fixtures loaded!\n";
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
}