<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Http\Requests\ApproveBookingRequest;
use App\Http\Requests\QrScanRequest;
use App\Http\Requests\RejectBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Controller untuk menangani alur pemesanan (booking) ruangan:
 * pengajuan, persetujuan (approve), penolakan (reject), dan simulasi scan QR (check-in/check-out).
 * Menerapkan pola Skinny Controller di mana seluruh logika bisnis didelegasikan ke BookingService.
 */
class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {
    }

    /**
     * Menampilkan daftar semua peminjaman (pengelola, admin, pimpinan).
     * Peminjam diarahkan ke "Peminjaman Saya" agar tidak melihat data orang lain.
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->cannot('viewAll', Booking::class)) {
            return redirect()->route('bookings.mine');
        }

        return view('bookings.index', [
            'bookings' => $this->bookingService->paginateBookings(),
            'scope' => 'all',
        ]);
    }

    /**
     * Riwayat peminjaman milik user yang login.
     */
    public function mine(Request $request): View
    {
        return view('bookings.index', [
            'bookings' => $this->bookingService->paginateBookingsOf($request->user()),
            'scope' => 'mine',
        ]);
    }

    /**
     * Detail satu peminjaman. Kode cadangan QR hanya ditampilkan untuk pemiliknya.
     */
    public function show(Request $request, Booking $booking): View
    {
        Gate::authorize('view', $booking);

        $booking->load(['room.academicUnit', 'user']);
        $isOwner = (int) $booking->user_id === (int) $request->user()->id;

        return view('bookings.show', [
            'booking' => $booking,
            'isOwner' => $isOwner,
            'backupCode' => $isOwner && in_array($booking->getStatus(), [
                Booking::STATUS_APPROVED,
                Booking::STATUS_CHECKED_IN,
            ], true) ? $booking->qr_token : null,
        ]);
    }

    /**
     * Menampilkan form pengajuan peminjaman ruangan.
     */
    public function create(Request $request): View
    {
        $rooms = $this->bookingService->roomsForBooking();

        // ?room_id=.. dari detail ruang: ruang langsung terpilih bila ada & tidak maintenance.
        $selected = $rooms->firstWhere('id', $request->integer('room_id'));

        return view('bookings.create', [
            'rooms' => $rooms,
            'selectedRoomId' => $selected && $selected->getStatus() !== 'maintenance' ? $selected->id : null,
        ]);
    }

    /**
     * Memproses pengajuan peminjaman baru oleh user.
     */
    public function store(
        StoreBookingRequest $request
    ): RedirectResponse {
        try {
            $booking = $this->bookingService->submitBooking(
                $request->user(),
                $request->validated()
            );
        } catch (BookingException $exception) {
            // D8: galat domain tampil di bawah field terkait (kapasitas, bentrok, dst.).
            return back()
                ->withInput()
                ->withErrors($exception->toErrors());
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with(
                'success',
                'Peminjaman berhasil diajukan dan menunggu persetujuan.'
            );
    }

    /**
     * Form ubah booking (pemilik, selama pending). View menyembunyikan form bila bukan pending.
     */
    public function edit(Booking $booking): View
    {
        Gate::authorize('update', $booking);

        return view('bookings.edit', [
            'booking' => $booking,
            'rooms' => $this->bookingService->roomsForBooking(),
        ]);
    }

    public function update(UpdateBookingRequest $request, Booking $booking): RedirectResponse
    {
        try {
            $this->bookingService->updateBooking($booking, $request->user(), $request->validated());
        } catch (BookingException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->toErrors());
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', 'Perubahan booking disimpan dan tetap menunggu persetujuan.');
    }

    /**
     * Batalkan booking oleh pemilik (pending/approved yang belum dimulai) -> status cancelled.
     */
    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('cancel', $booking);

        try {
            $this->bookingService->cancelBooking($booking, $request->user());
        } catch (BookingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', 'Booking dibatalkan.');
    }

    /**
     * Hapus booking oleh pengelola/admin (hanya pending, rejected, cancelled).
     */
    public function destroy(Booking $booking): RedirectResponse
    {
        Gate::authorize('delete', $booking);

        try {
            $this->bookingService->deleteBooking($booking);
        } catch (BookingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('bookings.index')
            ->with('success', 'Booking dihapus.');
    }

    /**
     * Menyetujui peminjaman oleh pihak berwenang (Kepala Lab / Admin).
     */
    public function approve(
        ApproveBookingRequest $request,
        Booking $booking
    ): RedirectResponse {
        try {
            $this->bookingService->approve($booking, $request->user());
        } catch (BookingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('bookings.index')
            ->with('success', 'Peminjaman disetujui.');
    }

    /**
     * Menolak peminjaman beserta alasan penolakan.
     */
    public function reject(
        RejectBookingRequest $request,
        Booking $booking
    ): RedirectResponse {
        try {
            $this->bookingService->reject(
                $booking,
                $request->user(),
                $request->validated('rejection_reason')
            );
        } catch (BookingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('bookings.index')
            ->with('success', 'Peminjaman ditolak.');
    }

    /**
     * Simulasi check-in pengguna di ruangan menggunakan token QR code.
     */
    public function checkIn(
        QrScanRequest $request,
        Booking $booking
    ): RedirectResponse {
        try {
            $this->bookingService->checkIn(
                $booking,
                $request->validated('qr_token')
            );
        } catch (BookingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('bookings.index')
            ->with('success', 'Check-in berhasil. Ruangan kini terpakai.');
    }

    /**
     * Simulasi check-out pengguna setelah selesai memakai ruangan.
     */
    public function checkOut(
        QrScanRequest $request,
        Booking $booking
    ): RedirectResponse {
        try {
            $this->bookingService->checkOut(
                $booking,
                $request->validated('qr_token')
            );
        } catch (BookingException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('bookings.index')
            ->with('success', 'Check-out berhasil. Ruangan kembali tersedia.');
    }
}
