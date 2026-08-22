<?php

namespace App\Tests\Entity;

use App\Entity\Categories;
use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Images;
use App\Entity\Rules;
use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Couvre les contraintes de validation Symfony (#[Assert\...]) portées par les
 * entités : le champ "user" obligatoire (HasUserTrait, partagé par Categories,
 * Faqs, Couples, Rules, Images), ainsi que les contraintes propres à
 * Categories et Faqs (nom obligatoire, limité à 70 caractères, catégorie
 * obligatoire pour une Faq).
 *
 * Les tests de comportement pur des entités (getters/setters, collections)
 * vivent dans un fichier dédié par entité (CategoriesTest, FaqsTest, ...).
 */
class EntitiesValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    private function getErrors(object $entity)
    {
        return $this->validator->validate($entity);
    }

    // --- Categories ---

    public function testCategoryIsValidWithNameAndUser(): void
    {
        $category = (new Categories())->setName('Ma catégorie')->setUser(new Users());

        $this->assertCount(0, $this->getErrors($category));
    }

    public function testCategoryIsInvalidWithoutName(): void
    {
        $category = (new Categories())->setUser(new Users());

        $errors = $this->getErrors($category);

        $this->assertCount(1, $errors);
        $this->assertSame('Ce champ doit être renseigné.', $errors[0]->getMessage());
    }

    public function testCategoryIsInvalidWithNameTooLong(): void
    {
        $category = (new Categories())->setName(str_repeat('a', 71))->setUser(new Users());

        $errors = $this->getErrors($category);

        $this->assertCount(1, $errors);
        $this->assertSame('Le nom ne doit pas dépasser 70 caractères.', $errors[0]->getMessage());
    }

    public function testCategoryIsInvalidWithoutUser(): void
    {
        $category = (new Categories())->setName('Ma catégorie');

        $errors = $this->getErrors($category);

        $this->assertCount(1, $errors);
        $this->assertSame('La ressource doit être liée à un utilisateur.', $errors[0]->getMessage());
    }

    // --- Faqs ---

    public function testFaqIsValidWithNameCategoryAndUser(): void
    {
        $faq = (new Faqs())->setName('Ma FAQ')->setCategory(new Categories())->setUser(new Users());

        $this->assertCount(0, $this->getErrors($faq));
    }

    public function testFaqIsInvalidWithoutName(): void
    {
        $faq = (new Faqs())->setCategory(new Categories())->setUser(new Users());

        $errors = $this->getErrors($faq);

        $this->assertCount(1, $errors);
        $this->assertSame('Ce champ doit être renseigné.', $errors[0]->getMessage());
    }

    public function testFaqIsInvalidWithNameTooLong(): void
    {
        $faq = (new Faqs())->setName(str_repeat('a', 71))->setCategory(new Categories())->setUser(new Users());

        $errors = $this->getErrors($faq);

        $this->assertCount(1, $errors);
        $this->assertSame('Le nom ne doit pas dépasser 70 caractères.', $errors[0]->getMessage());
    }

    public function testFaqIsInvalidWithoutCategory(): void
    {
        $faq = (new Faqs())->setName('Ma FAQ')->setUser(new Users());

        $errors = $this->getErrors($faq);

        $this->assertCount(1, $errors);
        $this->assertSame('Cette valeur ne doit pas être nulle.', $errors[0]->getMessage());
    }

    public function testFaqIsInvalidWithoutUser(): void
    {
        $faq = (new Faqs())->setName('Ma FAQ')->setCategory(new Categories());

        $errors = $this->getErrors($faq);

        $this->assertCount(1, $errors);
        $this->assertSame('La ressource doit être liée à un utilisateur.', $errors[0]->getMessage());
    }

    // --- Couples / Rules / Images : seule la contrainte "needUser" (HasUserTrait) s'applique ---

    public function testCoupleIsValidWithUser(): void
    {
        $couple = (new Couples())->setUser(new Users());

        $this->assertCount(0, $this->getErrors($couple));
    }

    public function testCoupleIsInvalidWithoutUser(): void
    {
        $errors = $this->getErrors(new Couples());

        $this->assertCount(1, $errors);
        $this->assertSame('La ressource doit être liée à un utilisateur.', $errors[0]->getMessage());
    }

    public function testRuleIsValidWithUser(): void
    {
        $rule = (new Rules())->setUser(new Users());

        $this->assertCount(0, $this->getErrors($rule));
    }

    public function testRuleIsInvalidWithoutUser(): void
    {
        $errors = $this->getErrors(new Rules());

        $this->assertCount(1, $errors);
        $this->assertSame('La ressource doit être liée à un utilisateur.', $errors[0]->getMessage());
    }

    public function testImageIsValidWithUser(): void
    {
        $image = (new Images())->setUser(new Users());

        $this->assertCount(0, $this->getErrors($image));
    }

    public function testImageIsInvalidWithoutUser(): void
    {
        $errors = $this->getErrors(new Images());

        $this->assertCount(1, $errors);
        $this->assertSame('La ressource doit être liée à un utilisateur.', $errors[0]->getMessage());
    }
}
