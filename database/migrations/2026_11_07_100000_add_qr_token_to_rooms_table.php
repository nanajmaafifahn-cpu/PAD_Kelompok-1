<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Token QR per ruangan. Isi QR yang ditempel di pintu = URL route('scan.room', qr_token).
     * Kolom dibuat nullable lalu diisi untuk ruang yang sudah ada; ruang baru diisi otomatis
     * oleh Room::booted() (creating).
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('qr_token', 64)
                ->nullable()
                ->unique()
                ->after('code');
        });

        DB::table('rooms')
            ->whereNull('qr_token')
            ->orderBy('id')
            ->pluck('id')
            ->each(fn ($id) => DB::table('rooms')
                ->where('id', $id)
                ->update(['qr_token' => Str::random(40)]));
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
