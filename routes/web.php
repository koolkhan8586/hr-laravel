<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\SalaryController;

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
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| AUTHENTICATED USER ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    /* Profile */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /* Attendance (Employee) */
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockin');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockout');

    /* Leave (Employee) */
    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::get('/leave/apply', [LeaveController::class, 'create'])->name('leave.create');
    Route::post('/leave/store', [LeaveController::class, 'store'])->name('leave.store');
    Route::get('/leave/history', [LeaveController::class, 'history'])->name('leave.history');

    /* Loans (Employee) */
    Route::get('/my-loans', [LoanController::class, 'myLoan'])->name('loan.my');
    Route::get('/loan/apply', [LoanController::class, 'apply'])->name('loan.apply');
    Route::post('/loan/store-request', [LoanController::class, 'storeRequest'])->name('loan.store.request');

    /* Salary (Employee) */
    Route::get('/salary', [SalaryController::class,'employeeIndex'])->name('salary.index');
    Route::get('/salary/download/{id}', [SalaryController::class,'download'])->name('salary.download');
});

/*
|--------------------------------------------------------------------------
| ADMIN SECTION
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Staff Management (UNCHANGED)
    |--------------------------------------------------------------------------
    */
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff/store', [StaffController::class, 'store'])->name('staff.store');
    Route::post('/staff/import', [StaffController::class, 'import'])->name('staff.import');
    Route::get('/staff/edit/{id}', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/update/{id}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/delete/{id}', [StaffController::class, 'destroy'])->name('staff.destroy');
    Route::post('/staff/reset-password/{id}', [StaffController::class, 'resetPassword'])->name('staff.reset.password');
    Route::get('/staff/sample', [StaffController::class,'downloadSample'])->name('staff.sample');
    Route::post('/staff/bulk-delete',[StaffController::class,'bulkDelete'])->name('staff.bulk.delete');
    Route::post('/staff/bulk-email',[StaffController::class,'bulkEmail'])->name('staff.bulk.email');
    Route::post('/staff/toggle/{id}',[StaffController::class,'toggleStatus'])->name('staff.toggle');
    Route::get('/staff/{id}/view',[StaffController::class,'view'])->name('staff.view');
    Route::get('/staff/export',[StaffController::class, 'export'])->name('staff.export');

    /*
|--------------------------------------------------------------------------
| Admin Attendance Management
|--------------------------------------------------------------------------
*/

Route::get('/attendance',
    [AttendanceController::class,'adminIndex']
)->name('attendance.index');

Route::get('/attendance/export',
    [AttendanceController::class,'export']
)->name('attendance.export');

Route::get('/attendance/analytics/{month}',
    [AttendanceController::class,'analytics']
)->name('attendance.analytics');

Route::get('/attendance/create',
    [AttendanceController::class,'create']
)->name('attendance.create');

Route::post('/attendance/store',
    [AttendanceController::class,'store']
)->name('attendance.store');

Route::get('/attendance/{id}/edit',
    [AttendanceController::class,'edit']
)->name('attendance.edit');

Route::put('/attendance/{id}',
    [AttendanceController::class,'update']
)->name('attendance.update');

Route::delete('/attendance/{id}',
    [AttendanceController::class,'destroy']
)->name('attendance.delete');

    


    /*
    |--------------------------------------------------------------------------
    | Leave Management (Admin) (UNCHANGED)
    |--------------------------------------------------------------------------
    */
    Route::get('/leaves', [LeaveController::class, 'adminIndex'])->name('leave.index');
    Route::post('/leave/approve/{id}', [LeaveController::class, 'approve'])->name('leave.approve');
    Route::post('/leave/reject/{id}', [LeaveController::class, 'reject'])->name('leave.reject');
    Route::get('/leave-transactions', [LeaveController::class, 'adminTransactions'])->name('leave.transactions');
    Route::get('/leave-transactions/export', [LeaveController::class, 'exportTransactions'])->name('leave.transactions.export');
    Route::get('/leave/export', [LeaveController::class, 'export'])->name('leave.export');
    Route::post('/leave/revert/{id}', [LeaveController::class, 'revert'])->name('leave.revert');
    Route::delete('/leave/delete/{id}', [LeaveController::class, 'destroy'])->name('leave.delete');
    Route::get('/payroll-summary', [LeaveController::class, 'payrollSummary'])->name('payroll.summary');
    Route::get('/leave/create', [LeaveController::class, 'adminCreate'])->name('leave.create');
    Route::post('/leave/store', [LeaveController::class, 'adminStore'])->name('leave.store');
    Route::get('/leave/{id}/edit', [LeaveController::class, 'adminEdit'])->name('leave.edit');
    Route::put('/leave/{id}', [LeaveController::class, 'adminUpdate'])->name('leave.update');

    /*
    |--------------------------------------------------------------------------
    | Loan Management (Admin) (UNCHANGED)
    |--------------------------------------------------------------------------
    */
    Route::get('/loans', [LoanController::class, 'index'])->name('loan.index');
    Route::get('/loans/create', [LoanController::class, 'create'])->name('loan.create');
    Route::post('/loans/store', [LoanController::class, 'store'])->name('loan.store');
    Route::post('/loans/approve/{id}', [LoanController::class, 'approve'])->name('loan.approve');
    Route::post('/loans/reject/{id}', [LoanController::class, 'reject'])->name('loan.reject');
    Route::get('/loans/{id}/edit', [LoanController::class, 'edit'])->name('loan.edit');
    Route::put('/loans/{id}', [LoanController::class, 'update'])->name('loan.update');
    Route::delete('/loans/{id}', [LoanController::class, 'destroy'])->name('loan.delete');
    Route::get('/loans/export', [LoanController::class,'export'])->name('loan.export');
    Route::get('/loans/import-form', [LoanController::class,'importForm'])->name('loan.import.form');
    Route::post('/loans/import', [LoanController::class,'import'])->name('loan.import');

    /*
    |--------------------------------------------------------------------------
    | Salary Management (Admin) (UNCHANGED)
    |--------------------------------------------------------------------------
    */
    Route::get('/salary', [SalaryController::class,'index'])->name('salary.index');
    Route::get('/salary/create', [SalaryController::class,'create'])->name('salary.create');
    Route::post('/salary/store', [SalaryController::class,'store'])->name('salary.store');
    Route::get('/salary/export', [SalaryController::class,'export'])->name('salary.export');
    Route::get('/salary/import-form', [SalaryController::class,'importForm'])->name('salary.import.form');
    Route::post('/salary/import', [SalaryController::class,'import'])->name('salary.import');
    Route::get('/salary/sample', [SalaryController::class,'downloadSample'])->name('salary.sample');
    Route::post('/salary/bulk-post', [SalaryController::class,'bulkPost'])->name('salary.bulk.post');
    Route::post('/salary/bulk-unpost', [SalaryController::class,'bulkUnpost'])->name('salary.bulk.unpost');
    Route::post('/salary/bulk-delete', [SalaryController::class,'bulkDelete'])->name('salary.bulk.delete');
    Route::post('/salary/post-all-drafts', [SalaryController::class,'postAllDrafts'])->name('salary.post.all');
    Route::post('/salary/post/{id}', [SalaryController::class,'post'])->name('salary.post');
    Route::post('/salary/unpost/{id}', [SalaryController::class,'unpost'])->name('salary.unpost');
    Route::get('/salary/{id}/edit', [SalaryController::class,'edit'])->name('salary.edit');
    Route::put('/salary/{id}', [SalaryController::class,'update'])->name('salary.update');
    Route::delete('/salary/{id}', [SalaryController::class,'destroy'])->name('salary.delete');
    Route::get('/salary/{id}', [SalaryController::class,'show'])->name('salary.show');
});
