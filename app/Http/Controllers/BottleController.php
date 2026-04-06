<?php

namespace App\Http\Controllers;

use App\Http\Requests\Bottle\StoreBottleRequest;
use App\Http\Requests\Bottle\UpdateBottleRequest;
use App\Models\BlendIngredient;
use App\Models\Bottle;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BottleController extends Controller
{
    // show the form to create a bottle
    public function create(Material $material)
    {
        $this->authorize('view', $material);

        return view('bottles.create', compact('material'));
    }

    public function store(Material $material, StoreBottleRequest $request)
    {
        $this->authorize('view', $material);

        $data = $request->validated();

        // Get the uploaded files
        $files = $request->file('files', []);

        // Remove the files from the request ($data)
        unset($data['files']);

        // Assign logged in user to bottle.user_id
        $data['user_id'] = auth()->id();

        // Create bottle
        $bottle = $material->bottles()->create($data);

        // Store files
        $this->storeFiles($bottle, $files);

        // Get the blend
        $blendIngredient = null;
        $blendIngredientId = $data['ingredient'] ?? null;
        if ($blendIngredientId) {
            $blendIngredient = BlendIngredient::find($blendIngredientId);
        }

        // Check blend ingredient
        if ($blendIngredient && $bottle->assignToBlendIngredient($blendIngredient)) {

            $blend = $blendIngredient->blendVersion?->blend;

            return redirect()->route('blends.show', $blend)
                ->with('success', "Bottle assigned to {$material->name}")
                ->with('blend_id', $blend->id);
        }

        return redirect()->route('materials.show', $material)
            ->with('success', 'Bottle added');
    }

    public function edit(Bottle $bottle)
    {
        $this->authorize('update', $bottle);

        return view('bottles.edit', compact('bottle'));
    }

    public function update(UpdateBottleRequest $request, Bottle $bottle)
    {
        $this->authorize('update', $bottle);

        $data = $request->validated();

        // Extract file deletion instructions
        $removeFileIds = $data['remove_files'] ?? [];

        // Extract new uploaded files
        $newFiles = $request->file('files', []);

        // Remove non-DB field before update
        unset($data['remove_files']);

        // update bottle fields
        $bottle->update($data);

        // delete files
        $this->deleteFiles($bottle, $removeFileIds);

        // store newly uploaded files
        $this->storeFiles($bottle, $newFiles);

        $material = $bottle->material;

        return redirect(route('materials.show', $material).'#bottle-'.$bottle->id)
            ->with('success', 'Bottle updated')
            ->with('bottle_id', $bottle->id);
    }

    // Delete files helper
    protected function deleteFiles(Bottle $bottle, array $fileIds): void
    {
        if (empty($fileIds)) {
            return;
        }
        $files = $bottle->files()
            ->whereIn('id', $fileIds)
            ->get();

        foreach ($files as $file) {
            // delete physical file from disk
            Storage::disk('public')->delete($file->path);
            // delete DB row
            $file->delete();
        }
    }

    // Store files helper
    protected function storeFiles(Bottle $bottle, array $files): void
    {
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
    }

    // Mark bottle as finished
    public function finish(Bottle $bottle)
    {
        $this->authorize('update', $bottle);

        $bottle->update(['is_finished' => true]);

        return redirect()
            ->route('materials.show', $bottle->material_id)
            ->with('success', 'Bottle marked as finished')
            ->with('bottle_id', $bottle->id);
    }

    // Unmark bottle as finished
    public function unfinish(Bottle $bottle)
    {
        $this->authorize('update', $bottle);

        $bottle->update(['is_finished' => false]);

        return redirect(route('materials.show', $bottle->material).'#bottle-'.$bottle->id)
            ->with('success', 'Bottle unmarked as finished')
            ->with('bottle_id', $bottle->id);
    }

    // Delete bottle
    public function destroy(Bottle $bottle)
    {
        $this->authorize('delete', $bottle);

        $material = $bottle->material;

        if ($bottle->usages()->exists()) {
            return redirect()
                ->route('materials.show', $material)
                ->with('error', 'This bottle is in use and cannot be deleted.')
                ->with('bottle_id', $bottle->id);
        }

        // Delete  files & bottle (b->files()->pluc(id) run a query like SELECT id FROM, unless b->files->pluck(id) which runs SELECT * FROM)
        $this->deleteFiles($bottle, $bottle->files()->pluck('id')->toArray());
        // delete the folder too
        Storage::disk('public')->deleteDirectory("bottles/{$bottle->id}");
        // delete the bottle
        $bottle->delete();

        return redirect()
            ->route('materials.show', $material)
            ->with('success', 'Bottle deleted');
    }
}
