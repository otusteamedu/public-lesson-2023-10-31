<?php

namespace App\Doctrine;

use App\Entity\TypeAwareInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class TypeFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (!$targetEntity->reflClass->implementsInterface(TypeAwareInterface::class)) {
            return '';
        }

        return $targetTableAlias.".type = 'student'";
    }
}
