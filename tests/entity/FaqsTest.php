<?php

namespace App\Tests\Entity;

use App\Entity\Categories;
use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Rules;
use App\Entity\Users;
use PHPUnit\Framework\TestCase;

class FaqsTest extends TestCase
{
    public function testInitialState(): void
    {
        $faq = new Faqs();

        $this->assertNull($faq->getId());
        $this->assertNull($faq->getName());
        $this->assertNull($faq->getDuration());
        $this->assertNull($faq->getCategory());
        $this->assertNull($faq->getUser());

        $this->assertInstanceOf(\DateTimeImmutable::class, $faq->getCreateAt());
        $this->assertCount(0, $faq->getCouples());
        $this->assertCount(0, $faq->getRules());
    }

    public function testGettersAndSetters(): void
    {
        $faq = new Faqs();

        $faq->setName('FAQ Test Name');
        $this->assertSame('FAQ Test Name', $faq->getName());

        $duration = new \DateInterval('P1D');
        $faq->setDuration($duration);
        $this->assertSame($duration, $faq->getDuration());

        $category = $this->createMock(Categories::class);
        $faq->setCategory($category);
        $this->assertSame($category, $faq->getCategory());

        $user = $this->createMock(Users::class);
        $faq->setUser($user);
        $this->assertSame($user, $faq->getUser());

        $now = new \DateTimeImmutable();
        $faq->setCreateAt($now);
        $this->assertSame($now, $faq->getCreateAt());
    }

    public function testAddAndRemoveCouple(): void
    {
        $faq = new Faqs();
        $couple = $this->createMock(Couples::class);

        // On vérifie que getFaq retourne l'entité FAQ actuelle lors de la suppression
        $couple->method('getFaq')->willReturn($faq);

        // setFaq sera appelé 2 fois : d'abord avec $faq (addCouple), puis avec null (removeCouple)
        $couple->expects($this->exactly(2))
            ->method('setFaq')
            ->withConsecutive(
                [$this->identicalTo($faq)],
                [null]
            );

        // Ajout du couple
        $faq->addCouple($couple);
        $this->assertCount(1, $faq->getCouples());
        $this->assertTrue($faq->getCouples()->contains($couple));

        // Tenter d'ajouter un doublon (ne doit pas rappeler setFaq)
        $faq->addCouple($couple);
        $this->assertCount(1, $faq->getCouples());

        // Suppression du couple
        $faq->removeCouple($couple);
        $this->assertCount(0, $faq->getCouples());
        $this->assertFalse($faq->getCouples()->contains($couple));
    }

    public function testAddAndRemoveRule(): void
    {
        $faq = new Faqs();
        $rule = $this->createMock(Rules::class);

        $rule->method('getFaq')->willReturn($faq);

        $rule->expects($this->exactly(2))
            ->method('setFaq')
            ->withConsecutive(
                [$this->identicalTo($faq)],
                [null]
            );

        // Ajout de la règle
        $faq->addRule($rule);
        $this->assertCount(1, $faq->getRules());
        $this->assertTrue($faq->getRules()->contains($rule));

        // Tenter d'ajouter un doublon
        $faq->addRule($rule);
        $this->assertCount(1, $faq->getRules());

        // Suppression de la règle
        $faq->removeRule($rule);
        $this->assertCount(0, $faq->getRules());
        $this->assertFalse($faq->getRules()->contains($rule));
    }
}
