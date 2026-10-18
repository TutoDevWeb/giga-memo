<?php

namespace App\Entity;

use App\Model\Frequency;
use App\Model\TimeFrame;
use App\Repository\PlanningRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: TimeFrame::class)]
    private ?TimeFrame $timeFrame = null;

    #[ORM\Column(enumType: Frequency::class)]
    private ?Frequency $frequency = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimeFrame(): ?TimeFrame
    {
        return $this->timeFrame;
    }

    public function setTimeFrame(TimeFrame $timeFrame): static
    {
        $this->timeFrame = $timeFrame;

        return $this;
    }

    public function getFrequency(): ?Frequency
    {
        return $this->frequency;
    }

    public function setFrequency(Frequency $frequency): static
    {
        $this->frequency = $frequency;

        return $this;
    }
}
