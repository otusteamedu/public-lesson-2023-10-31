<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'student')]
#[ORM\Entity]
class Student extends User
{
    #[ORM\Column(name: 'id', type: 'bigint', unique: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'grade_book_number', type: 'string', length: 10, unique: true)]
    private string $gradeBookNumber;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getGradeBookNumber(): string
    {
        return $this->gradeBookNumber;
    }

    public function setGradeBookNumber(string $gradeBookNumber): void
    {
        $this->gradeBookNumber = $gradeBookNumber;
    }

    public function toArray(): array
    {
        return array_merge(
            [
                'id' => $this->id,
                'grade_book_number' => $this->gradeBookNumber,
            ],
            parent::toArray(),
        );
    }
}
