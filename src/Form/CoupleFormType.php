<?php

namespace App\Form;

use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Rules;
use App\Model\LayerTypeEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoupleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('num')
            ->add('rules', EntityType::class, [
                'class' => Rules::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('question')
            ->add('reponse', TextareaType::class, [
                'required' => false,
            ])
            ->add('faq', EntityType::class, [
                'class' => Faqs::class,
                'choice_label' => 'name',
            ])
            ->add('images', FileType::class, [
                'mapped' => false,
                'multiple' => true,
                'required' => false,
            ])
            ->add('from', HiddenType::class, [
                'mapped' => false
            ])
            ->add('submit', SubmitType::class)
        ;

        if (LayerTypeEnum::LAYER_ONE == $options['layerType']) {
            $builder->add('regle');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Couples::class,
            'layerType' => LayerTypeEnum::LAYER_ONE,
            'from' => 'run'
        ]);
    }
}
