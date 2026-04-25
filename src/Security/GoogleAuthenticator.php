<?php
// src/Security/GoogleAuthenticator.php
namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $em,
        private RouterInterface $router,
        private UserRepository $userRepository
    ) {}

    public function supports(Request $request): ?bool
    {

        return $request->attributes->get('_route') === 'app_google_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();

            
                $existingUser = $this->em->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if ($existingUser) {
                  
                    $existingUser->setIsVerified(true);
                    $this->em->flush();
                    
                 
                    error_log('GoogleAuth: Auto-verified user ' . $existingUser->getEmail() . ' with isVerified=' . ($existingUser->isVerified() ? 'true' : 'false'));
                    
                    return $existingUser;
                }

             
                $user = new User();
                $user->setEmail($email);
                $user->setUsername(explode('@', $email)[0]);
                $user->setRoles(['ROLE_STAFF']);
                $user->setIsVerified(true);     
                $user->setPhone('N/A');
                $user->setName(explode('@', $email)[0]);   
                $user->setAge(18);
                $user->setAccountNumber($this->generateUniqueAccountNumber());
                $user->setPassword('');             
               

                $this->em->persist($user);
                $this->em->flush();

                return $user;
            })
        );
    }

      private function generateUniqueAccountNumber(): string
    {
        do {
            $num = 'ACC' . mt_rand(100000, 999999);
        } while ($this->userRepository->findOneBy(['accountNumber' => $num]));

        return $num;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
       if($token->getUser()->getRoles() == ['ROLE_STAFF']){
        return new RedirectResponse($this->router->generate('booking_index'));
       }
       else{
       return new RedirectResponse($this->router->generate('user_profile'));
       }
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set('auth_error', strtr($exception->getMessageKey(), $exception->getMessageData()));
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
