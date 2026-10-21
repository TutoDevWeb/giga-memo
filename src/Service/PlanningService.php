<?php

namespace App\Service;

use App\Service\PlanningModel\Frequency;
use App\Service\PlanningModel\RevisionDay;
use App\Service\PlanningModel\TimeFrame;

class PlanningService
{

    public function get(
        \DateTimeImmutable $dateStart,
        TimeFrame $timeFrame = TimeFrame::ACQUISITION,
        Frequency $frequency = Frequency::NORMAL
    ): array {

        $revisionDays = [];
        $j = 1;
        for ($i = 0; $i < 90; $i++) {
            $revisionDay = new RevisionDay;
            $revisionDay->dateDay = new \DateTimeImmutable("today + $i days");
            $executionDay = $this->isExecutionDay(
                $dateStart,
                $revisionDay->dateDay,
                $timeFrame,
                $frequency
            );
            if ($executionDay === true) {
                $revisionDay->numDay = $j++;
                $revisionDay->offsetDay = $i;
                $revisionDays[] = $revisionDay;
            }
        }
        return $revisionDays;
    }

    public function isExecutionDay(
        \DateTimeImmutable $dateStart,
        \DateTimeImmutable $dateDay,
        TimeFrame $timeFrame,
        Frequency $frequency
    ): bool {

        if ($dateStart > $dateDay) return false;

        $interval = $dateStart->diff($dateDay);

        $nbDays = $interval->days;

        // Semaine 1
        if ($nbDays >= 1 && $nbDays <= 5) {
            return true;
        }

        dump($frequency->value);
        $base = $frequency->value;

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
