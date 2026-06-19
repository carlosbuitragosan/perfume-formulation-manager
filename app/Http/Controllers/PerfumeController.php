<?php

namespace App\Http\Controllers;

use App\Http\Requests\Perfume\StorePerfumeRequest;
use App\Http\Requests\Perfume\UpdatePerfumeRequest;
use App\Models\BlendVersion;
use App\Models\Perfume;
use Illuminate\Http\Request;

class PerfumeController extends Controller
{
    public function index()
    {
        $perfumes = Perfume::whereHas('blendVersion.blend', function ($query) {
            $query->where('user_id', auth()->id());
        })->get();

        return view('perfumes.index', compact('perfumes'));
    }

    public function create(Request $request, BlendVersion $blendVersion)
    {
        $this->authorize('view', $blendVersion);

        $problems = $blendVersion->perfumeCreationProblems();

        if (! empty($problems)) {
            return redirect()
                ->route('blends.show', $blendVersion->blend)
                ->withFragment('version-'.$blendVersion->id)
                ->with('version_id', $blendVersion->id)
                ->with('alerts', $problems);
        }

        return view('perfumes.create', compact('blendVersion'));
    }

    public function store(StorePerfumeRequest $request, BlendVersion $blendVersion)
    {
        $this->authorize('view', $blendVersion);

        $validated = $request->validated();

        $perfume = $blendVersion->perfumes()->create([
            'name' => $validated['name'],
        ]);

        $perfumeVersion = $perfume->versions()->create([
            'size' => $validated['size'],
            'concentration' => $validated['concentration'],
        ]);

        return redirect()
            ->route('perfumes.show', $perfume)
            ->withFragment('version-'.$perfumeVersion->id)
            ->with('success', 'Perfume added')
            ->with('version_id', $perfumeVersion->id);
    }

    public function show(Perfume $perfume)
    {
        $this->authorize('view', $perfume);

        // returns a  collection to hold data for each perfume version
        $perfumeVersionBreakdowns = $perfume->versions->map(
            fn ($perfumeVersion) => $perfumeVersion->breakdown()
        );

        return view('perfumes.show', compact('perfume', 'perfumeVersionBreakdowns'));
    }

    public function update(UpdatePerfumeRequest $request, Perfume $perfume)
    {
        $this->authorize('update', $perfume);

        $validated = $request->validated();

        $perfume->update([
            'name' => $validated['name'],
        ]);

        // update timestamp
        $perfume->touch();

        return redirect()
            ->route('perfumes.index');
    }

    public function destroy(Perfume $perfume)
    {
        $this->authorize('delete', $perfume);

        $perfume->delete();

        return redirect()
            ->route('perfumes.index')
            ->with('success', "{$perfume->name} deleted");
    }
}
