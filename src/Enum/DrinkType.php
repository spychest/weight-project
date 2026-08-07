<?php

namespace App\Enum;

enum DrinkType: string
{
    case WATER = 'water';
    case SODA = 'soda';
    case JUICE = 'juice';
    case COFFEE = 'coffee';
    case TEA = 'tea';
    case OTHER = 'other';
}