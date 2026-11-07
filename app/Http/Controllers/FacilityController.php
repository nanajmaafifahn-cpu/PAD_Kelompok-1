<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFacilityRequest;
use App\Http\Requests\UpdateFacilityRequest;
use App\Models\Facility;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Kelola fasilitas per ruang (C4): daftar + tambah di satu halaman, ubah, hapus.
 * Khusus pengelola & admin (FacilityPolicy, middleware role).
 */
class FacilityController extends Controller
{
    public function index(Room $room): View
    {
        Gate::authorize('create', Facility::class);

        return view('facilities.index', [
            'room' => $room,
            'facilities' => $room->facilities()->orderBy('name')->get(),
            'statuses' => Facility::STATUS_LABELS,
        ]);
    }

    public function store(StoreFacilityRequest $request, Room $room): RedirectResponse
    {
        $room->facilities()->create($request->validated());

        return redirect()
            ->route('rooms.facilities.index', $room)
            ->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function edit(Facility $facility): View
    {
        Gate::authorize('update', $facility);

        return view('facilities.edit', [
            'facility' => $facility->load('room'),
            'statuses' => Facility::STATUS_LABELS,
        ]);
    }

    public function update(UpdateFacilityRequest $request, Facility $facility): RedirectResponse
    {
        $facility->update($request->validated());

        return redirect()
            ->route('rooms.facilities.index', $facility->room_id)
            ->with('success', 'Fasilitas berhasil diperbarui.');
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        Gate::authorize('delete', $facility);

        $roomId = $facility->room_id;
        $facility->delete();

        return redirect()
            ->route('rooms.facilities.index', $roomId)
            ->with('success', 'Fasilitas berhasil dihapus.');
    }
}
