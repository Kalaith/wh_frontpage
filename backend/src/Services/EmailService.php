<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmailNotification;
use App\Models\User;

class EmailService
{
    private $fromEmail;
    private $fromName;
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $enabled;

    public function __construct()
    {
        // Load email configuration from environment
        $this->fromEmail = $_ENV['EMAIL_FROM'] ?? 'noreply@webhatchery.au';
        $this->fromName = $_ENV['EMAIL_FROM_NAME'] ?? 'WebHatchery';
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? '';
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? 587;
        $this->smtpUsername = $_ENV['SMTP_USERNAME'] ?? '';
        $this->smtpPassword = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->enabled = filter_var($_ENV['EMAIL_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    }

    public function sendPendingNotifications($limit = 50)
    {
        if (!$this->enabled) {
            return ['sent' => 0, 'failed' => 0, 'message' => 'Email service disabled'];
        }

        $notifications = EmailNotification::pending()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            try {
                if ($this->sendNotification($notification)) {
                    $notification->markAsSent();
                    $sent++;
                } else {
                    $notification->markAsFailed('Unknown send error');
                    $failed++;
                }
            } catch (\Exception $e) {
                $notification->markAsFailed($e->getMessage());
                $failed++;
            }

            // Rate limiting - small delay between sends
            usleep(100000); // 100ms delay
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'message' => "Processed {$notifications->count()} notifications"
        ];
    }

    private function sendNotification(EmailNotification $notification)
    {
        if (!$notification->user) {
            throw new \Exception('User not found for notification');
        }

        $user = $notification->user;
        
        // Check user preferences (if implemented)
        // if (!$this->shouldSendToUser($user, $notification->type)) {
        //     return true; // Skip but mark as sent
        // }

        return $this->sendEmail(
            $user->email,
            $notification->subject,
            $notification->message,
            $this->generateHtmlContent($notification)
        );
    }

    private function sendEmail($to, $subject, $textContent, $htmlContent = null)
    {
        if (!$this->enabled) {
            return false;
        }

        // Using PHP's built-in mail function for simplicity
        // In production, you might want to use a library like PHPMailer or Symfony Mailer
        
        $headers = [
            'From' => "{$this->fromName} <{$this->fromEmail}>",
            'Reply-To' => $this->fromEmail,
            'X-Mailer' => 'WebHatchery Feature Request System',
            'MIME-Version' => '1.0'
        ];

        if ($htmlContent) {
            $boundary = uniqid();
            $headers['Content-Type'] = "multipart/alternative; boundary=\"{$boundary}\"";
            
            $message = "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $textContent . "\r\n\r\n";
            
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $htmlContent . "\r\n\r\n";
            
            $message .= "--{$boundary}--";
        } else {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
            $message = $textContent;
        }

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }

        return mail($to, $subject, $message, trim($headerString));
    }

    private function generateHtmlContent(EmailNotification $notification)
    {
        $displayName = $user->display_name ?: $user->username;
        $textContent = nl2br(htmlspecialchars($notification->message));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$notification->subject}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
                .logo { font-size: 24px; font-weight: bold; color: #2563eb; }
                .content { background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; }
                .footer { margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; font-size: 14px; color: #6b7280; }
                .button { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
                .egg-icon { font-size: 18px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='logo'>🥚 WebHatchery</div>
                <p>Feature Request System</p>
            </div>
            
            <div class='content'>
                <h2>{$notification->subject}</h2>
                <p>Hello {$displayName},</p>
                
                <div>{$textContent}</div>
                
                " . $this->generateNotificationSpecificContent($notification) . "
            </div>
            
            <div class='footer'>
                <p><strong>WebHatchery Feature Request System</strong></p>
                <p>This email was sent to {$user->email}. You can manage your email preferences in your dashboard.</p>
                <p>© " . date('Y') . " WebHatchery. All rights reserved.</p>
            </div>
        </body>
        </html>";
    }

    private function generateNotificationSpecificContent(EmailNotification $notification)
    {
        $baseUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000';
        
        switch ($notification->type) {
            case 'feature_approved':
                $featureId = $notification->metadata['feature_id'] ?? null;
                return "
                    <div style='background: #dcfce7; padding: 15px; border-radius: 6px; margin: 15px 0;'>
                        <p><strong>🎉 Your feature is now live and ready for community voting!</strong></p>
                        <a href='{$baseUrl}/features/{$featureId}' class='button'>View Feature</a>
                    </div>";
                    
            case 'feature_rejected':
                return "
                    <div style='background: #fef2f2; padding: 15px; border-radius: 6px; margin: 15px 0;'>
                        <p><strong>📝 Don't give up!</strong> You can revise and resubmit your request.</p>
                        <a href='{$baseUrl}/features/create' class='button'>Create New Feature</a>
                    </div>";
                    
            case 'daily_reminder':
                return "
                    <div style='background: #eff6ff; padding: 15px; border-radius: 6px; margin: 15px 0;'>
                        <p><strong>🥚 100 Free Eggs Waiting!</strong></p>
                        <p>Current Balance: {$notification->user->egg_balance} eggs</p>
                        <a href='{$baseUrl}/dashboard' class='button'>Claim Now</a>
                    </div>";
                    
            case 'weekly_digest':
                return "
                    <div style='background: #f0f9ff; padding: 15px; border-radius: 6px; margin: 15px 0;'>
                        <p><strong>📊 See what's trending this week</strong></p>
                        <a href='{$baseUrl}/features' class='button'>Browse Features</a>
                    </div>";
                    
            default:
                return "";
        }
    }

    public function sendTestEmail($to, $subject = 'Test Email from WebHatchery')
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Email service is disabled'];
        }

        $message = "This is a test email from the WebHatchery Feature Request System.\n\n";
        $message .= "If you received this email, the email service is working correctly.\n\n";
        $message .= "Sent at: " . date('Y-m-d H:i:s') . "\n";
        $message .= "Configuration:\n";
        $message .= "- From: {$this->fromEmail}\n";
        $message .= "- SMTP Host: " . ($this->smtpHost ?: 'PHP mail()') . "\n";

        try {
            $sent = $this->sendEmail($to, $subject, $message);
            return [
                'success' => $sent,
                'message' => $sent ? 'Test email sent successfully' : 'Failed to send test email'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error sending test email: ' . $e->getMessage()
            ];
        }
    }

    public function getQueueStats()
    {
        return [
            'pending' => EmailNotification::pending()->count(),
            'sent_today' => EmailNotification::sent()->whereDate('sent_at', date('Y-m-d'))->count(),
            'failed_today' => EmailNotification::failed()->whereDate('updated_at', date('Y-m-d'))->count(),
            'total_sent' => EmailNotification::sent()->count(),
            'total_failed' => EmailNotification::failed()->count(),
        ];
    }

    public function scheduleWeeklyDigests()
    {
        // Get active users who want weekly digests
        $users = User::where('created_at', '<=', now()->subDays(7))
            // ->whereHas('preferences', function($q) {
            //     $q->where('weekly_digest', true);
            // })
            ->get();

        $scheduled = 0;

        foreach ($users as $user) {
            // Check if user already has a digest for this week
            $weekStart = now()->startOfWeek();
            $existingDigest = EmailNotification::where('user_id', $user->id)
                ->where('type', 'weekly_digest')
                ->where('created_at', '>=', $weekStart)
                ->first();

            if ($existingDigest) continue;

            // Generate digest data
            $digestData = $this->generateUserDigestData($user);
            
            EmailNotification::createWeeklyDigestNotification($user, $digestData);
            $scheduled++;
        }

        return [
            'scheduled' => $scheduled,
            'message' => "Scheduled {$scheduled} weekly digest notifications"
        ];
    }

    private function generateUserDigestData($user)
    {
        $weekStart = now()->subWeek()->startOfWeek();
        $weekEnd = now()->subWeek()->endOfWeek();

        return [
            'user_features_created' => $user->featureRequests()->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            'user_votes_cast' => $user->votes()->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            'user_eggs_spent' => abs($user->eggTransactions()->where('amount', '<', 0)->whereBetween('created_at', [$weekStart, $weekEnd])->sum('amount')),
            'new_features_count' => \App\Models\FeatureRequest::whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            'active_voters' => \App\Models\FeatureVote::whereBetween('created_at', [$weekStart, $weekEnd])->distinct('user_id')->count(),
            'top_feature_title' => 'Sample Feature',
            'top_feature_eggs' => 500,
            'trending_features' => [
                ['title' => 'Dark Mode Support', 'eggs' => 350],
                ['title' => 'Mobile App', 'eggs' => 275],
                ['title' => 'API Documentation', 'eggs' => 200]
            ]
        ];
    }
}

function now() {
    return new \DateTime();
}