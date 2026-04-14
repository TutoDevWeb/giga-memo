<?php

namespace App\Form;

use App\Entity\Faqs;
use App\Entity\Rules;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RulesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('faq', EntityType::class, [
                'class' => Faqs::class,
                'choice_label' => 'name',
            ])
            ->add('name')
            ->add('content')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rules::class,
        ]);
    }
}
