<?php

namespace App\Doctrine\Types;

use App\Entity\FieldTypes\UserType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use RuntimeException;

class UserTypeType extends Type
{
    public function convertToPHPValue($value, AbstractPlatform $platform): ?UserType
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            try {
                return UserType::fromString($value);
            } catch (RuntimeException) {
            }
        }

        throw ConversionException::conversionFailed($value, $this->getName());
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof UserType) {
            return $value->getValue();
        }

        throw ConversionException::conversionFailed($value, $this->getName());
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function getName()
    {
        return 'userType';
    }
}
