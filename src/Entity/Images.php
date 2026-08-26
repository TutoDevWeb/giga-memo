<?php

namespace App\Entity;

use App\Entity\Trait\HasUserTrait;
use App\Repository\ImagesRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ImagesRepository::class)]
class Images
{
    use HasUserTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[Assert\NotNull(message: 'notNull')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] // Le onDelete: 'CASCADE' a été ajouté à la main
    private ?Couples $couple = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCouple(): ?Couples
    {
        return $this->couple;
    }

    public function setCouple(?Couples $couple): static
    {
        $this->couple = $couple;

        return $this;
    }
}
