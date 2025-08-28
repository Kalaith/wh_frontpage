<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class FeatureVote extends Model
{
    protected $table = 'feature_votes';
    
    protected $fillable = [
        'user_id',
        'feature_id',
        'eggs_allocated'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'feature_id' => 'integer',
        'eggs_allocated' => 'integer'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function featureRequest()
    {
        return $this->belongsTo(FeatureRequest::class, 'feature_id');
    }

    // Methods
    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'feature_id' => $this->feature_id,
            'eggs_allocated' => $this->eggs_allocated,
            'created_at' => $this->created_at ? $this->created_at->format('M j, Y') : null,
        ];
    }

    // Static methods
    public static function castVote($userId, $featureId, $eggAmount)
    {
        // Check if user has enough eggs
        $user = User::find($userId);
        if (!$user || $user->egg_balance < $eggAmount) {
            return ['success' => false, 'message' => 'Insufficient eggs'];
        }

        // Check if feature exists and is approved
        $feature = FeatureRequest::find($featureId);
        if (!$feature || $feature->status !== 'approved') {
            return ['success' => false, 'message' => 'Feature not found or not available for voting'];
        }

        // Check if user already voted
        $existingVote = self::where('user_id', $userId)
                           ->where('feature_id', $featureId)
                           ->first();

        if ($existingVote) {
            // Update existing vote
            $oldAmount = $existingVote->eggs_allocated;
            $difference = $eggAmount - $oldAmount;
            
            if ($difference > 0 && $user->egg_balance < $difference) {
                return ['success' => false, 'message' => 'Insufficient eggs for vote increase'];
            }

            // Update vote
            $existingVote->eggs_allocated = $eggAmount;
            $existingVote->save();

            // Update user's egg balance
            if ($difference != 0) {
                $user->decrement('egg_balance', $difference);
                
                // Record transaction
                EggTransaction::create([
                    'user_id' => $userId,
                    'amount' => -$difference,
                    'transaction_type' => 'vote',
                    'description' => "Vote adjustment for feature: {$feature->title}",
                    'reference_id' => $featureId,
                    'reference_type' => 'feature_request'
                ]);
            }

            // Update feature totals
            $feature->total_eggs = self::where('feature_id', $featureId)->sum('eggs_allocated');
            $feature->save();

            return ['success' => true, 'message' => 'Vote updated successfully'];
        } else {
            // Create new vote
            $vote = self::create([
                'user_id' => $userId,
                'feature_id' => $featureId,
                'eggs_allocated' => $eggAmount
            ]);

            // Update user's egg balance
            $user->decrement('egg_balance', $eggAmount);

            // Record transaction
            EggTransaction::create([
                'user_id' => $userId,
                'amount' => -$eggAmount,
                'transaction_type' => 'vote',
                'description' => "Vote for feature: {$feature->title}",
                'reference_id' => $featureId,
                'reference_type' => 'feature_request'
            ]);

            // Update feature totals
            $feature->increment('total_eggs', $eggAmount);
            $feature->increment('vote_count');

            return ['success' => true, 'message' => 'Vote cast successfully'];
        }
    }

    public static function getUserVotes($userId)
    {
        return self::where('user_id', $userId)
            ->with('featureRequest')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($vote) {
                $data = $vote->toApiArray();
                if ($vote->featureRequest) {
                    $data['feature'] = [
                        'id' => $vote->featureRequest->id,
                        'title' => $vote->featureRequest->title,
                        'status' => $vote->featureRequest->status,
                        'total_eggs' => $vote->featureRequest->total_eggs
                    ];
                }
                return $data;
            })
            ->toArray();
    }

    public static function getFeatureVotes($featureId)
    {
        return self::where('feature_id', $featureId)
            ->with('user:id,username,display_name')
            ->orderBy('eggs_allocated', 'desc')
            ->get()
            ->map(function ($vote) {
                $data = $vote->toApiArray();
                if ($vote->user) {
                    $data['user'] = [
                        'username' => $vote->user->username,
                        'display_name' => $vote->user->display_name
                    ];
                }
                return $data;
            })
            ->toArray();
    }

    public static function createTable()
    {
        if (!Capsule::schema()->hasTable('feature_votes')) {
            Capsule::schema()->create('feature_votes', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->bigInteger('feature_id')->unsigned();
                $table->integer('eggs_allocated');
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('feature_id')->references('id')->on('feature_requests')->onDelete('cascade');
                $table->unique(['user_id', 'feature_id']);
            });
        }
    }
}