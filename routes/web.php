<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Auth Required Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    | Profile
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    | Attendance
    */
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockin');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockout');

    /*
    | Employee Leave
    */
    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::get('/leave/apply', [LeaveController::class, 'create'])->name('leave.create');
    Route::post('/leave/store', [LeaveController::class, 'store'])->name('leave.store');
    Route::get('/leave/history', [LeaveController::class, 'history'])->name('leave.history');
});

/*
|--------------------------------------------------------------------------
| Admin Only Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->group(function () {

    /*
    | Staff Management
    */
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff/store', [StaffController::class, 'store'])->name('staff.store');

    /*
    | Leave Management
    */
    Route::get('/admin/leaves', [LeaveController::class, 'adminIndex'])->name('leave.admin');

    Route::post('/leave/approve/{id}', [LeaveController::class, 'approve'])->name('leave.approve');
    Route::post('/leave/reject/{id}', [LeaveController::class, 'reject'])->name('leave.reject');

    /*
    | Leave Transactions (Admin)
    */
    Route::get('/admin/leave-transactions', [LeaveController::class, 'adminTransactions'])
        ->name('leave.transactions');

    /*
    | Export Leave Requests
    */
    Route::get('/admin/leaves/export', [LeaveController::class, 'export'])
        ->name('leave.export');

    /*
    | Export Leave Transactions
    */
    Route::get('/admin/leave-transactions/export', [LeaveController::class, 'exportTransactions'])
        ->name('leave.export.transactions');

    Route::get('/admin/payroll-summary', [LeaveController::class, 'payrollSummary'])
        ->name('leave.payroll.summary');

});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
