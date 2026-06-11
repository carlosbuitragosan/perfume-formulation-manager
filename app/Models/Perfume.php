<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perfume extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function blendVersion()
    {
        return $this->belongsTo(BlendVersion::class);
    }

    public function versions()
    {
        return $this->hasMany(PerfumeVersion::class);
    }

    // Normalize perfume name (e.g. "dark floral" => "Dark Floral")
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucwords(strtolower($value))
        );
    }
}
