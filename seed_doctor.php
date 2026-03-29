<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$doctor = \App\Models\User::role('médico')->first();
if($doctor) {
    \App\Models\DoctorSchedule::create(['doctor_id'=>$doctor->id, 'day_of_week'=>1, 'start_time'=>'09:00', 'end_time'=>'18:00', 'slot_duration_minutes'=>15]);
    \App\Models\DoctorSchedule::create(['doctor_id'=>$doctor->id, 'day_of_week'=>3, 'start_time'=>'10:00', 'end_time'=>'14:00', 'slot_duration_minutes'=>15]);
    echo 'SEEDED ' . $doctor->name;
} else {
    echo 'NO_DOCTOR';
}
