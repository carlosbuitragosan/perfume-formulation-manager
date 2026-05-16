<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blend extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function versions()
    {
        return $this->hasMany(BlendVersion::class);
    }

    public function currentVersion()
    {
        return $this->versions()
            ->where('version', '1.0')
            ->with(['ingredients.material'])
            ->first(); // return version or null
    }

    public function CurrentVersionOrFail()
    {
        return $this->versions()
            ->where('version', '1.0')
            ->with(['ingredients.material'])
            ->firstOrFail(); // return version or 404
    }

    public static function createBlendWithIngredients(array $data, int $userId)
    {
        $blend = self::create([
            'user_id' => $userId,
            'name' => trim($data['name']),
        ]);

        $version = $blend->versions()->create([
            'version' => $blend->nextVersionNumber(),
        ]);

        // Create ingredients for this version
        foreach ($data['materials'] as $row) {
            // Find available bottles for each material
            $availableBottlesByMaterial = Bottle::where('user_id', $userId)
                ->where('material_id', $row['material_id'])
                ->where('is_finished', false)
                ->take(2) // we only need to check if 1 or more are available
                ->get();

            $bottleId = null;

            // Assign bottle if only one exists
            if ($availableBottlesByMaterial->count() === 1) {
                $bottleId = $availableBottlesByMaterial->first()->id;
            }

            $version->ingredients()->create([
                'material_id' => $row['material_id'],
                'bottle_id' => $bottleId,
                'drops' => $row['drops'],
                'dilution' => $row['dilution'],
            ]);
        }

        return $blend;
    }

    public function createVersionWithIngredients(array $data)
    {
        $version = $this->versions()->create([
            'version' => $this->nextVersionNumber(),
        ]);

        // Create ingredients for this version
        foreach ($data['materials'] as $row) {
            // Find available bottles for each material
            $availableBottlesByMaterial = Bottle::where('user_id', $this->user_id)
                ->where('material_id', $row['material_id'])
                ->where('is_finished', false)
                ->take(2) // we only need to check if 1 or more are available
                ->get();

            $bottleId = null;

            // Assign bottle if only one exists
            if ($availableBottlesByMaterial->count() === 1) {
                $bottleId = $availableBottlesByMaterial->first()->id;
            }

            $version->ingredients()->create([
                'material_id' => $row['material_id'],
                'bottle_id' => $bottleId,
                'drops' => $row['drops'],
                'dilution' => $row['dilution'],
            ]);
        }

        return $version;
    }

    // Normalize blend name (e.g. "dark floral" => "Dark Floral")
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucwords(strtolower($value))
        );
    }

    public function nextVersionNumber()
    {
        $latestVersion = $this->versions()->latest('created_at')->first();
        $latestVersionNumber = $latestVersion ? (int) $latestVersion->version : 0;

        return $latestVersionNumber + 1;
    }

    public function versionToClone(int $versionId): BlendVersion
    {
        return $this->versions()
            ->with('ingredients')
            ->findOrFail($versionId);
    }
}
