<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerfumeVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'perfume_id',
        'size',
        'concentration',
    ];

    public function perfume()
    {
        return $this->belongsTo(Perfume::class);
    }
}
