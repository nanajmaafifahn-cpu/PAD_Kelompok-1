<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicUnitRequest;
use App\Http\Requests\Admin\UpdateAcademicUnitRequest;
use App\Models\AcademicUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Kelola unit akademik (C5, khusus admin): daftar + tambah, ubah, hapus. */
class AcademicUnitController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', AcademicUnit::class);

        return view('admin.units.index', [
            'units' => AcademicUnit::withCount('rooms')->orderBy('code')->get(),
        ]);
    }

    public function store(StoreAcademicUnitRequest $request): RedirectResponse
    {
        AcademicUnit::create($request->validated());

        return redirect()
            ->route('admin.units.index')
            ->with('success', 'Unit akademik berhasil ditambahkan.');
    }

    public function edit(AcademicUnit $unit): View
    {
        Gate::authorize('update', $unit);

        return view('admin.units.edit', ['unit' => $unit]);
    }

    public function update(UpdateAcademicUnitRequest $request, AcademicUnit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return redirect()
            ->route('admin.units.index')
            ->with('success', "Unit {$unit->code} berhasil diperbarui.");
    }

    // FK rooms.academic_unit_id restrictOnDelete: unit yang masih punya ruang tidak dapat dihapus.
    public function destroy(AcademicUnit $unit): RedirectResponse
    {
        Gate::authorize('delete', $unit);

        $rooms = $unit->rooms()->count();

        if ($rooms > 0) {
            return back()->with(
                'error',
                "Unit {$unit->code} masih dipakai {$rooms} ruang. Pindahkan ruang ke unit lain terlebih dahulu."
            );
        }

        $unit->delete();

        return redirect()
            ->route('admin.units.index')
            ->with('success', "Unit {$unit->code} berhasil dihapus.");
    }
}
