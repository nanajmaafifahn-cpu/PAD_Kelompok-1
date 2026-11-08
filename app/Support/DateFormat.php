<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Satu tempat format tanggal/jam untuk view (bahasa Indonesia, zona waktu aplikasi WIB).
 * Pakai di Blade: {{ \App\Support\DateFormat::dateTime($booking->start_time) }}
 */
final class DateFormat
{
    /** 8 Nov 2026 */
    public static function date(?CarbonInterface $at): string
    {
        return $at ? $at->locale('id')->isoFormat('D MMM YYYY') : '—';
    }

    /** Minggu, 8 Nov 2026 */
    public static function longDate(?CarbonInterface $at): string
    {
        return $at ? $at->locale('id')->isoFormat('dddd, D MMM YYYY') : '—';
    }

    /** 10.36 */
    public static function time(?CarbonInterface $at): string
    {
        return $at ? $at->format('H.i') : '—';
    }

    /** 8 Nov 2026, 10.36 WIB */
    public static function dateTime(?CarbonInterface $at): string
    {
        return $at ? self::date($at) . ', ' . $at->format('H.i T') : '—';
    }

    /** Min, 8 Nov 2026 · 10.36–12.36 WIB (beda hari: dua tanggal lengkap) */
    public static function range(CarbonInterface $start, CarbonInterface $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->locale('id')->isoFormat('ddd, D MMM YYYY') . ' · '
                . $start->format('H.i') . '–' . $end->format('H.i T');
        }

        return self::dateTime($start) . ' – ' . self::dateTime($end);
    }

    /** Minggu */
    public static function dayName(CarbonInterface $at): string
    {
        return $at->locale('id')->isoFormat('dddd');
    }

    /** Nov 2026 */
    public static function monthYear(CarbonInterface $at): string
    {
        return $at->locale('id')->isoFormat('MMM YYYY');
    }

    /** Nilai untuk <input type="datetime-local">: 2026-11-08T10:36 */
    public static function inputValue(?CarbonInterface $at): ?string
    {
        return $at?->format('Y-m-d\TH:i');
    }

    /** WIB (UTC+7) — zona waktu aplikasi (config app.timezone) */
    public static function timezoneLabel(): string
    {
        $now = now();
        $hours = $now->utcOffset() / 60;

        return $now->format('T') . ' (UTC' . ($hours >= 0 ? '+' : '−') . abs($hours) . ')';
    }

    /** 02 Nov – 08 Nov 2026 */
    public static function weekRange(CarbonInterface $start, CarbonInterface $end): string
    {
        return $start->locale('id')->isoFormat('DD MMM') . ' – ' . $end->locale('id')->isoFormat('DD MMM YYYY');
    }
}
