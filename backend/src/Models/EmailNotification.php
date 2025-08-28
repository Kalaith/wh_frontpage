<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class EmailNotification extends Model
{
    protected $table = 'email_notifications';
    
    protected $fillable = [
        'user_id',
        'type',
        'subject',
        'message',
        'metadata',
        'status',
        'sent_at',
        'error_message'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'metadata' => 'array',
        'sent_at' => 'datetime'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function markAsSent()
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->error_message = null;
        $this->save();
    }

    public function markAsFailed($errorMessage)
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;
        $this->save();
    }

    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'subject' => $this->subject,
            'status' => $this->status,
            'sent_at' => $this->sent_at ? $this->sent_at->format('M j, Y g:i A') : null,
            'created_at' => $this->created_at ? $this->created_at->format('M j, Y g:i A') : null,
        ];
    }

    // Static methods
    public static function createNotification($userId, $type, $subject, $message, $metadata = [])
    {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'subject' => $subject,
            'message' => $message,
            'metadata' => $metadata,
            'status' => 'pending'
        ]);
    }

    public static function createFeatureApprovedNotification($feature)
    {
        return self::createNotification(
            $feature->user_id,
            'feature_approved',
            'Your Feature Request Has Been Approved!',
            self::generateFeatureApprovedMessage($feature),
            [
                'feature_id' => $feature->id,
                'feature_title' => $feature->title,
                'approval_notes' => $feature->approval_notes
            ]
        );
    }

    public static function createFeatureRejectedNotification($feature)
    {
        return self::createNotification(
            $feature->user_id,
            'feature_rejected',
            'Feature Request Update',
            self::generateFeatureRejectedMessage($feature),
            [
                'feature_id' => $feature->id,
                'feature_title' => $feature->title,
                'rejection_reason' => $feature->approval_notes
            ]
        );
    }

    public static function createDailyReminderNotification($user)
    {
        if (!$user->can_claim_daily) return null;

        return self::createNotification(
            $user->id,
            'daily_reminder',
            'Don\'t Forget Your Daily Eggs! 🥚',
            self::generateDailyReminderMessage($user),
            ['eggs_available' => 100]
        );
    }

    public static function createWeeklyDigestNotification($user, $digestData)
    {
        return self::createNotification(
            $user->id,
            'weekly_digest',
            'Your Weekly Feature Request Summary',
            self::generateWeeklyDigestMessage($digestData),
            $digestData
        );
    }

    private static function generateFeatureApprovedMessage($feature)
    {
        $message = "Great news! Your feature request '{$feature->title}' has been approved and is now available for community voting.\n\n";
        
        if ($feature->approval_notes) {
            $message .= "Admin Notes: {$feature->approval_notes}\n\n";
        }
        
        $message .= "What happens next:\n";
        $message .= "• Community members can now vote on your feature with their eggs\n";
        $message .= "• Features with more eggs get higher priority for development\n";
        $message .= "• You'll be notified of any status updates\n\n";
        $message .= "Thank you for contributing to WebHatchery!\n\n";
        $message .= "View your feature: [Feature Link Here]";
        
        return $message;
    }

    private static function generateFeatureRejectedMessage($feature)
    {
        $message = "We've reviewed your feature request '{$feature->title}' and unfortunately it doesn't meet our current criteria for approval.\n\n";
        
        if ($feature->approval_notes) {
            $message .= "Reason: {$feature->approval_notes}\n\n";
        }
        
        $message .= "Don't be discouraged! You can:\n";
        $message .= "• Revise and resubmit your request\n";
        $message .= "• Check our feature request guidelines\n";
        $message .= "• Vote on other community features\n\n";
        $message .= "Note: The 100 eggs spent on this request are not refunded, as they help fund the review process.\n\n";
        $message .= "Thank you for your participation!";
        
        return $message;
    }

    private static function generateDailyReminderMessage($user)
    {
        return "Hello {$user->display_name},\n\n" .
               "Don't forget to claim your daily 100 eggs! 🥚\n\n" .
               "Current balance: {$user->egg_balance} eggs\n\n" .
               "Use your eggs to:\n" .
               "• Create new feature requests (100 eggs)\n" .
               "• Vote on community features\n" .
               "• Support your favorite projects\n\n" .
               "Claim your eggs now: [Dashboard Link]";
    }

    private static function generateWeeklyDigestMessage($digestData)
    {
        $message = "Here's your weekly WebHatchery summary:\n\n";
        
        $message .= "🎯 Your Activity:\n";
        $message .= "• Features created: {$digestData['user_features_created']}\n";
        $message .= "• Votes cast: {$digestData['user_votes_cast']}\n";
        $message .= "• Eggs spent: {$digestData['user_eggs_spent']}\n\n";
        
        $message .= "🏆 Community Highlights:\n";
        $message .= "• New features this week: {$digestData['new_features_count']}\n";
        $message .= "• Most voted feature: {$digestData['top_feature_title']} ({$digestData['top_feature_eggs']} eggs)\n";
        $message .= "• Active voters: {$digestData['active_voters']}\n\n";
        
        $message .= "💡 Trending Features:\n";
        foreach ($digestData['trending_features'] as $feature) {
            $message .= "• {$feature['title']} - {$feature['eggs']} eggs\n";
        }
        
        $message .= "\n🥚 Don't forget to claim your daily eggs!\n";
        $message .= "Visit your dashboard: [Dashboard Link]";
        
        return $message;
    }

    public static function createTable()
    {
        if (!Capsule::schema()->hasTable('email_notifications')) {
            Capsule::schema()->create('email_notifications', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->string('type');
                $table->string('subject');
                $table->text('message');
                $table->json('metadata')->nullable();
                $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['status', 'created_at']);
                $table->index(['user_id', 'type']);
            });
        }
    }
}

function now() {
    return new \DateTime();
}