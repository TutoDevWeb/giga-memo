<?php

namespace App\Form;

use App\Entity\Faqs;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectFaqFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('faq', EntityType::class, [
            // looks for choices from this entity
            'class' => Faqs::class,

            // uses the User.username property as the visible option string
            'choice_label' => 'name',

            // used to render a select box, check boxes or radios
            // 'multiple' => true,
            // 'expanded' => true,

            'placeholder' => 'Choisir une faq',
            'label' => 'Liste des Faqs',
        ])
            ->add('selectionner', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
    }
}
