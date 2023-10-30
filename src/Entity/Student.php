<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'student')]
#[ORM\Entity]
class Student extends User
{
    #[ORM\Column(name: 'grade_book_number', type: 'string', length: 10, unique: true)]
    private string $gradeBookNumber;

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
                'grade_book_number' => $this->gradeBookNumber,
            ],
            parent::toArray(),
        );
    }
}
