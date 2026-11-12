<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('patronymic')->nullable()->after('last_name');
            $table->string('phone', 32)->nullable()->after('patronymic');
        });

        DB::table('users')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function (object $user): void {
                $parts = preg_split('/\s+/u', trim((string) $user->name), 3) ?: [];

                DB::table('users')->where('id', $user->id)->update([
                    'last_name' => $parts[0] ?? (string) $user->name,
                    'first_name' => $parts[1] ?? ($parts[0] ?? (string) $user->name),
                    'patronymic' => $parts[2] ?? null,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['first_name', 'last_name', 'patronymic', 'phone']);
        });
    }
};
