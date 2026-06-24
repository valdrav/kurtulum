<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            if (! Schema::hasColumn('vessels', 'tracked_at')) {
                $table->timestamp('tracked_at')->nullable()->after('mt_url');
            }
            if (! Schema::hasColumn('vessels', 'gt')) {
                $table->string('gt')->nullable()->after('dwt');
            }
            if (! Schema::hasColumn('vessels', 'length_m')) {
                $table->string('length_m')->nullable()->after('gt');
            }
            if (! Schema::hasColumn('vessels', 'beam_m')) {
                $table->string('beam_m')->nullable()->after('length_m');
            }
            if (! Schema::hasColumn('vessels', 'year_built')) {
                $table->unsignedSmallInteger('year_built')->nullable()->after('beam_m');
            }
        });

        DB::table('vessels')->where('mmsi', '')->update(['mmsi' => null]);

        $seen = [];
        $rows = DB::table('vessels')
            ->whereNotNull('mmsi')
            ->orderBy('id')
            ->get(['id', 'mmsi']);

        foreach ($rows as $row) {
            if (isset($seen[$row->mmsi])) {
                DB::table('vessels')->where('id', $row->id)->update(['mmsi' => null]);
                continue;
            }
            $seen[$row->mmsi] = true;
        }

        if (! $this->hasMmsiUniqueIndex()) {
            Schema::table('vessels', function (Blueprint $table) {
                $table->unique('mmsi');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasMmsiUniqueIndex()) {
            Schema::table('vessels', function (Blueprint $table) {
                $table->dropUnique(['mmsi']);
            });
        }

        Schema::table('vessels', function (Blueprint $table) {
            $columns = ['tracked_at', 'gt', 'length_m', 'beam_m', 'year_built'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('vessels', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    protected function hasMmsiUniqueIndex(): bool
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('vessels')");

            foreach ($indexes as $index) {
                if (($index->unique ?? 0) != 1) {
                    continue;
                }
                $info = DB::select("PRAGMA index_info('{$index->name}')");
                foreach ($info as $col) {
                    if (($col->name ?? '') === 'mmsi') {
                        return true;
                    }
                }
            }

            return false;
        }

        return false;
    }
};
