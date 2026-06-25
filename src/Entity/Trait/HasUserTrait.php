<?php

// src/Entity/Trait/HasUserTrait.php
namespace App\Entity\Trait;


use App\Entity\Users;
use Doctrine\ORM\Mapping as ORM;

trait HasUserTrait
{
    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $user = null;

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): self
    {
        $this->user = $user;

        return $this;
    }
}
