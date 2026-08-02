<?php

namespace App\Models;

use Database\Factories\SupplierProposalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProposal extends Model
{
    /** @use HasFactory<SupplierProposalFactory> */
    use HasFactory;

    protected $fillable = [
        'sourcing_request_id',
        'company_id',
        'created_by',
        'amount',
        'currency',
        'delivery_days',
        'message',
    ];

    /**
     * @return BelongsTo<SourcingRequest, $this>
     */
    public function sourcingRequest(): BelongsTo
    {
        return $this->belongsTo(SourcingRequest::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'delivery_days' => 'integer',
        ];
    }
}
