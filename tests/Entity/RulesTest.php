<?php

namespace App\Tests\Entity;

use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Rules;
use App\Entity\Users;
use PHPUnit\Framework\TestCase;

class RulesTest extends TestCase
{
    public function testInitialState(): void
    {
        $rule = new Rules();

        $this->assertNull($rule->getId());
        $this->assertNull($rule->getName());
        $this->assertNull($rule->getContent());
        $this->assertNull($rule->getFaq());
        $this->assertNull($rule->getUser());
        $this->assertCount(0, $rule->getCouples());
    }

    public function testGettersAndSetters(): void
    {
        $rule = new Rules();

        $rule->setName('Règle Test');
        $this->assertSame('Règle Test', $rule->getName());

        $rule->setContent('Contenu de la règle');
        $this->assertSame('Contenu de la règle', $rule->getContent());

        $faq = $this->createMock(Faqs::class);
        $rule->setFaq($faq);
        $this->assertSame($faq, $rule->getFaq());

        $user = $this->createMock(Users::class);
        $rule->setUser($user);
        $this->assertSame($user, $rule->getUser());
    }

    public function testAddAndRemoveCouple(): void
    {
        // Rules est le côté inverse de la relation ManyToMany : addCouple/removeCouple
        // doivent répercuter l'ajout/suppression sur le côté propriétaire (Couples).
        $rule = new Rules();
        $couple = $this->createMock(Couples::class);

        $couple->expects($this->once())
            ->method('addRule')
            ->with($this->identicalTo($rule));

        $couple->expects($this->once())
            ->method('removeRule')
            ->with($this->identicalTo($rule));

        // Ajout du couple
        $rule->addCouple($couple);
        $this->assertCount(1, $rule->getCouples());
        $this->assertTrue($rule->getCouples()->contains($couple));

        // Suppression du couple
        $rule->removeCouple($couple);
        $this->assertCount(0, $rule->getCouples());
        $this->assertFalse($rule->getCouples()->contains($couple));
    }
}
