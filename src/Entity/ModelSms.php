<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repository\ModelSmsRepository")
 */
class ModelSms
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $libelle;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $message;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $element1;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $element2;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $element3;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $element4;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $element5;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $element6;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $element7;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getElement1(): ?string
    {
        return $this->element1;
    }

    public function setElement1(?string $element1): self
    {
        $this->element1 = $element1;

        return $this;
    }

    public function getElement2(): ?string
    {
        return $this->element2;
    }

    public function setElement2(?string $element2): self
    {
        $this->element2 = $element2;

        return $this;
    }

    public function getElement3(): ?string
    {
        return $this->element3;
    }

    public function setElement3(?string $element3): self
    {
        $this->element3 = $element3;

        return $this;
    }

    public function getElement4(): ?string
    {
        return $this->element4;
    }

    public function setElement4(?string $element4): self
    {
        $this->element4 = $element4;

        return $this;
    }

    public function getElement5(): ?string
    {
        return $this->element5;
    }

    public function setElement5(?string $element5): self
    {
        $this->element5 = $element5;

        return $this;
    }

    public function getElement6(): ?string
    {
        return $this->element6;
    }

    public function setElement6(?string $element6): self
    {
        $this->element6 = $element6;

        return $this;
    }

    public function getElement7(): ?string
    {
        return $this->element7;
    }

    public function setElement7(?string $element7): self
    {
        $this->element7 = $element7;

        return $this;
    }

    public function __toString()
    {
        return $this->libelle;
    }

}
