<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'botanical',
        'pyramid',
        'families',
        'functions',
        'safety',
        'effects',
        'ifra_max_pct',
        'notes',
    ];

    protected $casts = [
        'pyramid' => 'array',
        'families' => 'array',
        'functions' => 'array',
        'safety' => 'array',
        'effects' => 'array',
        'ifra_max_pct' => 'float',
    ];

    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $needle = mb_strtolower($term);

        return $query->where(function ($q) use ($needle) {
            // Text fields
            $q->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"])
                ->orWhereRaw('LOWER(COALESCE(botanical, "")) LIKE ?', ["%{$needle}%"])
                ->orWhereRaw('LOWER(COALESCE(notes, "")) LIKE ?', ["%{$needle}%"]);

            if (str_contains($needle, 'ifra')) {
                $q->orWhereNotNull('ifra_max_pct');
            }

            // JSON arrays — exact contains (when exact term) + partial fallback via JSON_EXTRACT
            $jsonLike = function ($col) use ($q, $needle) {
                // exact (case-insensitive because we store lowercase tags)
                $q->orWhereJsonContains($col, $needle);
                // partial match fallback (works on SQLite/MySQL)
                $q->orWhereRaw('LOWER(COALESCE(JSON_EXTRACT('.$col.', "$"), "")) LIKE ?', ["%{$needle}%"]);
            };

            $jsonLike('pyramid');
            $jsonLike('families');
            $jsonLike('functions');
            $jsonLike('effects');
            $jsonLike('safety');
        });
    }

    public function bottles()
    {
        return $this->hasMany(Bottle::class);
    }

    public function blendIngredients()
    {
        return $this->hasMany(BlendIngredient::class);
    }
}
