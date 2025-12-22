<?php

namespace App\Form;

use App\Entity\StoreSchedule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StoreScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('weekDay', ChoiceType::class, [
                'label' => 'Día',
                'choices' => [
                    'Lunes' => 'monday',
                    'Martes' => 'tuesday',
                    'Miércoles' => 'wednesday',
                    'Jueves' => 'thursday',
                    'Viernes' => 'friday',
                    'Sábado' => 'saturday',
                    'Domingo' => 'sunday',
                ],
            ])
            ->add('open', CheckboxType::class, [
                'label' => 'Abierto',
                'required' => false,
            ])
            ->add('timeFrom', TimeType::class, [
                'label' => 'Desde',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('timeTo', TimeType::class, [
                'label' => 'Hasta',
                'required' => false,
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoreSchedule::class,
        ]);
    }
}
