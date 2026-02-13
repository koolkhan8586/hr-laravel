<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LeaveController;

/*
|--------------------------------------------------------------------------
| Public Route
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
| Auth Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/my-loan', [LoanController::class, 'myLoan'])->name('loan.my');

});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Attendance (All Auth Users)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockin');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockout');

});

/*
|--------------------------------------------------------------------------
| Employee Leave
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::get('/leave/apply', [LeaveController::class, 'create'])->name('leave.create');
    Route::post('/leave/store', [LeaveController::class, 'store'])->name('leave.store');
    Route::get('/leave/history', [LeaveController::class, 'history'])->name('leave.history');

});

/*
|--------------------------------------------------------------------------
| Admin Section (Staff + Leave + Payroll)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Staff Management
    |--------------------------------------------------------------------------
    */

    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff/store', [StaffController::class, 'store'])->name('staff.store');

    Route::post('/staff/import', [StaffController::class, 'import'])->name('staff.import');

    Route::get('/staff/edit/{id}', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/update/{id}', [StaffController::class, 'update'])->name('staff.update');

    Route::delete('/staff/delete/{id}', [StaffController::class, 'destroy'])->name('staff.destroy');

    // ✅ Reset Password Route (IMPORTANT FIX)
    Route::get('/staff/reset-password/{id}', 
        [StaffController::class, 'resetPassword'])
        ->name('staff.reset.password');


    /*
    |--------------------------------------------------------------------------
    | Leave Management
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/leaves', [LeaveController::class, 'adminIndex'])->name('leave.admin');

    Route::post('/leave/approve/{id}', [LeaveController::class, 'approve'])->name('leave.approve');
    Route::post('/leave/reject/{id}', [LeaveController::class, 'reject'])->name('leave.reject');


    /*
    |--------------------------------------------------------------------------
    | Leave Transactions
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/leave-transactions',
        [LeaveController::class, 'adminTransactions'])
        ->name('leave.transactions');

    Route::get('/admin/leave-transactions/export',
        [LeaveController::class, 'exportTransactions'])
        ->name('leave.export.transactions');


    /*
    |--------------------------------------------------------------------------
    | Leave Export
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/leaves/export',
        [LeaveController::class, 'export'])
        ->name('leave.export');


    /*
    |--------------------------------------------------------------------------
    | Payroll Summary
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/payroll-summary',
        [LeaveController::class, 'payrollSummary'])
        ->name('payroll.summary');

    Route::get('/admin/loans', [LoanController::class, 'index'])->name('loan.index');
Route::get('/admin/loans/create', [LoanController::class, 'create'])->name('loan.create');
Route::post('/admin/loans/store', [LoanController::class, 'store'])->name('loan.store');


});
