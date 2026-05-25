<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perfume extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'size',
        'concentration',
        'carrier_type',
    ];

    public function version()
    {
        return $this->belongsTo(BlendVersion::class);
    }
}
