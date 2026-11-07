<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Models\AcademicUnit;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Controller untuk manajemen data master ruangan dan laboratorium.
 * Bertanggung jawab menampilkan daftar, form tambah, dan persistensi data ruangan.
 */
class RoomController extends Controller
{
    /**
     * Menampilkan daftar master ruangan dan laboratorium beserta relasi
     * unit akademik dan fasilitas terkait menggunakan paginasi.
     */
    public function index(): View
    {
        $rooms = Room::with(['academicUnit', 'facilities'])
            ->orderBy('name')
            ->paginate(10);

        return view('rooms.index', [
            'rooms' => $rooms,
        ]);
    }

    /**
     * Menampilkan form untuk mendaftarkan ruangan baru beserta
     * pilihan unit akademik (fakultas/jurusan).
     */
    public function create(): View
    {
        $academicUnits = AcademicUnit::orderBy('name')
            ->get();

        return view('rooms.create', [
            'academicUnits' => $academicUnits,
        ]);
    }

    /**
     * Halaman "Cetak QR Ruangan" (pengelola/admin): satu kartu QR per ruang.
     * Gambar QR dibuat di browser (npm qrcode) dari URL route('scan.room', qr_token).
     */
    public function qrIndex(): View
    {
        Gate::authorize('create', Room::class);

        return view('rooms.qr', [
            'rooms' => Room::orderBy('code')->get(),
            'baseUrl' => rtrim((string) config('app.url'), '/'),
        ]);
    }

    /**
     * Aksi "Perbarui QR": token lama tidak berlaku, QR harus dicetak ulang.
     */
    public function regenerateQr(Room $room): RedirectResponse
    {
        Gate::authorize('update', $room);

        $room->regenerateQrToken();

        return redirect()
            ->route('rooms.qr.index')
            ->with('success', "QR ruang {$room->name} diperbarui. Cetak ulang dan ganti QR lama di pintu ruangan.");
    }

    /**
     * Menyimpan data ruangan baru ke basis data setelah divalidasi oleh StoreRoomRequest.
     */
    public function store(
        StoreRoomRequest $request
    ): RedirectResponse {
        Room::create($request->validated());

        return redirect()
            ->route('rooms.index')
            ->with(
                'success',
                'Ruangan berhasil ditambahkan.'
            );
    }
}
