<?php

namespace App\Tests\Security\Voter;

use App\Entity\Categories;
use App\Entity\Users;
use App\Security\Voter\ResourceOwnerVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ResourceOwnerVoterTest extends TestCase
{
    // CAS 1 : Le propriétaire accède à sa propre ressource
    public function testVoteReturnsAccessGrantedIfUserIsOwner(): void
    {
        $voter = new ResourceOwnerVoter();

        $owner = new Users();
        $category = new Categories();
        $category->setUser($owner);

        $token = new UsernamePasswordToken($owner, 'main', ['ROLE_USER']);
        $vote = $voter->vote($token, $category, [ResourceOwnerVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $vote);
    }

    // CAS 2 : Un utilisateur essaie d'accéder à la ressource de quelqu'un d'autre -> REFUSÉ
    public function testVoteReturnsAccessDeniedIfUserIsNotOwner(): void
    {
        $voter = new ResourceOwnerVoter();

        // Le propriétaire de la catégorie
        $owner = new Users();
        $category = new Categories();
        $category->setUser($owner);

        // Un AUTRE utilisateur qui tente de tricher
        $hacker = new Users();

        $token = new UsernamePasswordToken($hacker, 'main', ['ROLE_USER']);
        $vote = $voter->vote($token, $category, [ResourceOwnerVoter::EDIT]);

        // On affirme que le vote doit être "ACCESS_DENIED" (-1)
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $vote);
    }

    // CAS 3 : On passe un sujet qui n'a pas de getUser() -> ABSTENTION
    public function testVoteAbstainsIfSubjectIsNotSupported(): void
    {
        $voter = new ResourceOwnerVoter();

        $user = new Users();
        // On passe un simple objet générique (stdClass) qui n'a pas de méthode getUser()
        $invalidSubject = new \stdClass();

        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);
        $vote = $voter->vote($token, $invalidSubject, [ResourceOwnerVoter::EDIT]);

        // On affirme que le voter s'abstient "ACCESS_ABSTAIN" (0)
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $vote);
    }
}
