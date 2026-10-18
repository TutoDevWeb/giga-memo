<?php

namespace App\Form;

use App\Entity\Planning;
use App\Model\Frequency;
use App\Model\TimeFrame;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanningType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('timeFrame', null, [
                'class' => TimeFrame::class
            ])
            ->add('frequency', null, [
                'class' => Frequency::class
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Planning::class,
        ]);
    }
}
