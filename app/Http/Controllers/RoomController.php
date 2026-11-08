<?php

namespace App\Http\Controllers;

use App\Exceptions\RoomException;
use App\Http\Requests\RoomIndexRequest;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\AcademicUnit;
use App\Models\Facility;
use App\Models\Room;
use App\Services\CalendarService;
use App\Services\RoomService;
use App\Services\RoomStatusService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use League\Flysystem\FilesystemException;

/**
 * Controller untuk manajemen data master ruangan dan laboratorium.
 * Bertanggung jawab menampilkan daftar, form tambah, dan persistensi data ruangan.
 */
class RoomController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService,
        private readonly RoomStatusService $roomStatus
    ) {
    }

    /**
     * Menampilkan daftar master ruangan dan laboratorium beserta relasi
     * unit akademik dan fasilitas terkait menggunakan paginasi.
     * Filter query string (q, type, min_capacity, facility) bertahan saat paginasi (E4).
     */
    public function index(RoomIndexRequest $request): View
    {
        $filters = $request->filters();

        $rooms = Room::with(['academicUnit', 'facilities'])
            ->withCount('bookings')
            ->filter($filters)
            ->orderBy('name')
            ->paginate(12) // grid 3 kolom
            ->withQueryString();

        return view('rooms.index', [
            'rooms' => $rooms,
            'liveStatuses' => $this->roomStatus->forRooms($rooms->getCollection()),
            'filters' => $filters,
            'types' => Room::TYPE_LABELS,
            'facilityNames' => Facility::query()->distinct()->orderBy('name')->pluck('name'),
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
            'types' => Room::TYPE_LABELS,
        ]);
    }

    /**
     * Detail ruang untuk semua user login: foto, atribut, fasilitas, status live,
     * dan kegiatan terdekat (jadwal kuliah + peminjaman disetujui).
     */
    public function show(Room $room, CalendarService $calendar): View
    {
        Gate::authorize('viewAny', Room::class);

        $room->load(['academicUnit', 'facilities']);

        return view('rooms.show', [
            'room' => $room,
            'liveStatus' => $this->roomStatus->forRooms([$room])[$room->id],
            'computerCount' => $room->computerCount(),
            'upcomingEvents' => $calendar->upcomingForRoom($room),
        ]);
    }

    /**
     * "Ruangan & Foto" (data master, pengelola/admin): tabel ruang + aksi ubah/hapus/fasilitas.
     * Halaman Jelajahi (rooms.index) tetap untuk semua user.
     */
    public function manage(): View
    {
        Gate::authorize('create', Room::class);

        $rooms = Room::with(['academicUnit', 'facilities'])
            ->withCount('bookings')
            ->orderBy('code')
            ->paginate(15);

        return view('rooms.manage', [
            'rooms' => $rooms,
            'liveStatuses' => $this->roomStatus->forRooms($rooms->getCollection()),
        ]);
    }

    public function edit(Room $room): View
    {
        Gate::authorize('update', $room);

        return view('rooms.edit', [
            'room' => $room,
            'academicUnits' => AcademicUnit::orderBy('name')->get(),
            'types' => Room::TYPE_LABELS,
        ]);
    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        try {
            $this->roomService->update(
                $room,
                $request->validated(),
                $request->file('photo'),
                $request->boolean('remove_photo')
            );
        } catch (RoomException|QueryException|FilesystemException $exception) {
            report($exception);

            return back()->withInput($request->except('photo'))
                ->with('error', 'Perubahan ruang gagal disimpan. Coba lagi; pilih kembali foto jika ada.');
        }

        return redirect()
            ->route('rooms.manage')
            ->with('success', "Ruang {$room->name} berhasil diperbarui.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        Gate::authorize('delete', $room);

        try {
            $this->roomService->delete($room);
        } catch (RoomException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('rooms.manage')
            ->with('success', "Ruang {$room->name} berhasil dihapus.");
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
        try {
            $this->roomService->create($request->validated(), $request->file('photo'));
        } catch (RoomException|QueryException|FilesystemException $exception) {
            report($exception);

            return back()->withInput($request->except('photo'))
                ->with('error', 'Ruang gagal disimpan. Coba lagi; pilih kembali foto jika ada.');
        }

        return redirect()
            ->route('rooms.manage')
            ->with(
                'success',
                'Ruangan berhasil ditambahkan.'
            );
    }
}
