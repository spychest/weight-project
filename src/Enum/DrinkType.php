<?php

namespace App\Enum;

enum DrinkType: string
{
    case WATER = 'eau';
    case SODA = 'soda';
    case JUICE = 'jus';
    case COFFEE = 'café';
    case TEA = 'thé';
    case OTHER = 'autre';
}