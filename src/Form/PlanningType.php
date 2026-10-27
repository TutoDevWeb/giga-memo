<?php

namespace App\Form;

use App\Entity\Planning;
use App\Service\PlanningModel\Frequency;
use App\Service\PlanningModel\TimeFrame;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanningType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('timeFrame', null, [
            'class' => TimeFrame::class,
            'placeholder' => false,
            'choice_label' => fn(TimeFrame $choice) => $choice->value,
        ])
            ->add('frequency', null, [
                'class' => Frequency::class,
                'multiple' => false,
                'expanded' => true,
                'placeholder' => false,
                'choice_label' => fn(Frequency $choice) => sprintf('Cycle de %d jours', $choice->value),
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['save_label']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Planning::class,
            'save_label' => 'Enregistrer', // Valeur par défaut
        ]);
    }
}
