<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncomingLetterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\WorkUnitController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Controllers\DashboardController;


Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/locale/{locale}', function ($locale) {
    $available = ['id', 'en'];
    if (in_array($locale, $available, true)) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('locale.switch');

Auth::routes();

// Authenticated profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'changePassword'])->name('profile.password');
});

Route::get('/incoming-letters', [IncomingLetterController::class, 'index'])->name('incoming_letters.index');
Route::get('/incoming-letters/create', [IncomingLetterController::class, 'create'])->name('incoming_letters.create');
Route::post('/incoming-letters', [IncomingLetterController::class, 'store'])->name('incoming_letters.store');
Route::get('/incoming-letters/{incoming_letter}', [IncomingLetterController::class, 'show'])->name('incoming_letters.show');
Route::get('/incoming-letters/{incoming_letter}/edit', [IncomingLetterController::class, 'edit'])->name('incoming_letters.edit');
Route::put('/incoming-letters/{incoming_letter}', [IncomingLetterController::class, 'update'])->name('incoming_letters.update');
Route::delete('/incoming-letters/{incoming_letter}', [IncomingLetterController::class, 'destroy'])->name('incoming_letters.destroy');

Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/datatable', [UserController::class, 'datatable'])->name('users.datatable');
Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

Route::prefix('employees')->name('employees.')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::get('/create', [EmployeeController::class, 'create'])->name('create');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
    Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
    Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
    Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
});

Route::prefix('permissions')->name('permissions.')->group(function () {
    Route::get('/', [PermissionController::class, 'index'])->name('index');
    Route::get('/create', [PermissionController::class, 'create'])->name('create');
    Route::post('/', [PermissionController::class, 'store'])->name('store');
    Route::get('/edit', [PermissionController::class, 'edit'])->name('edit'); // bulk edit screen
    Route::put('/bulk-update', [PermissionController::class, 'update'])->name('update');
    Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy');
});

Route::prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::get('/create', [RoleController::class, 'create'])->name('create');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::get('/{role}', [RoleController::class, 'show'])->name('show');
    Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
});

Route::prefix('grades')->name('grades.')->group(function () {
    Route::get('/', [GradeController::class, 'index'])->name('index');
    Route::get('/create', [GradeController::class, 'create'])->name('create');
    Route::post('/', [GradeController::class, 'store'])->name('store');
    Route::get('/{grade}', [GradeController::class, 'show'])->name('show');
    Route::get('/{grade}/edit', [GradeController::class, 'edit'])->name('edit');
    Route::put('/{grade}', [GradeController::class, 'update'])->name('update');
    Route::delete('/{grade}', [GradeController::class, 'destroy'])->name('destroy');
});

Route::prefix('work-units')->name('work_units.')->group(function () {
    Route::get('/', [WorkUnitController::class, 'index'])->name('index');
    Route::get('/create', [WorkUnitController::class, 'create'])->name('create');
    Route::post('/', [WorkUnitController::class, 'store'])->name('store');
    Route::get('/{work_unit}', [WorkUnitController::class, 'show'])->name('show');
    Route::get('/{work_unit}/edit', [WorkUnitController::class, 'edit'])->name('edit');
    Route::put('/{work_unit}', [WorkUnitController::class, 'update'])->name('update');
    Route::delete('/{work_unit}', [WorkUnitController::class, 'destroy'])->name('destroy');
});

// WhatsApp webhook (no auth middleware)
Route::get('/webhook/whatsapp', [WhatsappWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WhatsappWebhookController::class, 'handle']);
