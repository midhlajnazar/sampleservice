<?php

namespace App\Enums;

enum UserRole: string
{
    case Applicant = 'applicant';
    case Admin = 'admin';
}
