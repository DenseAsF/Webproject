<?php

namespace App\Form;

use App\Entity\BookingRoom;
use App\Entity\Room;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingRoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('room', EntityType::class, [
            'class' => Room::class,
            'choice_label' => 'roomNumber',
            'placeholder' => 'Select Room',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BookingRoom::class,
        ]);
    }
}
