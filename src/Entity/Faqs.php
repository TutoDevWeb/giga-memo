<?php

namespace App\Entity;

use App\Model\LayerTypeEnum;
use App\Repository\FaqsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FaqsRepository::class)]
class Faqs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'faqRequired')]
    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Couples>
     */
    #[ORM\OneToMany(targetEntity: Couples::class, mappedBy: 'faq', orphanRemoval: true)]
    private Collection $couples;

    #[ORM\Column(enumType: LayerTypeEnum::class, options: ['default' => LayerTypeEnum::LAYER_ONE->value])]
    private ?LayerTypeEnum $layerType = LayerTypeEnum::LAYER_ONE;

    #[ORM\Column(nullable: true)]
    private ?\DateInterval $duration = null;

    /**
     * @var Collection<int, Rules>
     */
    #[ORM\OneToMany(targetEntity: Rules::class, mappedBy: 'faq', orphanRemoval: true)]
    private Collection $rules;

    #[ORM\ManyToOne(inversedBy: 'faqs')]
    private ?Categories $category = null;

    public function __construct()
    {
        $this->couples = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable('now');
        $this->rules = new ArrayCollection();
    }

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

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreateAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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
            $couple->setFaq($this);
        }

        return $this;
    }

    public function removeCouple(Couples $couple): static
    {
        if ($this->couples->removeElement($couple)) {
            // set the owning side to null (unless already changed)
            if ($couple->getFaq() === $this) {
                $couple->setFaq(null);
            }
        }

        return $this;
    }

    public function getLayerType(): ?LayerTypeEnum
    {
        return $this->layerType;
    }

    public function setLayerType(LayerTypeEnum $layerType): static
    {
        $this->layerType = $layerType;

        return $this;
    }

    public function getDuration(): ?\DateInterval
    {
        return $this->duration;
    }

    public function setDuration(?\DateInterval $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * @return Collection<int, Rules>
     */
    public function getRules(): Collection
    {
        return $this->rules;
    }

    public function addRule(Rules $rule): static
    {
        if (!$this->rules->contains($rule)) {
            $this->rules->add($rule);
            $rule->setFaq($this);
        }

        return $this;
    }

    public function removeRule(Rules $rule): static
    {
        if ($this->rules->removeElement($rule)) {
            // set the owning side to null (unless already changed)
            if ($rule->getFaq() === $this) {
                $rule->setFaq(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?Categories
    {
        return $this->category;
    }

    public function setCategory(?Categories $category): static
    {
        $this->category = $category;

        return $this;
    }
}
