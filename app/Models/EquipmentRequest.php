<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EquipmentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'equipment_id',
        'purpose',
        'requested_from',
        'requested_until',
        'status',
        'returned_at',
        'return_condition',
        'return_notes',
    ];

    protected $casts = [
        'requested_from' => 'datetime',
        'requested_until' => 'datetime',
        'returned_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_RETURNED = 'returned';    // Relationships
    public function user()
    {
        return $this->belongsTo(Ruser::class, 'user_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    // Status check methods
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isReturned()
    {
        return !is_null($this->returned_at);
    }

    public function isOverdue()
    {
        return $this->isApproved() 
            && !$this->isReturned() 
            && Carbon::now()->isAfter($this->requested_until);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeActive($query)
    {
        return $query->approved()->whereNull('returned_at');
    }

    public function scopeOverdue($query)
    {
        return $query->active()->where('requested_until', '<', Carbon::now());
    }

    public function scopeReturned($query)
    {
        return $query->whereNotNull('returned_at');
    }
} 