<?php

namespace App\Tests\Entity;

use App\Entity\Couples;
use App\Entity\Images;
use App\Entity\Users;
use PHPUnit\Framework\TestCase;

class ImagesTest extends TestCase
{
    public function testInitialState(): void
    {
        $image = new Images();

        $this->assertNull($image->getId());
        $this->assertNull($image->getName());
        $this->assertNull($image->getUser());
        $this->assertNull($image->getCouple());
    }

    public function testGettersAndSetters(): void
    {
        $image = new Images();

        $image->setName('photo.png');
        $this->assertSame('photo.png', $image->getName());

        $user = $this->createMock(Users::class);
        $image->setUser($user);
        $this->assertSame($user, $image->getUser());

        $couple = $this->createMock(Couples::class);
        $image->setCouple($couple);
        $this->assertSame($couple, $image->getCouple());

        $image->setCouple(null);
        $this->assertNull($image->getCouple());
    }
}
