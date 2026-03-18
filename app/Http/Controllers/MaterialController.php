<?php

namespace App\Http\Controllers;

use App\Models\BlendVersionIngredient;
use App\Models\Material;
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
            ->with('ok', 'Material added');
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
            ->with('ok', 'Material updated');
    }

    // material show page
    public function show(Material $material)
    {
        $material->load(['bottles.files']);
        $usedBottleIds = BlendVersionIngredient::pluck('bottle_id')
            ->filter()
            ->unique();

        return view('materials.show', compact('material', 'usedBottleIds'));
    }

    public function destroy(Material $material)
    {
        $material->delete();

        return redirect()
            ->route('materials.index')
            ->with('ok', 'Material deleted');

    }
}
