<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    protected $fillable = [
        'booking_code',
        'user_id',
        'asset_id',
        'quantity',
        'borrow_date',
        'return_date',
        'returned_at',
        'purpose',
        'status',
        'return_condition',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'return_date' => 'date',
            'returned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['approved', 'borrowed'])
            && now()->startOfDay()->gt($this->return_date);
    }
}