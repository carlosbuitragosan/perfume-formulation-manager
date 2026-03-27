<?php

namespace App\Http\Controllers;

use App\Enums\ExtractionMethod;
use App\Models\BlendIngredient;
use App\Models\Bottle;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum as EnumRule;

class BottleController extends Controller
{
    // show the form to create a bottle
    public function create(Material $material)
    {
        abort_if($material->user_id !== auth()->id(), 404);

        return view('bottles.create', compact('material'));
    }

    public function store(Material $material, Request $request)
    {
        abort_if($material->user_id !== auth()->id(), 404);

        $data = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'supplier_url' => ['nullable', 'url'],
            'batch_code' => ['nullable', 'string', 'max:255'],
            'method' => ['required', new EnumRule(ExtractionMethod::class)],
            'plant_part' => ['nullable', 'string', 'max:255'],
            'origin_country' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'density' => ['nullable', 'numeric', 'between:0,2'],
            'volume_ml' => ['nullable', 'numeric', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:5120'],
        ]);

        // Get the uploaded files
        $files = $request->file('files', []);

        // Remove the files from the request ($data)
        unset($data['files']);

        // Assign logged in user to bottle.user_id
        $data['user_id'] = auth()->id();

        // Create bottle
        $bottle = $material->bottles()->create($data);

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();

            // Store files in 'public' disk (storage/app/public)
            $storedPath = $file->store("bottles/{$bottle->id}", 'public');
            // Create files
            $bottle->files()->create([
                'user_id' => auth()->id(),
                'path' => $storedPath,
                'original_name' => $originalName,
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'note' => null,
            ]);
        }

        $blendIngredientId = $request->input('ingredient');
        $blendIngredient = BlendIngredient::find($blendIngredientId);

        if ($blendIngredient && $blendIngredient->bottle_id == null) {

            $blendIngredient->update([
                'bottle_id' => $bottle->id,
            ]);

            $blend = $blendIngredient->BlendVersion?->blend;

            return redirect()->route('blends.show', $blend);
        }

        return redirect()->route('materials.show', $material);
    }

    public function edit(Bottle $bottle)
    {
        abort_if($bottle->user_id !== auth()->id(), 404);

        return view('bottles.edit', compact('bottle'));
    }

    public function update(Request $request, Bottle $bottle)
    {
        abort_if($bottle->user_id !== auth()->id(), 404);

        $data = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'supplier_url' => ['nullable', 'url'],
            'batch_code' => ['nullable', 'string', 'max:255'],
            'method' => ['required', new EnumRule(ExtractionMethod::class)],
            'plant_part' => ['nullable', 'string', 'max:255'],
            'origin_country' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'density' => ['nullable', 'numeric', 'between:0,2'],
            'volume_ml' => ['nullable', 'numeric', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'remove_files' => ['sometimes', 'array'],
            'remove_files.*' => ['integer'],
            'files' => ['sometimes', 'array'],
            'files.*' => ['file', 'max:5120'],
        ]);

        $removeIds = $data['remove_files'] ?? [];
        $newFiles = $request->file('files', []);
        unset($data['remove_files']);

        // update bottle fields
        $bottle->update($data);

        // delete files
        if (! empty($removeIds)) {
            $files = $bottle->files()
                ->whereIn('id', $removeIds)
                ->get();

            foreach ($files as $file) {
                // delete physical file from disk
                Storage::disk('public')->delete($file->path);
                // delete DB row
                $file->delete();
            }
        }

        // store newly uploaded files
        foreach ($newFiles as $file) {
            $originalName = $file->getClientOriginalName();
            $storedPath = $file->store("bottles/{$bottle->id}", 'public');

            $bottle->files()->create([
                'user_id' => auth()->id(),
                'path' => $storedPath,
                'original_name' => $originalName,
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'note' => null,
            ]);
        }

        $material = $bottle->material;

        return redirect(route('materials.show', $material).'#bottle-'.$bottle->id)
            ->with('ok', 'Bottle updated');
    }

    public function finish(Bottle $bottle)
    {
        abort_if($bottle->user_id !== auth()->id(), 404);

        $bottle->is_finished = true;
        $bottle->save();

        return redirect()
            ->route('materials.show', $bottle->material_id)
            ->with('ok', 'Bottle marked as finished')
            ->with('bottle_id', $bottle->id);
    }

    public function destroy(Request $request, Bottle $bottle)
    {
        abort_if($bottle->user_id !== auth()->id(), 404);

        $material = $bottle->material;

        if ($bottle->usages()->exists()) {
            return redirect()
                ->route('materials.show', $material)
                ->with('error', 'This bottle is in use and cannot be deleted.')
                ->with('bottle_id', $bottle->id);
        }

        // delete physical files:
        foreach ($bottle->files as $file) {
            Storage::disk('public')->delete($file->path);
        }

        // delete the folder too
        Storage::disk('public')->deleteDirectory("bottles/{$bottle->id}");

        // delete the bottle
        $bottle->delete();

        return redirect()
            ->route('materials.show', $material)
            ->with('ok', 'Bottle deleted')
            ->with('bottle_id', $bottle->id);

    }

    public function unfinish(Bottle $bottle)
    {
        $bottle->is_finished = false;
        $bottle->save();

        return redirect(route('materials.show', $bottle->material).'#bottle-'.$bottle->id)
            ->with('ok', 'Bottle unmarked as finished')
            ->with('bottle_id', $bottle->id);
    }
}
