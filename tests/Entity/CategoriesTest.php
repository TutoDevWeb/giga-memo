<?php

namespace App\Tests\Entity;

use App\Entity\Categories;
use App\Entity\Faqs;
use App\Entity\Users;
use PHPUnit\Framework\TestCase;

class CategoriesTest extends TestCase
{
    public function testInitialState(): void
    {
        $category = new Categories();

        $this->assertNull($category->getId());
        $this->assertNull($category->getName());
        $this->assertNull($category->getUser());
        $this->assertCount(0, $category->getFaqs());
    }

    public function testGettersAndSetters(): void
    {
        $category = new Categories();

        $category->setName('Catégorie Test');
        $this->assertSame('Catégorie Test', $category->getName());

        $user = $this->createMock(Users::class);
        $category->setUser($user);
        $this->assertSame($user, $category->getUser());
    }

    public function testAddAndRemoveFaq(): void
    {
        $category = new Categories();
        $faq = $this->createMock(Faqs::class);

        // On vérifie que getCategory retourne l'entité actuelle lors de la suppression
        $faq->method('getCategory')->willReturn($category);

        // setCategory sera appelé 2 fois : d'abord avec $category (addFaq), puis avec null (removeFaq)
        $faq->expects($this->exactly(2))
            ->method('setCategory')
            ->withConsecutive(
                [$this->identicalTo($category)],
                [null]
            );

        // Ajout de la faq
        $category->addFaq($faq);
        $this->assertCount(1, $category->getFaqs());
        $this->assertTrue($category->getFaqs()->contains($faq));

        // Tenter d'ajouter un doublon (ne doit pas rappeler setCategory)
        $category->addFaq($faq);
        $this->assertCount(1, $category->getFaqs());

        // Suppression de la faq
        $category->removeFaq($faq);
        $this->assertCount(0, $category->getFaqs());
        $this->assertFalse($category->getFaqs()->contains($faq));
    }
}
