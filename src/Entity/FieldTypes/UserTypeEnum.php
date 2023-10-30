<?php

namespace App\Entity\FieldTypes;

enum UserTypeEnum: string
{
    case Teacher = 'teacher';
    case Student = 'student';
}
