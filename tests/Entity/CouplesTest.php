<?php

namespace App\Tests\Entity;

use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Images;
use App\Entity\Rules;
use App\Entity\Users;
use PHPUnit\Framework\TestCase;

class CouplesTest extends TestCase
{
    public function testInitialState(): void
    {
        $couple = new Couples();

        $this->assertNull($couple->getId());
        $this->assertNull($couple->getNum());
        $this->assertNull($couple->getFaq());
        $this->assertNull($couple->getQuestion());
        $this->assertNull($couple->getReponse());
        $this->assertNull($couple->getUser());

        $this->assertInstanceOf(\DateTimeImmutable::class, $couple->getCreatedAt());
        $this->assertTrue($couple->isTodoRun());
        $this->assertTrue($couple->isTodoReview());
        $this->assertCount(0, $couple->getImages());
        $this->assertCount(0, $couple->getRules());
    }

    public function testGettersAndSetters(): void
    {
        $couple = new Couples();

        $couple->setNum(3);
        $this->assertSame(3, $couple->getNum());

        $createdAt = new \DateTimeImmutable('2026-01-01');
        $couple->setCreatedAt($createdAt);
        $this->assertSame($createdAt, $couple->getCreatedAt());

        $faq = $this->createMock(Faqs::class);
        $couple->setFaq($faq);
        $this->assertSame($faq, $couple->getFaq());

        $couple->setQuestion('Question ?');
        $this->assertSame('Question ?', $couple->getQuestion());

        $couple->setReponse('Réponse.');
        $this->assertSame('Réponse.', $couple->getReponse());

        $couple->setTodoRun(false);
        $this->assertFalse($couple->isTodoRun());

        $couple->setTodoReview(false);
        $this->assertFalse($couple->isTodoReview());

        $couple->setSelectReview(true);
        $this->assertTrue($couple->isSelectReview());

        $user = $this->createMock(Users::class);
        $couple->setUser($user);
        $this->assertSame($user, $couple->getUser());
    }

    public function testAddAndRemoveImage(): void
    {
        $couple = new Couples();
        $image = $this->createMock(Images::class);

        // On vérifie que getCouple retourne l'entité actuelle lors de la suppression
        $image->method('getCouple')->willReturn($couple);

        // setCouple sera appelé 2 fois : d'abord avec $couple (addImage), puis avec null (removeImage)
        $image->expects($this->exactly(2))
            ->method('setCouple')
            ->withConsecutive(
                [$this->identicalTo($couple)],
                [null]
            );

        // Ajout de l'image
        $couple->addImage($image);
        $this->assertCount(1, $couple->getImages());
        $this->assertTrue($couple->getImages()->contains($image));

        // Tenter d'ajouter un doublon (ne doit pas rappeler setCouple)
        $couple->addImage($image);
        $this->assertCount(1, $couple->getImages());

        // Suppression de l'image
        $couple->removeImage($image);
        $this->assertCount(0, $couple->getImages());
        $this->assertFalse($couple->getImages()->contains($image));
    }

    public function testAddAndRemoveRule(): void
    {
        // Couples est le côté propriétaire de la relation ManyToMany avec Rules :
        // pas de synchronisation à répercuter sur Rules ici.
        $couple = new Couples();
        $rule = $this->createMock(Rules::class);

        $couple->addRule($rule);
        $this->assertCount(1, $couple->getRules());
        $this->assertTrue($couple->getRules()->contains($rule));

        // Tenter d'ajouter un doublon
        $couple->addRule($rule);
        $this->assertCount(1, $couple->getRules());

        $couple->removeRule($rule);
        $this->assertCount(0, $couple->getRules());
        $this->assertFalse($couple->getRules()->contains($rule));
    }
}
