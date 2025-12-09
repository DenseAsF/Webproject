<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $opts)
    {
        $b
        ->add('username', TextType::class)
        ->add('name', TextType::class)
        ->add('email', EmailType::class)
        ->add('phone', TextType::class)
        ->add('age', IntegerType::class)

        ->add('roles', ChoiceType::class, [
            'choices'=>[
                'User' => 'ROLE_USER',
                'Staff' => 'ROLE_STAFF',
                'Admin' => 'ROLE_ADMIN'
            ],
            'multiple'=>true
        ])

        ->add('plainPassword', PasswordType::class, [
            'mapped'=>false,
            'constraints'=>[
                new Assert\NotBlank,
                new Assert\Length(min:6)
            ]
        ]);
    }
}
