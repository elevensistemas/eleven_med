<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$doctor = User::where('name', 'like', '%Cortalezzi%')->where('email', 'medico@cortalezzi.com')->first();
if ($doctor) {
    $doctor->profile_photo = 'avatars/dr_cortalezzi.png';
    $doctor->save();
    echo "Doctor updated.\n";
} else {
    // If not found by email, just find by name 'Dr. Cortalezzi'
    $doctor2 = User::where('name', 'like', '%Dr.%')->first();
    if ($doctor2) {
        $doctor2->profile_photo = 'avatars/dr_cortalezzi.png';
        $doctor2->save();
        echo "Doctor 2 updated.\n";
    } else {
        echo "Doctor not found.\n";
    }
}
