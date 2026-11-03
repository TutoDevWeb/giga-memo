<?php

namespace App\Service\PlanningModel;

class RevisionDay
{
    public \DateTimeImmutable $dateDay;

    public int $numDay;

    public int $offsetDay;

    public int $cycleNum;


    public function setDateDay(\DateTimeImmutable $dateDay)
    {
        $this->dateDay = $dateDay;
    }

    public function getDateDay(): \DateTimeImmutable
    {
        return $this->dateDay;
    }

    public function setNumDay(int $numDay)
    {
        $this->numDay = $numDay;
    }

    public function getNumDay(): int
    {
        return $this->numDay;
    }
    public function setOffsetDay(int $offsetDay)
    {
        $this->offsetDay = $offsetDay;
    }

    public function getOffsetDay(): int
    {
        return $this->offsetDay;
    }
}
