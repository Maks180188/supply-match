<?php

namespace App\Models;

use App\Enums\SourcingRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourcingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'category_id',
        'created_by',
        'title',
        'description',
        'status',
        'submission_deadline',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SourcingRequestStatus::class,
            'submission_deadline' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(RequestKeyword::class);
    }
}
