<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Schedule;
use Carbon\Carbon;

/**
 * Jadwal resmi kuliah/praktikum (C3) dengan cek bentrok (D2):
 * tidak boleh tumpang tindih dengan jadwal lain (status != cancelled) maupun
 * booking approved / checked_in di ruang yang sama.
 */
class ScheduleService
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @param array{room_id:int,title:string,start_time:mixed,end_time:mixed} $data
     *
     * @throws BookingException field start_time bila bentrok
     */
    public function create(array $data): Schedule
    {
        $this->assertNoConflict((int) $data['room_id'], $data['start_time'], $data['end_time']);

        return Schedule::create([
            ...$data,
            'status' => self::STATUS_SCHEDULED,
        ]);
    }

    /** @throws BookingException */
    public function update(Schedule $schedule, array $data): Schedule
    {
        if ($schedule->status !== self::STATUS_CANCELLED) {
            $this->assertNoConflict((int) $data['room_id'], $data['start_time'], $data['end_time'], $schedule->id);
        }

        $schedule->update($data);

        return $schedule;
    }

    public function cancel(Schedule $schedule): Schedule
    {
        $schedule->update(['status' => self::STATUS_CANCELLED]);

        return $schedule;
    }

    /** @throws BookingException */
    public function assertNoConflict(int $roomId, mixed $start, mixed $end, ?int $exceptScheduleId = null): void
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        $schedules = Schedule::query()
            ->where('room_id', $roomId)
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($exceptScheduleId, fn ($q) => $q->whereKeyNot($exceptScheduleId))
            ->exists();

        $bookings = Booking::query()
            ->where('room_id', $roomId)
            ->whereIn('status', [Booking::STATUS_APPROVED, Booking::STATUS_CHECKED_IN])
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();

        if ($schedules || $bookings) {
            throw BookingException::scheduleConflict();
        }
    }
}
