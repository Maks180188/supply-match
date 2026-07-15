<?php

namespace App\Enums;

enum UserRole: string
{
    case Buyer = 'buyer';
    case Supplier = 'supplier';
    case Admin = 'admin';
}
