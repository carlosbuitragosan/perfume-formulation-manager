<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        // Overview: search materials by name, botanical, notes, families effects, etc.

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

    public function bottlesFor(?BlendIngredient $blendIngredient)
    {
        return $this->bottles()
            // Add a virtual "is_used" flag: true if this bottle is referenced by any blend ingredients
            ->withExists(['usages as is_used'])
            // if blendIngredient exists (in the url) apply filter (not finished), else do nothing
            ->when($blendIngredient, function ($query) {
                $query->where('is_finished', false);
            })
            // Sort used bottles first (true = 1, false = 0)
            ->orderByDesc('is_used')
            // Then sort most recently updated bottles first within each group
            ->orderBy('is_finished')
            // Then sort most recently updated bottles first within each group
            ->orderByDesc('updated_at')
            ->with('files')
            ->get();
    }

    // Query builder extension, internall Material::query()->forUserOrdered
    public function scopeForUserOrdered($query, $userId)
    {
        return $query
            ->where('user_id', $userId)
            ->orderBy('name');

    }

    // Store material names caplitaized
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucwords(strtolower($value))
        );
    }
}
