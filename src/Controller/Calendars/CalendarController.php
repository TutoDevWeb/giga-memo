<?php

namespace App\Controller\Calendars;

use App\Entity\Faqs;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Voter\ResourceOwnerVoter;
use DateTime;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CalendarController extends AbstractController
{
    #[Route('/calendar/{id_faq<\d+>?}', name: 'app_calendar')]
    #[IsGranted(ResourceOwnerVoter::VIEW, subject: 'faq')]
    public function index(
        #[MapEntity(id: 'id_faq')] Faqs $faq,
    ): Response {

        $dateStart = new \DateTimeImmutable('today');

        $dateDays = [];
        $executionDays = [];
        $numDays = [];
        for ($i = 0; $i < 90; $i++) {
            $dateDay = new \DateTimeImmutable("today + $i days");
            $executionDay = $this->isExecutionDay($dateStart, $dateDay);
            if ($executionDay === true) {
                $dateDays[] = $dateDay;
                $executionDays[] = $executionDay;
                $numDays[] = $i;
            }
        }



        return $this->render('calendar/index.html.twig', [
            'faq' => $faq,
            'dateStart' => $dateStart,
            'dateDays' => $dateDays,
            'executionDays' => $executionDays,
            'numDays' => $numDays
        ]);
    }

    private function isExecutionDay(\DateTimeImmutable $dateStart, \DateTimeImmutable $dateDay): bool
    {

        if ($dateStart > $dateDay) return false;

        $interval = $dateStart->diff($dateDay);

        $nbDays = $interval->days;

        // Semaine 1
        if ($nbDays >= 1 && $nbDays <= 5) {
            return true;
        }

        $base = 8;

        // $quotient = intdiv($nbDays, 7);
        // $reste = $nbDays % 7;

        $quotient = intdiv($nbDays, $base);
        $reste = $nbDays % $base;


        // Semaine 2 et 3
        if ($quotient >= 1 && $quotient <= 2) {
            return $reste >= 1 && $reste <= 3;
        }

        // Semaine 4 à 7 inclues
        if ($quotient >= 3 && $quotient <= 6) {
            return $reste === 1 || $reste === 2;
        }

        // Semaine 8 à 12 inclues
        if ($quotient >= 7 && $quotient <= 11) {
            return $reste === 1;
        }

        return false;
    }
}
