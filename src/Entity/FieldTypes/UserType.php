<?php

namespace App\Entity\FieldTypes;

use RuntimeException;

class UserType
{
    private const TEACHER = 'teacher';
    private const STUDENT = 'student';
    private const ALLOWED_VALUES = [self::TEACHER, self::STUDENT];

    private function __construct(private readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (!in_array($value, self::ALLOWED_VALUES, true)) {
            throw new RuntimeException('Invalid user type value');
        }

        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
