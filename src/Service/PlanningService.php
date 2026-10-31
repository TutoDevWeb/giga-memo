<?php

namespace App\Service;

use App\Service\PlanningModel\Frequency;
use App\Service\PlanningModel\RevisionDay;
use App\Service\PlanningModel\TimeFrame;


class PlanningService
{

    public function getCalendrier(
        \DateTimeImmutable $dateStart,
        TimeFrame $timeFrame = TimeFrame::ACQUISITION,
        Frequency $frequency = Frequency::NORMAL
    ): array {

        $revisionDays = [];

        if ($timeFrame === TimeFrame::ACQUISITION) {
            $j = 1;
            for ($i = 0; $i < 180; $i++) {
                $revisionDay = new RevisionDay;
                $revisionDay->dateDay = $dateStart->modify("+{$i} day");

                if ($this->isExecutionDay($dateStart, $revisionDay->dateDay, $frequency)) {
                    $revisionDay->numDay = $j++;
                    $revisionDay->offsetDay = $i;
                    $revisionDays[] = $revisionDay;
                }
            }
        }

        if ($timeFrame === TimeFrame::MAINTENANCE) {

            $cycleDuration = $frequency->getMaintenanceCycleDuration();
            $cycleCount = $frequency->getMaintenanceCycleCount();

            $j = 1;
            for ($i = 0; $i < $cycleCount; $i++) {
                $revisionDay = new RevisionDay;
                $offset = $i * $cycleDuration;
                $revisionDay->dateDay = (clone $dateStart)->modify("+{$offset} day");
                $revisionDay->numDay = $j++;
                $revisionDay->offsetDay = $offset;
                $revisionDays[] = $revisionDay;
            }
        }

        return $revisionDays;
    }



    public function isExecutionDay(
        \DateTimeImmutable $dateStart,
        \DateTimeImmutable $dateDay,
        Frequency $frequency
    ): bool {

        if ($dateStart > $dateDay) return false;

        $interval = $dateStart->diff($dateDay);

        $nbDays = $interval->days;

        // Cycle 1
        if ($nbDays >= 1 && $nbDays <= 5) {
            return true;
        }

        $base = $frequency->getAcquisitionCycleDuration();

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

    public function send(): string
    {
        return 'Send::Envoie des messages';
    }
}
