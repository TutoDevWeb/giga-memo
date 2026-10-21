<?php

namespace App\Entity;

use App\Entity\Trait\HasUserTrait;
use App\Service\PlanningModel\Frequency;
use App\Service\PlanningModel\Status;
use App\Service\PlanningModel\TimeFrame;
use App\Repository\PlanningRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
class Planning
{
    use HasUserTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: TimeFrame::class)]
    private ?TimeFrame $timeFrame = null;

    #[ORM\Column(enumType: Frequency::class)]
    private ?Frequency $frequency = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateStart = null;

    #[ORM\Column(enumType: Status::class)]
    private ?Status $status = null;

    #[ORM\OneToOne(inversedBy: 'planning')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Faqs $faq = null;

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

    public function getDateStart(): ?\DateTimeImmutable
    {
        return $this->dateStart;
    }

    public function setDateStart(?\DateTimeImmutable $dateStart): static
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getFaq(): ?Faqs
    {
        return $this->faq;
    }

    public function setFaq(?Faqs $faq): static
    {
        $this->faq = $faq;

        return $this;
    }
}
