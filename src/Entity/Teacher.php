<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Teacher extends User
{
    #[ORM\Column(name: 'subject', type: 'string', length: 60, unique: true)]
    private string $subject;

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
                'subject' => $this->subject,
            ],
            parent::toArray(),
        );
    }
}
