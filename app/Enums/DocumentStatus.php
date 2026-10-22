<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Superseded = 'superseded';
}
