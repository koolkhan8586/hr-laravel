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

        // Payroll / salary sheet details
        'salary_category',
        'bank_account_no',
        'new_account_no',
        'bank_payee_id',
        'can_manage_salary',
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
        ];
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
}
