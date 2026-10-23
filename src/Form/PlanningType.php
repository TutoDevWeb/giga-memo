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
            'data' => TimeFrame::ACQUISITION,
        ]);
        $builder->add('frequency', null, [
            'class' => Frequency::class,
            'multiple' => false,
            'expanded' => true,
            'placeholder' => false,
            'data' => Frequency::NORMAL,
        ]);
        $builder->add('build', SubmitType::class);
        $builder->add('save', SubmitType::class);
        $builder->add('delete', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Planning::class,
        ]);
    }
}
