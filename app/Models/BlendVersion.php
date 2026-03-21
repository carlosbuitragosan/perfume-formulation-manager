<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlendVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'blend_id',
        'version',
    ];

    public function blend()
    {
        return $this->belongsTo(Blend::class);
    }

    public function ingredients()
    {
        return $this->hasMany(BlendIngredient::class);
    }
}
