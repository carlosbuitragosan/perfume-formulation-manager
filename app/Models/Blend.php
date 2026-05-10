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
            'version' => '1.0',
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
            'version' => '2.0',
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

    public function updateName(string $name)
    {
        // Update blend name
        $this->update([
            'name' => $name,
        ]);
        $this->touch();
    }

    public function existingBottleAssignments($version)
    {
        return $version->ingredients
            ->pluck('bottle_id', 'material_id');
    }

    public function rebuildIngredients(User $user, $version, $materials)
    {
        // Preserve bottle assignments (materialId => bottleId) from DB
        $existingBottleIds = $this->existingBottleAssignments($version);

        // Delete ingredients
        $version->ingredients()->delete();

        // Get the all materials from the request
        $incomingMaterialIds = collect($materials)
            ->pluck('material_id');

        // Find all available bottles for all materials in the request (materialId => collection of bottles)
        $availableBottlesByMaterial = Bottle::where('user_id', $user->id)
            ->where('is_finished', false)
            ->WhereIn('material_id', $incomingMaterialIds)
            ->get()
            ->groupBy('material_id');

        foreach ($materials as $row) {
            $materialId = $row['material_id'];
            $bottleId = null;

            // If ingredient being added already existed in the blend...
            if ($existingBottleIds->has($materialId)) {
                // Fetch it from the list and assign a bottle id
                $bottleId = $existingBottleIds[$materialId];
            } else {
                // Fetch available bottles from newly added ingredient
                $materialBottles = $availableBottlesByMaterial->get($materialId, collect());
                if ($materialBottles->count() === 1) {
                    // Assign the bottle ID
                    $bottleId = $materialBottles->first()->id;
                }
            }
            // Create a new ingredient from existing ones and newly added
            $version->ingredients()->create([
                'material_id' => $materialId,
                'bottle_id' => $bottleId,
                'drops' => $row['drops'],
                'dilution' => $row['dilution'],
            ]);
        }
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucwords(strtolower($value))
        );
    }
}
