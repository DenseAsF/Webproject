<?php

namespace App\Form;

use App\Entity\Room;
use App\Entity\RoomStatus;
use App\Entity\RoomType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

class RoomForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
   $builder
      ->add('roomNumber', TextType::class, [
        'label' => 'Room Number',
    ])
    ->add('maxPeople', IntegerType::class, [
        'label' => 'Max People',
    ])
    ->add('price')
    ->add('roomType', EntityType::class, [
        'class' => RoomType::class,
        'choice_label' => 'name',
        'placeholder' => 'Select a room type',
    ])
    ->add('status', EntityType::class, [
        'class' => RoomStatus::class,
        'choice_label' => 'name',
        'placeholder' => 'Select room status',
    ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Room::class,
        ]);
    }
}
