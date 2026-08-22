<?php

namespace App\Tests\Entity;

use App\Entity\Users;
use PHPUnit\Framework\TestCase;

class UsersTest extends TestCase
{
    public function testInitialState(): void
    {
        $user = new Users();

        $this->assertNull($user->getId());
        $this->assertNull($user->getEmail());
        $this->assertNull($user->getPassword());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testGettersAndSetters(): void
    {
        $user = new Users();

        $user->setEmail('user@example.com');
        $this->assertSame('user@example.com', $user->getEmail());

        $user->setPassword('hashed-password');
        $this->assertSame('hashed-password', $user->getPassword());
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = (new Users())->setEmail('user@example.com');

        $this->assertSame('user@example.com', $user->getUserIdentifier());
    }

    public function testGetRolesAlwaysIncludesRoleUserWithoutDuplicate(): void
    {
        $user = (new Users())->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_ADMIN', $roles);
        // ROLE_USER ne doit apparaître qu'une seule fois même si déjà présent
        $this->assertSame(1, array_count_values($roles)['ROLE_USER']);
    }

    public function testSerializeHashesPasswordInsteadOfExposingIt(): void
    {
        $user = (new Users())->setEmail('user@example.com')->setPassword('secret-password');

        $data = $user->__serialize();

        $this->assertSame(hash('crc32c', 'secret-password'), $data["\0".Users::class."\0password"]);
        $this->assertNotContains('secret-password', $data);
    }
}
