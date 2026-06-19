<?php

namespace App\Enums;

enum PyramidVariant: string
{
    case TOP = 'top';
    case HEART = 'heart';
    case BASE = 'base';
    case TOP_HEART = 'top-heart';
    case HEART_BASE = 'heart-base';
    case ALCOHOL = 'alcohol';
    case ALL = 'all';

    public function colorClass(): string
    {
        return match ($this) {
            self::TOP => 'bg-purple-600 text-white',
            self::HEART => 'bg-green-700 text-white',
            self::BASE => 'bg-red-700 text-white',
            self::TOP_HEART => 'bg-cyan-500 text-white',
            self::HEART_BASE => 'bg-orange-500 text-white',
            self::ALCOHOL => 'bg-slate-700 text-white',
            self::ALL => 'bg-[#D4AF37] text-black',
        };
    }
}
