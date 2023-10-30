<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'teacher')]
#[ORM\Entity]
class Teacher extends User
{
    #[ORM\Column(name: 'id', type: 'bigint', unique: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'subject', type: 'string', length: 60, unique: true)]
    private string $subject;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function toArray(): array
    {
        return array_merge(
            [
                'id' => $this->id,
                'subject' => $this->subject,
            ],
            parent::toArray(),
        );
    }
}
