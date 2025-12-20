declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ActivityFeed extends Model
{
    protected $table = 'activity_feed';
    
    protected $fillable = [
        'type',
        'action',
        'title',
        'description',
        'reference_id',
        'reference_type',
        'user'
    ];

    // Scopes for filtering
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    // Get formatted data for API response
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'action' => $this->action,
            'title' => $this->title,
            'description' => $this->description,
            'reference_id' => $this->reference_id,
            'reference_type' => $this->reference_type,
            'user' => $this->user,
            'created_at' => $this->created_at ? $this->created_at->format('M j, Y') : null,
            'created_at_relative' => $this->created_at ? $this->created_at->diffForHumans() : null
        ];
    }

    // Static method to get recent activity with optional project filtering
    public static function getRecentActivity(int $limit = 10, int $projectId = null): array
    {
        $query = self::recent($limit);
        
        if ($projectId) {
            // Filter activities related to the specific project
            $query->where(function($q) use ($projectId) {
                $q->where(function($subq) use ($projectId) {
                    // Feature requests for this project
                    $subq->where('reference_type', 'feature_request')
                         ->whereExists(function($exists) use ($projectId) {
                             $exists->select(\Illuminate\Database\Capsule\Manager::raw(1))
                                    ->from('feature_requests')
                                    ->whereColumn('feature_requests.id', 'activity_feed.reference_id')
                                    ->where('feature_requests.project_id', $projectId);
                         });
                })->orWhere(function($subq) use ($projectId) {
                    // Project suggestions for this project group (we'll need to implement this logic)
                    $subq->where('reference_type', 'project_suggestion');
                })->orWhere(function($subq) use ($projectId) {
                    // Direct project activities
                    $subq->where('reference_type', 'project')
                         ->where('reference_id', $projectId);
                });
            });
        }
        
        return $query->get()->map(function ($activity) {
            return $activity->toApiArray();
        })->toArray();
    }

    // Create activity entry
    public static function logActivity(string $type, string $action, string $title, string $description = null, int $referenceId = null, string $referenceType = null, string $user = null): self
    {
        return self::create([
            'type' => $type,
            'action' => $action,
            'title' => $title,
            'description' => $description,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'user' => $user
        ]);
    }
}
?>