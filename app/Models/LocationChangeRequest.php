<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An employee's request to mark attendance at a different office.
 */
class LocationChangeRequest extends Model
{
    protected $fillable = [
        'user_id',
        'office_location_id',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'decision_note',
        'applied_as',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function officeLocation()
    {
        return $this->belongsTo(OfficeLocation::class);
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /** Human wording for the decision, used on both screens. */
    public function outcome(): string
    {
        return match ($this->status) {
            'approved' => $this->applied_as === 'replaced'
                ? 'Approved - this is now your location'
                : 'Approved - added to your locations',
            'rejected' => 'Not approved',
            default    => 'Waiting for approval',
        };
    }
}
