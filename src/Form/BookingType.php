<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('checkInDate', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'id' => 'checkInDate',
                    'class' => 'mt-1 block w-full px-3 py-2 border rounded-md'
                ],
            ])
            ->add('checkOutDate', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'id' => 'checkOutDate',
                    'class' => 'mt-1 block w-full px-3 py-2 border rounded-md'
                ],
            ])
            ->add('roomId', TextType::class, [
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'id' => 'roomId',
                    'class' => 'hidden',
                ],
            ])
            ->add('customerAccountNumber', TextType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'Customer Account Number',
                'attr' => [
                    'class' => 'mt-1 block w-full px-3 py-2 border rounded-md'
                ],
            ]);
        // REMOVED the services field entirely - we'll handle it manually in the template
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}