<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\EmployeeScheduleController;
use App\Http\Controllers\WeeklyScheduleController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\WorkFromHomeController;


/*
|--------------------------------------------------------------------------
| Public Route
|--------------------------------------------------------------------------
*/
Route::get('/', function () {

    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');

});

/*|                                                                          |
| -------------------------------------------------------------------------- |
| Dashboard                                                                  |
| -------------------------------------------------------------------------- |
| */                                                                         
Route::middleware(['auth', 'verified'])->group(function () {               


Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');


});

require **DIR**.'/auth.php';

 /* |                                                                        |
| -------------------------------------------------------------------------- |
| AUTHENTICATED USER ROUTES                                                  |
| -------------------------------------------------------------------------- |
| */                                                                         
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
Route::get('/loan/{id}/ledger', [LoanController::class,'employeeLedger'])->name('loan.ledger');

/* Employees Directory */
Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');

/* Salary (Employee) */
Route::get('/salary', [SalaryController::class,'employeeIndex'])->name('salary.index');
Route::get('/salary/download/{id}', [SalaryController::class,'download'])->name('salary.download');

/* Shift & Schedule */
Route::resource('shifts', ShiftController::class);
Route::resource('schedules', EmployeeScheduleController::class);

Route::get('/weekly-schedule', [WeeklyScheduleController::class,'create'])->name('weekly.schedule');
Route::post('/weekly-schedule', [WeeklyScheduleController::class,'store']);

Route::get('/weekly-schedules', [WeeklyScheduleController::class, 'index'])->name('weekly.schedules');
Route::get('/weekly-schedule/{user}/edit', [WeeklyScheduleController::class,'edit'])->name('weekly.edit');
Route::delete('/weekly-schedule/{user}', [WeeklyScheduleController::class,'delete'])->name('weekly.delete');

Route::get('/schedule-calendar', [WeeklyScheduleController::class, 'calendar'])->name('schedule.calendar');
Route::get('/schedule-editor', [WeeklyScheduleController::class,'editor'])->name('schedule.editor');
Route::post('/schedule-editor', [WeeklyScheduleController::class,'updateGrid'])->name('schedule.editor.update');

/* Holidays (Employee View Only) */
Route::get('/holidays', [HolidayController::class,'index'])->name('holidays.index');

/* Work From Home (Employee View Only) */
Route::get('/my-wfh', [WorkFromHomeController::class,'employeeWFH'])->name('employee.wfh');
```

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
)->name('attendance.destroy');

Route::get('/hr-calendar-events',
    [AdminAttendanceController::class,'calendarEvents']
)->name('calendar.events');

Route::get('/attendance/monthly/{user}/{month}',
    [AttendanceController::class,'downloadMonthlyAttendance']
)->name('attendance.monthly.download');

/*
|--------------------------------------------------------------------------
| Admin Attendance Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/attendance-dashboard',
    [AdminAttendanceController::class,'dashboard']
)->name('attendance.dashboard');

/*
|--------------------------------------------------------------------------
| Attendance List (Present, Late, Half Day, Leave, WFH, Absent)
|--------------------------------------------------------------------------
*/

Route::get('/attendance-list/{type}',
    [AdminAttendanceController::class,'attendanceList']
)->name('attendance.list');

/*
|--------------------------------------------------------------------------
| Live Attendance
|--------------------------------------------------------------------------
*/

Route::get('/live-attendance',
    [AdminAttendanceController::class,'liveAttendance']
);

Route::post('/attendance/manual-mark',
    [AdminAttendanceController::class,'manualMarkAttendance']
)->name('attendance.manual');

Route::get('/attendance-calendar',
    [AdminAttendanceController::class,'attendanceCalendar']
)->name('attendance.calendar');

Route::post('/allow-overtime',
    [AdminAttendanceController::class,'allowOvertime']
)->name('allow.overtime');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard',
    [DashboardController::class,'index']
)->name('dashboard');

/*
|--------------------------------------------------------------------------
| Holiday Management
|--------------------------------------------------------------------------
*/

Route::get('/holidays',
    [HolidayController::class,'index']
)->name('holidays.index');

Route::post('/holidays',
    [HolidayController::class,'store']
)->name('holidays.store');

Route::delete('/holidays/{id}',
    [HolidayController::class,'destroy']
)->name('holidays.delete');

/*
|--------------------------------------------------------------------------
| Work From Home Management
|--------------------------------------------------------------------------
*/

Route::get('/work-from-home',
    [WorkFromHomeController::class,'index']
)->name('wfh.index');

Route::post('/work-from-home',
    [WorkFromHomeController::class,'store']
)->name('wfh.store');

Route::delete('/work-from-home/{id}',
    [WorkFromHomeController::class,'destroy']
)->name('wfh.delete');

Route::get('/work-from-home/{id}/edit',
    [WorkFromHomeController::class,'edit']
)->name('wfh.edit');

Route::put('/work-from-home/{id}',
    [WorkFromHomeController::class,'update']
)->name('wfh.update');
    


    /*
    |--------------------------------------------------------------------------
    | Leave Management (Admin) (UNCHANGED)
    |--------------------------------------------------------------------------
    */
Route::get('/leaves', [LeaveController::class, 'adminIndex'])->name('leave.index');
Route::post('/leave/approve/{id}', [LeaveController::class, 'approve'])->name('leave.approve');
Route::post('/leave/reject/{id}', [LeaveController::class, 'reject'])->name('leave.reject');
Route::post('/leave/revert/{id}', [LeaveController::class, 'revert'])->name('leave.revert');
Route::delete('/leave/delete/{id}', [LeaveController::class, 'destroy'])->name('leave.delete');

Route::get('/leave-transactions', [LeaveController::class, 'adminTransactions'])->name('leave.transactions');
Route::get('/leave-transactions/export', [LeaveController::class, 'exportTransactions'])->name('leave.transactions.export');

Route::get('/leave-calendar',[LeaveController::class,'calendar'])->name('leave.calendar');

Route::get('/leave-allocation', [LeaveController::class, 'allocationIndex'])->name('leave.allocation.index');
Route::post('/leave-allocation/update/{id}', [LeaveController::class, 'updateAllocation'])->name('leave.allocation.update');

Route::post('/leave/recalculate', [LeaveController::class, 'recalculateBalances'])->name('leave.recalculate');
Route::post('/leave/reset-year',[LeaveController::class, 'resetYearlyBalance'])->name('leave.reset.year');

Route::get('/payroll-summary', [LeaveController::class, 'payrollSummary'])->name('payroll.summary');

Route::post('/leave/store', [LeaveController::class, 'store'])->name('leave.store');
    Route::get('/leave/{id}/edit', [LeaveController::class, 'adminEdit'])->name('leave.edit');
Route::post('/leave/bulk-allocation',[LeaveController::class, 'bulkAllocate'])->name('leave.bulk.allocate');

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
    Route::get('/loans/{id}/ledger',[LoanController::class,'ledger'])->name('loan.ledger');


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
    Route::post('/salary/import-confirm', [SalaryController::class,'confirmImport'])->name('salary.import.confirm');
});
