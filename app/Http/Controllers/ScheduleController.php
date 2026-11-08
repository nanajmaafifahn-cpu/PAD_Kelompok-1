<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingException;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Models\Room;
use App\Models\Schedule;
use App\Services\CalendarService;
use App\Services\ScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Controller untuk mengelola jadwal resmi perkuliahan dan praktikum.
 * Jadwal ini menjadi dasar perhitungan scheduled utilization pada ruangan.
 * Cek bentrok (D2) ada di ScheduleService.
 */
class ScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleService $scheduleService
    ) {
    }

    /**
     * Menampilkan daftar jadwal perkuliahan/praktikum terurut berdasarkan waktu mulai,
     * beserta daftar ruang untuk form tambah jadwal (A5 / BE-03).
     */
    public function index(CalendarService $calendar): View
    {
        $schedules = Schedule::with('room')
            ->orderBy('start_time')
            ->paginate(10);

        return view('schedules.index', [
            'schedules' => $schedules,
            'rooms' => Room::orderBy('name')->get(),
            // Kalender minggu berjalan: jadwal kuliah + peminjaman disetujui per hari.
            'week' => $calendar->week(),
            'eventTypes' => CalendarService::TYPE_LABELS,
        ]);
    }

    /**
     * Menyimpan jadwal baru dengan status default 'scheduled'.
     */
    public function store(
        StoreScheduleRequest $request
    ): RedirectResponse {
        try {
            $this->scheduleService->create($request->validated());
        } catch (BookingException $exception) {
            return back()->withInput()->withErrors($exception->toErrors());
        }

        return redirect()
            ->route('schedules.index')
            ->with(
                'success',
                'Jadwal berhasil ditambahkan.'
            );
    }

    public function edit(Schedule $schedule): View
    {
        Gate::authorize('update', $schedule);

        return view('schedules.edit', [
            'schedule' => $schedule,
            'rooms' => Room::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        try {
            $this->scheduleService->update($schedule, $request->validated());
        } catch (BookingException $exception) {
            return back()->withInput()->withErrors($exception->toErrors());
        }

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function cancel(Schedule $schedule): RedirectResponse
    {
        Gate::authorize('update', $schedule);

        $this->scheduleService->cancel($schedule);

        return redirect()
            ->route('schedules.index')
            ->with('success', "Jadwal {$schedule->title} dibatalkan.");
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        Gate::authorize('delete', $schedule);

        $schedule->delete();

        return redirect()
            ->route('schedules.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }
}
