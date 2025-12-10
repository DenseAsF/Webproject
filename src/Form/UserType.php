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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        $user = $options['data'] ?? null;
        
        $builder
            ->add('username', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Username is required']),
                    new Assert\Length([
                        'min' => 3, 
                        'max' => 180,
                        'minMessage' => 'Username must be at least {{ limit }} characters',
                        'maxMessage' => 'Username cannot be longer than {{ limit }} characters'
                    ]),
                ],
            ])
            ->add('name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Name is required']),
                ],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Email is required']),
                    new Assert\Email(['message' => 'Invalid email format']),
                ],
            ])
            ->add('phone', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Phone is required']),
                    new Assert\Regex([
                        'pattern' => '/^09\d{9}$/',
                        'message' => 'Phone must start with 09 and be 11 digits',
                    ]),
                ],
            ])
            ->add('age', IntegerType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Age is required']),
                    new Assert\Range([
                        'min' => 18,
                        'max' => 120,
                        'notInRangeMessage' => 'Age must be between {{ min }} and {{ max }}'
                    ]),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Staff' => 'ROLE_STAFF',
                    'Admin' => 'ROLE_ADMIN'
                ],
                'multiple' => true,
                'expanded' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Select at least one role']),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => !$isEdit,
                'constraints' => $isEdit ? [] : [
                    new Assert\NotBlank(['message' => 'Password is required']),
                    new Assert\Length([
                        'min' => 6,
                        'minMessage' => 'Password must be at least {{ limit }} characters',
                    ]),
                ],
                'attr' => [
                    'placeholder' => $isEdit ? 'Leave blank to keep current' : ''
                ]
            ]);
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);
    }
}