<?php

namespace App\Http\Controllers;

use App\Models\BlendIngredient;
use App\Models\Material;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaterialController extends Controller
{
    // Allowed vocabularies
    private array $familiesAllowed;

    private array $functionsAllowed;

    private array $safetyAllowed;

    private array $effectsAllowed;

    public function __construct()
    {
        $this->familiesAllowed = config('materials.families', []);
        $this->functionsAllowed = config('materials.functions', []);
        $this->safetyAllowed = config('materials.safety', []);
        $this->effectsAllowed = config('materials.effects', []);
    }

    public function index(Request $request)
    {
        return view('materials.index');
    }

    // Create form
    public function create()
    {
        return view('materials.create');
    }

    // Shared validation
    private function validateMaterials(Request $request, ?Material $material = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'botanical' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],

            'pyramid' => ['nullable', 'array'],
            'pyramid.*' => ['in:top,heart,base'],

            'families' => ['nullable', 'array'],
            'families.*' => [Rule::in($this->familiesAllowed)],

            'functions' => ['nullable', 'array'],
            'functions.*' => [Rule::in($this->functionsAllowed)],

            'safety' => ['nullable', 'array'],
            'safety.*' => [Rule::in($this->safetyAllowed)],

            'effects' => ['nullable', 'array'],
            'effects.*' => [Rule::in($this->effectsAllowed)],

            'ifra_max_pct' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $needle = mb_strtolower(trim($data['name']));

        $query = Material::where('user_id', auth()->id())
            ->whereRaw('LOWER(name) = ?', [$needle]);

        if ($material) {
            $query->where('id', '!=', $material->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => ['You already have a material with that name.'],
            ]);
        }

        return $data;
    }

    // Store
    public function store(Request $request)
    {
        $data = $this->validateMaterials($request);

        $data['name'] = trim($data['name']);
        if (! empty($data['botanical'])) {
            $data['botanical'] = trim($data['botanical']);
        }

        $data['user_id'] = auth()->id();

        $material = Material::create($data);

        return redirect(route('materials.index').'#material-'.$material->id)
            ->with('success', "{$material->name} added")
            ->with('material_id', $material->id);
    }

    // Edit form
    public function edit(Material $material)
    {
        return view('materials.edit', compact('material'));
    }

    // Update
    public function update(Request $request, Material $material)
    {
        $data = $this->validateMaterials($request, $material);

        $data['name'] = trim($data['name']);
        if (! empty($data['botanical'])) {
            $data['botanical'] = trim($data['botanical']);
        }
        $material->update($data);

        return redirect(route('materials.index').'#material-'.$material->id)
            ->with('success', "{$material->name} updated")
            ->with('material_id', $material->id);
    }

    // material show page
    public function show(Material $material)
    {
        // GET request, data is in url
        $blendIngredientId = request('ingredient');
        $selectedBottleId = null;
        $blendIngredient = null;

        if ($blendIngredientId) {
            $blendIngredient = BlendIngredient::find($blendIngredientId);
            $selectedBottleId = $blendIngredient?->bottle_id;
        }

        $bottles = $material->bottlesFor($blendIngredient);

        return view('materials.show', compact(
            'material',
            'selectedBottleId',
            'blendIngredient',
            'bottles'
        ));
    }

    public function destroy(Material $material)
    {
        try {
            $material->delete();

            return redirect()
                ->route('materials.index')
                ->with('success', "{$material->name} deleted");
        } catch (QueryException $e) {
            return redirect()
                ->route('materials.edit', $material)
                ->with('error', "{$material->name} is in use and cannot be deleted");
        }

    }
}
