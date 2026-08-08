<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_code',
        'name',
        'class',
        'approval_status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'qr_code',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::created(function (Student $student) {
            if (! $student->student_code) {
                $student->updateQuietly([
                    'student_code' => 'STU'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function child(): HasOne
    {
        return $this->hasOne(Children::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
