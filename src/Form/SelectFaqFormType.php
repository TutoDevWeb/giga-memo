<?php

namespace App\Form;

use App\Entity\Categories;
use App\Entity\Faqs;
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

        $builder
            ->add('category', EntityType::class, [
                'class' => Categories::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionnez une catégorie',
                'mapped' => false,
                'attr' => ['data-action' => 'change->dynamic-select#updateFaqs'] // Action Stimulus
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
                    'attr' => ['data-dynamic-select-target' => 'faqSelect'] // Cible Stimulus
                ]
            )
            ->add('run', SubmitType::class)
            ->add('edit', SubmitType::class);

        // Après le SUBMIT le framework va vérifier que la valeur proposée dans le select est une valeur autorisée.
        // Donc ici AVANT le submit on reconstitue cette liste.
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData(); // Les données envoyées (le tableau $_POST)

                // On récupère l'ID de la catégorie soumise
                $categoryId = $data['category'] ?? null;

                if ($categoryId) {
                    // On redéfinit le champ "faq" mais avec les bons choix cette fois
                    $form->add('faq', EntityType::class, [
                        'class' => Faqs::class,
                        'query_builder' => function (EntityRepository $er) use ($categoryId) {
                            return $er->createQueryBuilder('f')
                                ->where('f.category = :cat')
                                ->setParameter('cat', $categoryId);
                        },
                        'attr' => ['data-dynamic-select-target' => 'faqSelect']
                    ]);
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($options) {

                // Si il n'y a pas de faq en option ça ne sert à rien de venir ici
                if ($options['faq'] === null) return;

                // Si l'option faq n'est pas une instance de Faqs on ne trouvera pas ce dont on a besoin.
                if (!($options['faq'] instanceof Faqs)) return;

                $category = $options['faq']->getCategory();

                $form = $event->getForm();

                // On traite le select category
                $form->add('category', EntityType::class, [
                    'class' => Categories::class,
                    'choice_label' => 'name',
                    'data' => $category, // Forcer le select des catégories
                    'placeholder' => 'Sélectionnez une catégorie',
                    'attr' => ['data-action' => 'change->dynamic-select#updateFaqs']
                ]);

                // On traite le select faq
                $form->add('faq', EntityType::class, [
                    'class' => Faqs::class,
                    'choice_label' => 'name',
                    'data' => $options['faq'], // Forcer le select des Faqs
                    'query_builder' => function (EntityRepository $er) use ($category) {
                        return $er->createQueryBuilder('f')
                            ->where('f.category = :cat')
                            ->setParameter('cat', $category);
                    },
                    'attr' => ['data-dynamic-select-target' => 'faqSelect']
                ]);
            }
        );
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'faq' => null
        ]);

        // Optionnel mais recommandé : on précise que 'faq' doit être 
        // soit null, soit une instance de ton entité Faq.
        $resolver->setAllowedTypes('faq', ['null', 'App\Entity\Faqs']);
    }
}
