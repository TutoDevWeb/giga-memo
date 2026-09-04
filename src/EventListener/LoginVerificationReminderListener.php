<?php

namespace App\EventListener;

use App\Entity\Users;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginVerificationReminderListener
{
    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof Users && !$user->isVerified()) {
            $event->getRequest()->getSession()->getFlashBag()->add(
                'warning',
                'Votre adresse email n\'est pas encore vérifiée. Merci d\'aller voir le mail que nous avons envoyé et de cliquer sur le lien pour faire disparaître ce message.'
            );
        }
    }
}
