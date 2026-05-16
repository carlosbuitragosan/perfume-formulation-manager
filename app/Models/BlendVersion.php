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

    public function ingredientsOrdered()
    {
        return $this->ingredients()
            ->with('material')
            ->get()
            ->sortBy(fn ($ingredient) => $ingredient->pyramidSortValue())
            ->values(); // reindex after sorting
    }

    public function formattedIngredients()
    {
        // Sort ingredients by pyramid order
        $ingredients = $this->ingredientsOrdered();

        // total of drops of PURE material (e.g. x3, 10 drops @25 each =  7.5)
        $pureTotal = $ingredients->sum(function ($ingredient) {
            return $ingredient->drops * ($ingredient->dilution / 100);
        });

        return $ingredients->map(function ($ingredient) use ($pureTotal) {
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
                'variant' => $ingredient->variant(),
            ];
        });
    }

    public function existingBottleAssignments()
    {
        return $this->ingredients
            ->pluck('bottle_id', 'material_id');
    }

    public function rebuildIngredients(User $user, $materials)
    {
        // Preserve bottle assignments (materialId => bottleId) from DB
        $existingBottleIds = $this->existingBottleAssignments();

        // Delete ingredients
        $this->ingredients()->delete();

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
            $this->ingredients()->create([
                'material_id' => $materialId,
                'bottle_id' => $bottleId,
                'drops' => $row['drops'],
                'dilution' => $row['dilution'],
            ]);
        }
    }

    public function versionToClone(int $versionId): BlendVersion
    {
        return $this->versions()
            ->with('ingredients')
            ->findOrFail($versionId);
    }
}
