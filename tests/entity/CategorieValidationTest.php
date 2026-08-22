<?php

namespace App\Tests\Entity;

use App\Entity\Categories;
use App\Entity\Product;
use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoriesValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        // Démarre le kernel Symfony
        self::bootKernel();

        // Récupère le service Validator du conteneur de test
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    /**
     * Un helper pour récupérer les erreurs sur une entité
     */
    private function getErrors(Categories $category)
    {
        return $this->validator->validate($category);
    }

    public function testValidCategory(): void
    {
        $fakeUser = new Users();
        $category = (new Categories())->setName('Mon super produit')->setUser($fakeUser);

        $errors = $this->getErrors($category);

        // On attend 0 erreur
        $this->assertCount(0, $errors);
    }

    public function testInvalidNullName(): void
    {
        $fakeUser = new Users();
        $category = (new Categories())->setUser($fakeUser);

        $errors = $this->getErrors($category);

        $this->assertCount(1, $errors);

        // Tu peux vérifier les messages si tu veux être ultra précis :
        $messages = [
            $errors[0]->getMessage(),
        ];

        $this->assertContains('Ce champ doit être renseigné.', $messages);
    }
    public function testInvalidNullUser(): void
    {
        $category = (new Categories())->setName('Une catégorie sans user');

        $errors = $this->getErrors($category);

        $this->assertCount(1, $errors);
        $this->assertEquals('La ressource doit être liée à un utilisateur.', $errors[0]->getMessage());
    }
}
