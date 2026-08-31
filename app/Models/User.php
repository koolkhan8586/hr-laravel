<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\OfficeLocation;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | Fillable (EMPLOYEE SAFE FIELDS ONLY)
    |--------------------------------------------------------------------------
    |
    | Only fields that employee is allowed to update
    | Admin updates should be handled manually in controllers
    |
    */

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',

        // Set by staff management. Without these the values are silently
        // dropped on save, because a non-empty fillable list wins over guarded.
        'employee_code',
        'cnic',
        'office_location_id',
        'allow_anywhere_attendance',
        'attendance_override_until',
        'tracks_attendance',

        // Payroll / salary sheet details
        'salary_category',
        'bank_account_no',
        'new_account_no',
        'bank_payee_id',
        'can_manage_salary',
        'can_manage_loan',
    ];

    /*
    |--------------------------------------------------------------------------
    | Guarded (PROTECTED FIELDS)
    |--------------------------------------------------------------------------
    |
    | These cannot be mass-assigned by employee
    |
    */

    protected $guarded = [
        'role',
        'annual_leave_balance',
    ];

    /*
    |--------------------------------------------------------------------------
    | Hidden
    |--------------------------------------------------------------------------
    */

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tracks_attendance' => 'boolean',
            'can_manage_salary' => 'boolean',
            'can_manage_loan' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Attendance tracking
    |--------------------------------------------------------------------------
    |
    | Payroll-only employees are paid every month but never mark attendance
    | and never apply for leave here, so they must stay out of the attendance
    | figures instead of showing up absent for every working day.
    |
    */

    /**
     * Employees whose attendance is actually followed.
     *
     * Rows written before the flag existed have no value at all, so a null
     * has to read as "tracked" - the default for everybody.
     */
    public function scopeTracked($query)
    {
        return $query->where(function ($q) {
            $q->where('tracks_attendance', true)
              ->orWhereNull('tracks_attendance');
        });
    }

    public function scopePayrollOnly($query)
    {
        return $query->where('tracks_attendance', false);
    }

    /**
     * Employees still working — excludes staff marked as left / resigned.
     */
    public function scopeEmployed($query)
    {
        return $query->whereHas('staff', fn ($q) => $q->where('status', 'active'));
    }

    /**
     * Who should appear on daily attendance reports and absence lists.
     */
    public function scopeForAttendanceRoster($query)
    {
        return $query->tracked()->employed();
    }

    public function isEmployed(): bool
    {
        return $this->staff?->isActive() ?? true;
    }

    public function tracksAttendance(): bool
    {
        return $this->tracks_attendance === null
            ? true
            : (bool) $this->tracks_attendance;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    /** Employee whose account this person's salary is credited into. */
    public function bankPayee()
    {
        return $this->belongsTo(User::class, 'bank_payee_id');
    }

    /** Employees whose salary is credited into this person's account. */
    public function bankPayeeFor()
    {
        return $this->hasMany(User::class, 'bank_payee_id');
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function leaveBalance()
    {
        return $this->hasOne(LeaveBalance::class);
    }


    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function officeLocation()
{
    return $this->belongsTo(OfficeLocation::class);
}

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function weeklySchedules()
{
    return $this->hasMany(\App\Models\WeeklySchedule::class);
}

    public function isEmployee()
    {
        return $this->role === 'employee';
    }

    public function isManager()
    {
        return $this->role === 'manager';
    }

    /** Admin, or staff granted Salary Management access. */
    public function canManageSalary(): bool
    {
        return $this->role === 'admin' || (bool) $this->can_manage_salary;
    }

    /** Admin, manager, or staff granted Loan Management access. */
    public function canManageLoans(): bool
    {
        return in_array($this->role, ['admin', 'manager'], true)
            || (bool) $this->can_manage_loan;
    }
}
