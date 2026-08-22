<?php

namespace App\Form;

use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Rules;
use App\Repository\RulesRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class CoupleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // On récupère l'entité passée en option
        $faq = $options['faq'];

        $builder
            ->add('num')
            ->add('rules', EntityType::class, [
                'class' => Rules::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'query_builder' => function (RulesRepository $rulesRepo) use ($faq): QueryBuilder {
                    return $rulesRepo->createQueryBuilder('r')
                        ->where('r.faq = :faq')
                        ->setParameter('faq', $faq)
                        ->orderBy('r.name', 'ASC');
                },
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
                'attr' => [
                    'accept' => '.png',
                ],
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '2M',
                                'mimeTypes' => [
                                    'image/png',
                                ],
                                'maxSizeMessage' => 'Le fichier {{ name }} est trop volumineux ({{ size }} {{ suffix }}). La taille maximale autorisée est {{ limit }} {{ suffix }}.',
                                'mimeTypesMessage' => 'Merci de ne déposer que des images au format PNG.',
                            ]),
                        ],
                    ]),
                ],
            ])
            ->add('from', HiddenType::class, [
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Couples::class,
            'from' => 'run',
            'attr' => ['novalidate' => 'novalidate'],
        ]);

        // On définit l'option comme obligatoire
        $resolver->setRequired('faq');

        // On valide que c'est bien une instance de l'entité Category
        $resolver->setAllowedTypes('faq', [Faqs::class]);
    }
}
