<?php

namespace App\Form;

use App\Entity\Faqs;
use App\Model\LayerTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FaqFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add(
                'layerType',
                EnumType::class,
                [
                    'class' => LayerTypeEnum::class,
                ]
            )
            ->add('duration', DateIntervalType::class, [
                'label' => 'Durée prévue',
                // On choisit les unités à afficher
                'with_years'  => false,
                'with_months' => false,
                'with_days'   => false,
                'with_hours'  => false,
                'with_minutes' => true,
                // Le format d'affichage dans le formulaire
                'labels' => [
                    'minutes' => 'Minutes',
                ],
            ])
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Faqs::class,
        ]);
    }
}
