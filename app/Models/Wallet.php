<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:0',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeSystem(Builder $query, string $slug): Builder
    {
        return $query
            ->whereNull('user_id')
            ->where('slug', $slug);
    }

    public function isSystem(): bool
    {
        return $this->user_id === null && $this->slug !== null;
    }
}

