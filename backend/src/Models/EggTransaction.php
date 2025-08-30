<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class EggTransaction extends Model
{
    protected $table = 'egg_transactions';
    
    protected $fillable = [
        'user_id',
        'amount',
        'transaction_type',
        'description',
        'reference_id',
        'reference_type'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'amount' => 'integer',
        'reference_id' => 'integer'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeEarnings($query)
    {
        return $query->where('amount', '>', 0);
    }

    public function scopeSpending($query)
    {
        return $query->where('amount', '<', 0);
    }

    // Methods
    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'type' => $this->transaction_type,
            'description' => $this->description,
            'reference_id' => $this->reference_id,
            'reference_type' => $this->reference_type,
            'created_at' => $this->created_at ? $this->created_at->format('M j, Y g:i A') : null,
        ];
    }

    // Static methods
    public static function getUserBalance($userId)
    {
        return self::where('user_id', $userId)->sum('amount');
    }

    public static function getUserTransactions($userId, $limit = 50)
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return $transaction->toApiArray();
            })
            ->toArray();
    }

    public static function getTransactionStats($userId = null)
    {
        $query = self::query();
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $stats = [
            'total_earned' => $query->clone()->where('amount', '>', 0)->sum('amount'),
            'total_spent' => abs($query->clone()->where('amount', '<', 0)->sum('amount')),
            'total_transactions' => $query->clone()->count(),
            'daily_rewards_claimed' => $query->clone()->byType('daily_reward')->count(),
            'features_created' => $query->clone()->byType('spend')->where('description', 'like', '%feature%')->count(),
            'votes_cast' => $query->clone()->byType('vote')->count(),
        ];

        $stats['current_balance'] = $stats['total_earned'] - $stats['total_spent'];

        return $stats;
    }

    public static function createTable()
    {
        if (!Capsule::schema()->hasTable('egg_transactions')) {
            Capsule::schema()->create('egg_transactions', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->integer('amount');
                $table->enum('transaction_type', ['earn', 'spend', 'vote', 'daily_reward', 'registration_bonus', 'kofi_reward', 'admin_adjustment']);
                $table->string('description');
                $table->integer('reference_id')->nullable();
                $table->string('reference_type')->nullable();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['user_id', 'created_at']);
            });
        }
    }
}