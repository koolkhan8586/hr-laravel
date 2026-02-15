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

    /* Attendance */
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
    ->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Staff Management
    |--------------------------------------------------------------------------
    */
    Route::get('/staff', [StaffController::class, 'index'])->name('admin.staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('admin.staff.create');
    Route::post('/staff/store', [StaffController::class, 'store'])->name('admin.staff.store');
    Route::post('/staff/import', [StaffController::class, 'import'])->name('admin.staff.import');
    Route::get('/staff/edit/{id}', [StaffController::class, 'edit'])->name('admin.staff.edit');
    Route::put('/staff/update/{id}', [StaffController::class, 'update'])->name('admin.staff.update');
    Route::delete('/staff/delete/{id}', [StaffController::class, 'destroy'])->name('admin.staff.destroy');
    Route::post('/staff/reset-password/{id}', [StaffController::class, 'resetPassword'])
        ->name('admin.staff.reset.password');


    /*
    |--------------------------------------------------------------------------
    | Leave Management (Admin)
    |--------------------------------------------------------------------------
    */
    Route::get('/leaves', [LeaveController::class, 'adminIndex'])->name('admin.leave.index');
    Route::post('/leave/approve/{id}', [LeaveController::class, 'approve'])->name('admin.leave.approve');
    Route::post('/leave/reject/{id}', [LeaveController::class, 'reject'])->name('admin.leave.reject');

    Route::get('/leave-transactions', [LeaveController::class, 'adminTransactions'])
        ->name('admin.leave.transactions');

    Route::get('/leave-transactions/export', [LeaveController::class, 'exportTransactions'])
        ->name('admin.leave.transactions.export');

    Route::get('/leave/export', [LeaveController::class, 'export'])
        ->name('admin.leave.export');

    Route::post('/leave/revert/{id}', [LeaveController::class, 'revert'])
        ->name('admin.leave.revert');

    Route::delete('/leave/delete/{id}', [LeaveController::class, 'destroy'])
        ->name('admin.leave.delete');

    Route::get('/payroll-summary', [LeaveController::class, 'payrollSummary'])
        ->name('admin.payroll.summary');


    /*
    |--------------------------------------------------------------------------
    | Loan Management (Admin)
    |--------------------------------------------------------------------------
    */
    Route::get('/loans', [LoanController::class, 'index'])->name('admin.loan.index');
    Route::get('/loans/create', [LoanController::class, 'create'])->name('admin.loan.create');
    Route::post('/loans/store', [LoanController::class, 'store'])->name('admin.loan.store');
    Route::post('/loans/approve/{id}', [LoanController::class, 'approve'])->name('admin.loan.approve');
    Route::post('/loans/reject/{id}', [LoanController::class, 'reject'])->name('admin.loan.reject');
    Route::get('/loans/{id}/edit', [LoanController::class, 'edit'])->name('admin.loan.edit');
    Route::put('/loans/{id}', [LoanController::class, 'update'])->name('admin.loan.update');
    Route::delete('/loans/{id}', [LoanController::class, 'destroy'])->name('admin.loan.delete');


    /*
    |--------------------------------------------------------------------------
    | Salary Management (Admin)
    |--------------------------------------------------------------------------
    */

    Route::get('/salary', [SalaryController::class,'index'])
        ->name('admin.salary.index');

    Route::get('/salary/create', [SalaryController::class,'create'])
        ->name('admin.salary.create');

    Route::post('/salary/store', [SalaryController::class,'store'])
        ->name('admin.salary.store');

    /* IMPORTANT: Static routes BEFORE {id} */

    Route::get('/salary/export', [SalaryController::class,'export'])
        ->name('admin.salary.export');

    Route::get('/salary/import-form', [SalaryController::class,'importForm'])
        ->name('admin.salary.import.form');

    Route::post('/salary/import', [SalaryController::class,'import'])
        ->name('admin.salary.import');

    Route::post('/salary/bulk-post', [SalaryController::class,'bulkPost'])
        ->name('admin.salary.bulk.post');

    Route::post('/salary/bulk-unpost', [SalaryController::class,'bulkUnpost'])
        ->name('admin.salary.bulk.unpost');

    Route::post('/salary/bulk-delete', [SalaryController::class,'bulkDelete'])
        ->name('admin.salary.bulk.delete');

    Route::post('/salary/post-all-drafts', [SalaryController::class,'postAllDrafts'])
        ->name('admin.salary.post.all');

    Route::post('/salary/post/{id}', [SalaryController::class,'post'])
        ->name('admin.salary.post');

    Route::post('/salary/unpost/{id}', [SalaryController::class,'unpost'])
        ->name('admin.salary.unpost');

    /* Dynamic routes LAST */

    Route::get('/salary/{id}/edit', [SalaryController::class,'edit'])
        ->name('admin.salary.edit');

    Route::put('/salary/{id}', [SalaryController::class,'update'])
        ->name('admin.salary.update');

    Route::delete('/salary/{id}', [SalaryController::class,'destroy'])
        ->name('admin.salary.delete');

    Route::get('/salary/{id}', [SalaryController::class,'show'])
        ->name('admin.salary.show');

        

});
