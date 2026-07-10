<?php

namespace App\Entity;

use App\Entity\Trait\HasUserTrait;
use App\Repository\CategoriesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoriesRepository::class)]
class Categories
{
    use HasUserTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'notBlank')]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Faqs>
     */
    #[ORM\OneToMany(targetEntity: Faqs::class, mappedBy: 'category', orphanRemoval: true)]
    private Collection $faqs;

    public function __construct()
    {
        $this->faqs = new ArrayCollection();
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

    /**
     * @return Collection<int, Faqs>
     */
    public function getFaqs(): Collection
    {
        return $this->faqs;
    }

    public function addFaq(Faqs $faq): static
    {
        if (!$this->faqs->contains($faq)) {
            $this->faqs->add($faq);
            $faq->setCategory($this);
        }

        return $this;
    }

    public function removeFaq(Faqs $faq): static
    {
        if ($this->faqs->removeElement($faq)) {
            // set the owning side to null (unless already changed)
            if ($faq->getCategory() === $this) {
                $faq->setCategory(null);
            }
        }

        return $this;
    }
}
