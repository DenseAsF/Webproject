<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(): Response
    {
        return $this->render('contact.html.twig');
    }

    #[Route('/contact/submit', name: 'contact_submit', methods: ['POST'])]
public function submit(Request $request, MailerInterface $mailer): Response
{
    $name = $request->request->get('name');
    $email = $request->request->get('email');
    $phone = $request->request->get('phone');
    $subject = $request->request->get('subject');
    $message = $request->request->get('message');


    $errors = [];

    if (empty($name) || strlen(trim($name)) < 2) {
        $errors[] = 'Name must be at least 2 characters long.';
    } elseif (strlen(trim($name)) > 100) {
        $errors[] = 'Name cannot exceed 100 characters.';
    }


    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

  
    if (!empty($phone) && !preg_match('/^[\+]?[0-9\s\-\(\)]+$/', $phone)) {
        $errors[] = 'Please provide a valid phone number.';
    }


    if (empty($message) || strlen(trim($message)) < 10) {
        $errors[] = 'Message must be at least 10 characters long.';
    } elseif (strlen(trim($message)) > 1000) {
        $errors[] = 'Message cannot exceed 1000 characters.';
    }


    if (empty($subject)) {
        $errors[] = 'Please select an inquiry type.';
    }

  
    if (!empty($errors)) {
        foreach ($errors as $error) {
            $this->addFlash('error', $error);
        }
        return $this->redirectToRoute('app_contact');
    }

  
    $name = htmlspecialchars(trim($name), ENT_QUOTES, 'UTF-8');
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($phone), ENT_QUOTES, 'UTF-8');
    $subject = htmlspecialchars(trim($subject), ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars(trim($message), ENT_QUOTES, 'UTF-8');

    try {
        $emailMessage = (new Email())
            ->from('puccifrag@gmail.com') 
            ->to('densebaka@gmail.com')   
            ->subject($subject . ' - Hotel Diongco')
            ->html("
                <h2>New Contact Form Submission</h2>
                <p><strong>Name:</strong> {$name}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>Phone:</strong> {$phone}</p>
                <p><strong>Subject:</strong> {$subject}</p>
                <p><strong>Message:</strong><br>" . nl2br($message) . "</p>
                <hr>
                <p><small><em>Submitted on: " . date('Y-m-d H:i:s') . "</em></small></p>
            ");

        $mailer->send($emailMessage);
        $this->addFlash('success', 'Your message has been sent successfully! We will get back to you soon.');

    } catch (\Exception $e) {

        error_log('Contact form error: ' . $e->getMessage());
        
   
        $this->addFlash('error', 'Sorry, we encountered an issue sending your message. Please try again later or contact us directly at +63 917 890 1234.');
    }

    return $this->redirectToRoute('app_contact');
}

}
