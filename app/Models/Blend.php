<?php

namespace App\Models;

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

    public static function createWithIngredients(array $data, int $userId)
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

    public function formattedIngredients($version)
    {
        if (! $version) {
            return collect();
        }

        // total of drops of PURE material (e.g. x3, 10 drops @25 each =  7.5)
        $pureTotal = $version->ingredients->sum(function ($ingredient) {
            return $ingredient->drops * ($ingredient->dilution / 100);
        });

        return $version->ingredients->map(function ($ingredient) use ($pureTotal) {
            // pure amount of material
            $pure = $ingredient->drops * ($ingredient->dilution / 100); // 2.5
            // percentage of pure material in the blend
            $purePercentage = $pureTotal > 0 ? ($pure / $pureTotal) * 100 : 0; // 33.33%

            // Returns a collection of arrays
            return [
                'blend_ingredient_id' => $ingredient->id,
                'material_id' => $ingredient->material_id,
                'material_name' => $ingredient->material->name,
                'bottle_id' => $ingredient->bottle_id,
                'drops' => (string) $ingredient->drops,
                'dilution' => $ingredient->dilution.'%',
                'pure_pct' => rtrim(rtrim(number_format($purePercentage, 2, '.', ''), '0'), '.').'%',
            ];
        });
    }

    public function updateName(string $name)
    {
        // Update blend name
        $this->update([
            'name' => $name,
        ]);
    }

    public function existingBottleAssignments($version)
    {
        return $version->ingredients
            ->pluck('bottle_id', 'material_id');
    }

    public function rebuildIngredients($version, $materials)
    {
        // Preserve bottle assignments (materialId => bottleId) from DB
        $existingBottleIds = $this->existingBottleAssignments($version);

        // Delete ingredients
        $version->ingredients()->delete();

        // Get the all materials from the request
        $incomingMaterialIds = collect($materials)
            ->pluck('material_id');

        // Find all available bottles for all materials in the request (materialId => collection of bottles)
        $availableBottlesByMaterial = Bottle::where('user_id', auth()->id())
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
}
