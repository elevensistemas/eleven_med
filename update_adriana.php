<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\File;

$sourceFile = __DIR__ . '/adriana nieva foto.PNG';
$destinationPath = __DIR__ . '/storage/app/public/avatars/';
$destinationFile = $destinationPath . 'adriana_nieva.png';

if (!File::exists($destinationPath)) {
    File::makeDirectory($destinationPath, 0755, true);
}

if (File::exists($sourceFile)) {
    File::copy($sourceFile, $destinationFile);
    echo "Image copied to storage.\n";
    
    $adriana = User::where('name', 'like', '%Adriana%')->orWhere('email', 'like', '%adriana%')->first();
    if ($adriana) {
        $adriana->profile_photo = 'avatars/adriana_nieva.png';
        $adriana->save();
        echo "Adriana's user profile updated successfully.\n";
    } else {
        echo "User Adriana not found in database.\n";
    }
} else {
    echo "Source file not found.\n";
}
