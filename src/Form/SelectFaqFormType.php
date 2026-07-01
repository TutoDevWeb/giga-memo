<?php

namespace App\Form;

use App\Entity\Categories;
use App\Entity\Faqs;
// À adapter selon ton namespace User
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectFaqFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // On récupère l'utilisateur passé en option
        $user = $options['user'];

        $builder
            ->add('category', EntityType::class, [
                'class' => Categories::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionnez une catégorie',
                'mapped' => false,
                // Filtrage de la catégorie de départ pour l'utilisateur connecté
                'query_builder' => function (EntityRepository $er) use ($user) {
                    return $er->createQueryBuilder('c')
                        ->where('c.user = :user') // Remplace 'user' par ta propriété de relation dans Categories
                        ->setParameter('user', $user);
                },
                'attr' => ['data-action' => 'change->dynamic-select#updateFaqs'], // Action Stimulus
            ])
            ->add(
                'faq',
                EntityType::class,
                [
                    'class' => Faqs::class,
                    'choices' => [], // Vide au départ
                    'placeholder' => 'Choisissez une catégorie d\'abord',
                    'label' => 'Liste des Faqs',
                    'mapped' => false,
                    'attr' => ['data-dynamic-select-target' => 'faqSelect'], // Cible Stimulus
                ]
            )
            ->add('run', SubmitType::class)
            ->add('edit', SubmitType::class);

        // Validation lors de la soumission (PRE_SUBMIT)
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($user) {
                $form = $event->getForm();
                $data = $event->getData();

                $category = $data['category'] ?? null;

                if ($category) {
                    $form->add('faq', EntityType::class, [
                        'class' => Faqs::class,
                        // On sécurise en vérifiant la catégorie ET l'utilisateur
                        'query_builder' => function (EntityRepository $er) use ($category, $user) {
                            return $er->createQueryBuilder('f')
                                ->where('f.category = :cat')
                                ->andWhere('f.user = :user') // Remplace 'user' par ta propriété dans Faqs
                                ->setParameter('cat', $category)
                                ->setParameter('user', $user);
                        },
                        'attr' => ['data-dynamic-select-target' => 'faqSelect'],
                    ]);
                }
            }
        );

        // Initialisation si édition / données existantes (PRE_SET_DATA)
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options, $user) {
                if (null === $options['faq']) {
                    return;
                }
                if (!$options['faq'] instanceof Faqs) {
                    return;
                }

                $category = $options['faq']->getCategory();
                $form = $event->getForm();

                // On traite le select category
                $form->add('category', EntityType::class, [
                    'class' => Categories::class,
                    'choice_label' => 'name',
                    'data' => $category,
                    'placeholder' => 'Sélectionnez une catégorie',
                    'query_builder' => function (EntityRepository $er) use ($user) {
                        return $er->createQueryBuilder('c')
                            ->where('c.user = :user')
                            ->setParameter('user', $user);
                    },
                    'attr' => ['data-action' => 'change->dynamic-select#updateFaqs'],
                ]);

                // On traite le select faq
                $form->add('faq', EntityType::class, [
                    'class' => Faqs::class,
                    'choice_label' => 'name',
                    'data' => $options['faq'],
                    'query_builder' => function (EntityRepository $er) use ($category, $user) {
                        return $er->createQueryBuilder('f')
                            ->where('f.category = :cat')
                            ->andWhere('f.user = :user')
                            ->setParameter('cat', $category)
                            ->setParameter('user', $user);
                    },
                    'attr' => ['data-dynamic-select-target' => 'faqSelect'],
                ]);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'faq' => null,
            'user' => null, // On définit la nouvelle option par défaut
        ]);

        $resolver->setAllowedTypes('faq', ['null', 'App\Entity\Faqs']);

        // On exige que l'option user soit une instance de la classe User
        $resolver->setAllowedTypes('user', ['App\Entity\Users']);
        $resolver->setRequired('user'); // Rend l'option obligatoire
    }
}
