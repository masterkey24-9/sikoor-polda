<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'satker.padang@poldasumbar.go.id')->first();
if ($user) {
    echo "FOUND\n";
    echo "email: {$user->email}\n";
    echo "password: {$user->password}\n";
} else {
    echo "NOT FOUND\n";
}
