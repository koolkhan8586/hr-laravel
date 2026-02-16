<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Carbon\Carbon;

class AttendanceExport implements FromCollection
{
    protected $month;

    public function __construct($month)
    {
        $this->month = $month;
    }

    public function collection()
    {
        $month = Carbon::parse($this->month);

        return Attendance::with('user')
            ->whereMonth('clock_in',$month->month)
            ->whereYear('clock_in',$month->year)
            ->get()
            ->map(function($r){
                return [
                    'Employee'=>$r->user->name,
                    'Date'=>$r->clock_in,
                    'Hours'=>$r->total_hours,
                    'Status'=>$r->status
                ];
            });
    }
}
