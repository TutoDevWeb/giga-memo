<?php

namespace App\Tests\Form;

use App\Entity\Categories;
use App\Entity\Users;
use App\Form\CategoriesType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Ces tests couvrent CategoriesType : mapping du champ "name" et validation
 * de l'entité sous-jacente (NotBlank, longueur max) déclenchée par la
 * soumission du formulaire racine.
 */
class CategoriesTypeTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
    }

    private function submit(array $data): \Symfony\Component\Form\FormInterface
    {
        $category = new Categories();
        $category->setUser(new Users());

        // Le CSRF est activé globalement (config/packages/csrf.yaml) ; hors contexte
        // HTTP, aucun token n'est disponible, on le désactive pour isoler le mapping.
        $form = $this->formFactory->create(CategoriesType::class, $category, ['csrf_protection' => false]);
        $form->submit($data);

        return $form;
    }

    public function testValidNameIsAccepted(): void
    {
        $form = $this->submit(['name' => 'Ma catégorie']);

        $this->assertTrue($form->isValid());
        $this->assertSame('Ma catégorie', $form->getData()->getName());
    }

    public function testEmptyNameIsRejected(): void
    {
        $form = $this->submit(['name' => '']);

        $this->assertFalse($form->isValid());
        $this->assertStringContainsString('Ce champ doit être renseigné.', (string) $form->getErrors(true));
    }

    public function testMissingNameFieldFallsBackToEmptyString(): void
    {
        // Le champ a 'empty_data' => '' : une absence de valeur est transformée
        // en chaîne vide plutôt qu'en null, mais reste rejetée par NotBlank.
        $form = $this->submit([]);

        $this->assertFalse($form->isValid());
        $this->assertSame('', $form->getData()->getName());
    }

    public function testNameTooLongIsRejected(): void
    {
        $form = $this->submit(['name' => str_repeat('a', 71)]);

        $this->assertFalse($form->isValid());
        $this->assertStringContainsString('Le nom ne doit pas dépasser 70 caractères.', (string) $form->getErrors(true));
    }
}
