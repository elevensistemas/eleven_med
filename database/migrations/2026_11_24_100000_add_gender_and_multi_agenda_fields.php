<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('patients') && !Schema::hasColumn('patients', 'gender')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('gender', 20)->nullable()->after('date_of_birth');
            });
        }

        if (Schema::hasTable('doctor_configurations')) {
            Schema::table('doctor_configurations', function (Blueprint $table) {
                if (!Schema::hasColumn('doctor_configurations', 'agenda_type')) {
                    $table->string('agenda_type', 40)->default('medica')->after('doctor_id');
                }
                if (!Schema::hasColumn('doctor_configurations', 'agenda_label')) {
                    $table->string('agenda_label', 120)->nullable()->after('agenda_type');
                }
            });
        }

        if (Schema::hasTable('doctor_schedules') && !Schema::hasColumn('doctor_schedules', 'agenda_type')) {
            Schema::table('doctor_schedules', function (Blueprint $table) {
                $table->string('agenda_type', 40)->default('medica')->after('doctor_id');
            });
        }

        if (Schema::hasTable('schedule_blocks') && !Schema::hasColumn('schedule_blocks', 'agenda_type')) {
            Schema::table('schedule_blocks', function (Blueprint $table) {
                $table->string('agenda_type', 40)->default('medica')->after('doctor_id');
            });
        }

        if (Schema::hasTable('appointments') && !Schema::hasColumn('appointments', 'agenda_type')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('agenda_type', 40)->default('medica')->after('doctor_id');
            });
        }

        if (Schema::hasTable('roles')) {
            try {
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'técnico', 'guard_name' => 'web']);
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            } catch (\Throwable $e) {
                // Silencioso
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('patients') && Schema::hasColumn('patients', 'gender')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('gender');
            });
        }

        foreach (['doctor_configurations', 'doctor_schedules', 'schedule_blocks', 'appointments'] as $tbl) {
            if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'agenda_type')) {
                Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                    $cols = ['agenda_type'];
                    if ($tbl === 'doctor_configurations' && Schema::hasColumn($tbl, 'agenda_label')) {
                        $cols[] = 'agenda_label';
                    }
                    $table->dropColumn($cols);
                });
            }
        }
    }
};
