<?php

namespace App\Form;

use App\Entity\Service;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Service Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter service name'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Service name cannot be empty.']),
                ]
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter price',
                    'min' => 0,
                    'step' => '0.01'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Price cannot be empty.']),
                    new Positive(['message' => 'Price must be a positive number.']),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}
