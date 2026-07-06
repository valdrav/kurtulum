<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schedule_programs')) {
            Schema::table('schedule_programs', function (Blueprint $table) {
                $table->dropUnique(['year', 'month', 'week_number']);
            });
        }

        DB::table('schedule_programs')->orderBy('id')->lazy()->each(function ($program) {
            $start = \Carbon\Carbon::create($program->year, $program->month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            DB::table('schedule_programs')->where('id', $program->id)->update([
                'week_start' => $start->toDateString(),
                'week_end' => $end->toDateString(),
                'week_number' => 0,
            ]);
        });

        $duplicates = DB::table('schedule_programs')
            ->select('year', 'month', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('year', 'month')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $group) {
            $removeIds = DB::table('schedule_programs')
                ->where('year', $group->year)
                ->where('month', $group->month)
                ->where('id', '!=', $group->keep_id)
                ->pluck('id');

            if ($removeIds->isNotEmpty()) {
                DB::table('schedule_entries')
                    ->whereIn('schedule_program_id', $removeIds)
                    ->update(['schedule_program_id' => $group->keep_id]);

                DB::table('schedule_programs')->whereIn('id', $removeIds)->delete();
            }
        }

        Schema::table('schedule_programs', function (Blueprint $table) {
            $table->unique(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::table('schedule_programs', function (Blueprint $table) {
            $table->dropUnique(['year', 'month']);
            $table->unique(['year', 'month', 'week_number']);
        });
    }
};
