declare(strict_types=1);

namespace App\Models;

/**
 * EmailNotification Data Transfer Object
 * Previously an Eloquent model, now a simple data structure.
 */
final class EmailNotification
{
    public int $id;
    public int $user_id;
    public string $type;
    public string $subject;
    public string $message;
    public array $metadata = [];
    public string $status = 'pending';
    public ?string $sent_at = null;
    public ?string $error_message = null;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->user_id = (int)($data['user_id'] ?? 0);
            $this->type = (string)($data['type'] ?? 'general');
            $this->subject = (string)($data['subject'] ?? '');
            $this->message = (string)($data['message'] ?? '');
            $this->status = (string)($data['status'] ?? 'pending');
            $this->sent_at = $data['sent_at'] ?? null;
            $this->error_message = $data['error_message'] ?? null;
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
            $this->updated_at = (string)($data['updated_at'] ?? date('Y-m-d H:i:s'));

            if (isset($data['metadata'])) {
                $this->metadata = is_string($data['metadata']) 
                    ? json_decode($data['metadata'], true) 
                    : (array)$data['metadata'];
            }
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'subject' => $this->subject,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'status' => $this->status,
            'sent_at' => $this->sent_at,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Helper methods for message generation (Moved from Eloquent model)
     */
    public static function generateFeatureApprovedMessage(array $featureData): string
    {
        $title = $featureData['title'] ?? 'Feature';
        $notes = $featureData['approval_notes'] ?? '';
        
        $message = "Great news! Your feature request '{$title}' has been approved and is now available for community voting.\n\n";
        if ($notes) {
            $message .= "Admin Notes: {$notes}\n\n";
        }
        $message .= "What happens next:\n" .
                    "• Community members can now vote on your feature with their eggs\n" .
                    "• Features with more eggs get higher priority for development\n" .
                    "• You'll be notified of any status updates\n\n" .
                    "Thank you for contributing to WebHatchery!\n\n" .
                    "View your feature: [Feature Link Here]";
        return $message;
    }

    public static function generateFeatureRejectedMessage(array $featureData): string
    {
        $title = $featureData['title'] ?? 'Feature';
        $notes = $featureData['approval_notes'] ?? '';
        
        $message = "We've reviewed your feature request '{$title}' and unfortunately it doesn't meet our current criteria for approval.\n\n";
        if ($notes) {
            $message .= "Reason: {$notes}\n\n";
        }
        $message .= "Don't be discouraged! You can:\n" .
                    "• Revise and resubmit your request\n" .
                    "• Check our feature request guidelines\n" .
                    "• Vote on other community features\n\n" .
                    "Note: The 100 eggs spent on this request are not refunded, as they help fund the review process.\n\n" .
                    "Thank you for your participation!";
        return $message;
    }

    public static function generateDailyReminderMessage(string $displayName, int $balance): string
    {
        return "Hello {$displayName},\n\n" .
               "Don't forget to claim your daily 100 eggs! 🥚\n\n" .
               "Current balance: {$balance} eggs\n\n" .
               "Use your eggs to:\n" .
               "• Create new feature requests (100 eggs)\n" .
               "• Vote on community features\n" .
               "• Support your favorite projects\n\n" .
               "Claim your eggs now: [Dashboard Link]";
    }
}
