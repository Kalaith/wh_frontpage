<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class User extends Model
{
    protected $table = 'users';
    
    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'display_name',
        'role',
        'egg_balance',
        'last_daily_reward',
        'is_verified',
        'verification_token',
        'email_verified_at'
    ];

    protected $casts = [
        'egg_balance' => 'integer',
        'is_verified' => 'boolean',
        'last_daily_reward' => 'datetime',
        'email_verified_at' => 'datetime'
    ];

    protected $hidden = [
        'password_hash',
        'verification_token'
    ];

    // Relationships
    public function featureRequests()
    {
        return $this->hasMany(FeatureRequest::class, 'user_id');
    }

    public function votes()
    {
        return $this->hasMany(FeatureVote::class, 'user_id');
    }

    public function eggTransactions()
    {
        return $this->hasMany(EggTransaction::class, 'user_id');
    }

    public function preferences()
    {
        return $this->hasOne(UserPreference::class, 'user_id');
    }

    // Methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function canClaimDailyReward()
    {
        if (!$this->last_daily_reward) {
            return true;
        }
        
        $lastReward = $this->last_daily_reward;
        $today = now();
        
        return $lastReward->format('Y-m-d') !== $today->format('Y-m-d');
    }

    public function claimDailyReward($amount = 100)
    {
        if (!$this->canClaimDailyReward()) {
            return false;
        }

        $this->increment('egg_balance', $amount);
        $this->update(['last_daily_reward' => now()]);

        // Record transaction
        EggTransaction::create([
            'user_id' => $this->id,
            'amount' => $amount,
            'transaction_type' => 'daily_reward',
            'description' => 'Daily egg reward',
        ]);

        return true;
    }

    public function spendEggs($amount, $description, $referenceId = null, $referenceType = null)
    {
        if ($this->egg_balance < $amount) {
            return false;
        }

        $this->decrement('egg_balance', $amount);

        EggTransaction::create([
            'user_id' => $this->id,
            'amount' => -$amount,
            'transaction_type' => 'spend',
            'description' => $description,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
        ]);

        return true;
    }

    public function awardEggs($amount, $type, $description, $referenceId = null, $referenceType = null)
    {
        $this->increment('egg_balance', $amount);

        EggTransaction::create([
            'user_id' => $this->id,
            'amount' => $amount,
            'transaction_type' => $type,
            'description' => $description,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
        ]);

        return true;
    }

    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'display_name' => $this->display_name,
            'role' => $this->role,
            'egg_balance' => $this->egg_balance,
            'is_verified' => $this->is_verified,
            'can_claim_daily' => $this->canClaimDailyReward(),
            'member_since' => $this->created_at ? $this->created_at->format('M j, Y') : null,
        ];
    }

    // Static methods
    public static function findByEmail($email)
    {
        return self::where('email', $email)->first();
    }

    public static function findByUsername($username)
    {
        return self::where('username', $username)->first();
    }

    public static function createUser(array $data)
    {
        $user = self::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'display_name' => $data['display_name'] ?? $data['username'],
            'role' => $data['role'] ?? 'user',
            'egg_balance' => 500, // New users get 500 eggs
            'is_verified' => false,
            'verification_token' => bin2hex(random_bytes(32)),
        ]);

        // Record registration bonus
        EggTransaction::create([
            'user_id' => $user->id,
            'amount' => 500,
            'transaction_type' => 'registration_bonus',
            'description' => 'Welcome bonus for new account',
        ]);

        return $user;
    }

    public static function createTable()
    {
        if (!Capsule::schema()->hasTable('users')) {
            Capsule::schema()->create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('username')->unique();
                $table->string('email')->unique();
                $table->string('password_hash');
                $table->string('display_name')->nullable();
                $table->enum('role', ['user', 'admin'])->default('user');
                $table->integer('egg_balance')->default(500);
                $table->timestamp('last_daily_reward')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->string('verification_token')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamps();
            });
        }
    }
}

function now() {
    return new \DateTime();
}