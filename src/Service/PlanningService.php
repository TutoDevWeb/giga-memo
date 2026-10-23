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

        if ($timeFrame === TimeFrame::ACQUISITION) {
            $j = 1;
            for ($i = 0; $i < 180; $i++) {
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
        }

        // On part sur un an de révision
        // Cadence rapide 15 jours
        // Cadence normale 1 mois
        // Cadence lente 2 mois
        if ($timeFrame === TimeFrame::MAINTENANCE) {

            if ($frequency === Frequency::QUICK) {
                $period = 15;
                $j = 1;
                for ($i = 0; $i < 24; $i++) {
                    $revisionDay = new RevisionDay;
                    $offset = $i * $period;
                    $revisionDay->dateDay = new \DateTimeImmutable("today + $offset days");
                    $revisionDay->numDay = $j++;
                    $revisionDay->offsetDay = $i * $period;
                    $revisionDays[] = $revisionDay;
                }
            }

            if ($frequency === Frequency::NORMAL) {
                $period = 30;
                $j = 1;
                for ($i = 0; $i < 12; $i++) {
                    $revisionDay = new RevisionDay;
                    $offset = $i * $period;
                    $revisionDay->dateDay = new \DateTimeImmutable("today + $offset days");
                    $revisionDay->numDay = $j++;
                    $revisionDay->offsetDay = $i * $period;
                    $revisionDays[] = $revisionDay;
                }
            }

            if ($frequency === Frequency::SLOW) {
                $period = 60;
                $j = 1;
                for ($i = 0; $i < 6; $i++) {
                    $revisionDay = new RevisionDay;
                    $offset = $i * $period;
                    $revisionDay->dateDay = new \DateTimeImmutable("today + $offset days");
                    $revisionDay->numDay = $j++;
                    $revisionDay->offsetDay = $offset;
                    $revisionDays[] = $revisionDay;
                }
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

        // Cycle 1
        if ($nbDays >= 1 && $nbDays <= 5) {
            return true;
        }

        $base = $frequency->value;

        // $quotient = intdiv($nbDays, 7);
        // $reste = $nbDays % 7;

        $quotient = intdiv($nbDays, $base);
        $reste = $nbDays % $base;


        // Cycle 2 et 3
        if ($quotient >= 1 && $quotient <= 2) {
            return $reste >= 1 && $reste <= 3;
        }

        // Cycle 4 à 7 inclues
        if ($quotient >= 3 && $quotient <= 6) {
            return $reste === 1 || $reste === 2;
        }

        // Cycle 8 à 12 inclues
        if ($quotient >= 7 && $quotient <= 11) {
            return $reste === 1;
        }

        return false;
    }
}
