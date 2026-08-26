<?php

namespace App\Entity;

use App\Entity\Trait\HasUserTrait;
use App\Repository\RulesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RulesRepository::class)]
class Rules
{
    use HasUserTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'rules')]
    #[Assert\NotNull(message: 'notNull')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Faqs $faq = null;

    /**
     * @var Collection<int, Couples>
     */
    #[ORM\ManyToMany(targetEntity: Couples::class, mappedBy: 'rules')]
    private Collection $couples;

    public function __construct()
    {
        $this->couples = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

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

    /**
     * @return Collection<int, Couples>
     */
    public function getCouples(): Collection
    {
        return $this->couples;
    }

    public function addCouple(Couples $couple): static
    {
        if (!$this->couples->contains($couple)) {
            $this->couples->add($couple);
            $couple->addRule($this);
        }

        return $this;
    }

    public function removeCouple(Couples $couple): static
    {
        if ($this->couples->removeElement($couple)) {
            $couple->removeRule($this);
        }

        return $this;
    }
}
