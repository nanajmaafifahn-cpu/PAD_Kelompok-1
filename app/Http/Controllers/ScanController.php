<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Http\Requests\ScanManualRequest;
use App\Http\Requests\ScanRoomRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Alur QR per ruangan:
 *   GET  /scan                 halaman pemindai kamera + input manual
 *   GET  /scan/{room:qr_token} hasil pindai (dibuka langsung dari kamera ponsel), tidak mengubah data
 *   POST /scan/{room:qr_token} konfirmasi check-in / check-out
 * Seluruh logika ada di BookingService; controller hanya meneruskan dan merespons.
 */
class ScanController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {
    }

    public function index(): View
    {
        Gate::authorize('scan', Booking::class);

        return view('scan.index');
    }

    public function manual(ScanManualRequest $request): RedirectResponse
    {
        try {
            $room = $this->bookingService->resolveScanCode(
                $request->user(),
                $request->validated('code')
            );
        } catch (BookingException $exception) {
            return back()
                ->withInput()
                ->withErrors(['code' => $exception->getMessage()]);
        }

        return redirect()->route('scan.room', $room);
    }

    public function show(Request $request, Room $room): View
    {
        Gate::authorize('scan', Booking::class);

        return view('scan.show', [
            'room' => $room,
            'result' => $this->bookingService->evaluateScan($request->user(), $room),
        ]);
    }

    public function confirm(ScanRoomRequest $request, Room $room): RedirectResponse
    {
        try {
            $result = $this->bookingService->scanRoom($request->user(), $room);
        } catch (BookingException $exception) {
            return redirect()
                ->route('scan.room', $room)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('bookings.show', $result->booking)
            ->with('success', $result->message);
    }
}
