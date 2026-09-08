<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * How the daily WhatsApp attendance report went on a given day.
 */
class DailyReportRun extends Model
{
    protected $fillable = [
        'report_date',
        'status',
        'attempts',
        'sent_count',
        'failed_count',
        'last_attempt_at',
        'sent_at',
        'last_error',
        'manual',
    ];

    protected $casts = [
        'report_date'     => 'date',
        'last_attempt_at' => 'datetime',
        'sent_at'         => 'datetime',
        'manual'          => 'boolean',
    ];

    public function wasSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Today's row, created on first use.
     *
     * The lookup has to be by date rather than by raw equality: the date cast
     * writes "2026-08-10 00:00:00", so comparing against "2026-08-10" would
     * never match the row that is already there and every retry would try to
     * insert a second one.
     */
    public static function forDate(string $date): self
    {
        return static::whereDate('report_date', $date)->first()
            ?? static::create(['report_date' => $date, 'status' => 'pending']);
    }
}
