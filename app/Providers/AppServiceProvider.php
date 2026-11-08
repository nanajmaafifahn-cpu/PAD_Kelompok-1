<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use App\Strategies\UtilizationCalculatorContext;
use App\Strategies\ClassroomUtilizationStrategy;
use App\Strategies\LaboratoryUtilizationStrategy;
use App\Strategies\UtilizationStrategyInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Pola Desain Pendukung (Strategy Pattern):
        // Mendaftarkan seluruh implementasi strategi utilisasi konkret ke dalam UtilizationCalculatorContext.
        // Jika terdapat tipe ruangan baru (misal: auditorium), cukup buat class strateginya dan tambahkan di array ini.
        $this->app->bind(UtilizationCalculatorContext::class, function ($app) {
            $strategies = [
                $app->make(ClassroomUtilizationStrategy::class),
                $app->make(LaboratoryUtilizationStrategy::class),
            ];

            return new UtilizationCalculatorContext($strategies);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Data mentah dashboard (JSON) hanya untuk pengelola, admin, dan pimpinan.
        Gate::define(
            'view-utilization-data',
            fn (User $user) => $user->hasRole(User::ROLE_LAB_HEAD, User::ROLE_ADMIN, User::ROLE_LEADER)
        );

        // Batas percobaan; lewat batas -> kembali ke form dengan pesan (bukan halaman 429 polos).
        $tooMany = fn (string $message) => fn (Request $request) => back()
            ->withInput($request->except('password'))
            ->withErrors(['throttle' => $message]);

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('email')) . '|' . $request->ip())
            ->response($tooMany('Terlalu banyak percobaan login. Coba lagi dalam 1 menit.')));

        RateLimiter::for('booking-store', fn (Request $request) => Limit::perMinute(10)
            ->by((string) ($request->user()?->id ?? $request->ip()))
            ->response($tooMany('Terlalu banyak pengajuan dalam 1 menit. Coba lagi sebentar lagi.')));

        RateLimiter::for('qr-scan', fn (Request $request) => Limit::perMinute(20)
            ->by((string) ($request->user()?->id ?? $request->ip()))
            ->response($tooMany('Terlalu banyak percobaan scan QR. Coba lagi dalam 1 menit.')));
    }
}
