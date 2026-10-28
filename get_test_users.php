<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Student;
use App\Models\Lecturer;

echo "--- ADMIN ---" . PHP_EOL;
$admin = User::where('role', 'admin')->first();
echo "Username: " . ($admin ? $admin->username : 'admin') . " | Name: " . ($admin ? $admin->name : 'Admin') . PHP_EOL;

echo PHP_EOL . "--- LECTURERS ---" . PHP_EOL;
foreach (Lecturer::with('user', 'homeroomClasses')->take(3)->get() as $gv) {
    $classes = $gv->homeroomClasses->pluck('class_code')->implode(', ');
    echo "Username: " . ($gv->user ? $gv->user->username : $gv->lecturer_code) . " | Name: {$gv->full_name} | Class CVHT: [{$classes}]" . PHP_EOL;
}

echo PHP_EOL . "--- STUDENTS ---" . PHP_EOL;
foreach (Student::with('user')->take(3)->get() as $st) {
    echo "Username: " . ($st->user ? $st->user->username : $st->student_code) . " | Code: {$st->student_code} | Name: {$st->full_name} | Class: {$st->class_code}" . PHP_EOL;
}
