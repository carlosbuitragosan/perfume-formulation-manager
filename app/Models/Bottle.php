<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bottle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'supplier_name',
        'supplier_url',
        'batch_code',
        'method',
        'plant_part',
        'origin_country',
        'purchase_date',
        'expiry_date',
        'density',
        'volume_ml',
        'price',
        'notes',
    ];

    protected $casts = [
        'is_finished' => 'boolean',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function files()
    {
        return $this->hasMany(BottleFile::class);
    }

    public function usages()
    {
        return $this->hasMany(BlendIngredient::class, 'bottle_id');
    }
}
