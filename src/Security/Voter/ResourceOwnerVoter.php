<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class ResourceOwnerVoter extends Voter
{
    // On renomme les attributs pour qu'ils soient génériques (valables pour FAQ, Couple, Category, etc.)
    public const EDIT = 'RESOURCE_EDIT';
    public const VIEW = 'RESOURCE_VIEW';
    public const DELETE = 'RESOURCE_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Le voter s'active si l'attribut est géré ET si le sujet est un objet possédant la méthode getUser()
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])
            && is_object($subject)
            && method_exists($subject, 'getUser');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // Si l'utilisateur n'est pas connecté, accès refusé d'office
        if (!$user instanceof UserInterface) {
            $vote?->addReason('The user must be logged in to access this resource.');

            return false;
        }

        // Grâce à la vérification method_exists() dans supports(),
        // on est sûr à 100% que $subject possède la méthode getUser() issue de ton Trait.
        return $subject->getUser() === $user;
    }
}
