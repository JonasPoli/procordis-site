<?php

namespace App\Entity;

use App\Repository\SystemVariableRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SystemVariableRepository::class)]
class SystemVariable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $variableKey = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $variableValue = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVariableKey(): ?string
    {
        return $this->variableKey;
    }

    public function setVariableKey(string $variableKey): static
    {
        $this->variableKey = $variableKey;

        return $this;
    }

    public function getVariableValue(): ?string
    {
        return $this->variableValue;
    }

    public function setVariableValue(?string $variableValue): static
    {
        $this->variableValue = $variableValue;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
