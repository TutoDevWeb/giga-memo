<?php

namespace App\Entity;

use App\Repository\CouplesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouplesRepository::class)]
class Couples
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $num = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'couples')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Faqs $faq = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $question = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reponse = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $todoRun = true;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $todoReview = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $regle = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $selectReview = null;

    /**
     * @var Collection<int, Images>
     */
    #[ORM\OneToMany(targetEntity: Images::class, mappedBy: 'couple', orphanRemoval: true, cascade: ['persist'])] // J'ai ajouté à la maim le cascade persist
    private Collection $images;

    /**
     * @var Collection<int, Rules>
     */
    #[ORM\ManyToMany(targetEntity: Rules::class, inversedBy: 'couples')]
    private Collection $rules;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNum(): ?int
    {
        return $this->num;
    }

    public function setNum(int $num): static
    {
        $this->num = $num;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now');
        $this->images = new ArrayCollection();
        $this->rules = new ArrayCollection();
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(?string $reponse): static
    {
        $this->reponse = $reponse;

        return $this;
    }

    public function isTodoRun(): ?bool
    {
        return $this->todoRun;
    }

    public function setTodoRun(bool $todoRun): static
    {
        $this->todoRun = $todoRun;

        return $this;
    }

    public function isTodoReview(): ?bool
    {
        return $this->todoReview;
    }

    public function setTodoReview(bool $todoReview): static
    {
        $this->todoReview = $todoReview;

        return $this;
    }

    public function getRegle(): ?string
    {
        return $this->regle;
    }

    public function setRegle(?string $regle): static
    {
        $this->regle = $regle;

        return $this;
    }

    public function isSelectReview(): ?bool
    {
        return $this->selectReview;
    }

    public function setSelectReview(bool $selectReview): static
    {
        $this->selectReview = $selectReview;

        return $this;
    }

    /**
     * @return Collection<int, Images>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Images $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setCouple($this);
        }

        return $this;
    }

    public function removeImage(Images $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getCouple() === $this) {
                $image->setCouple(null);
            }
        }

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
        }

        return $this;
    }

    public function removeRule(Rules $rule): static
    {
        $this->rules->removeElement($rule);

        return $this;
    }
}
