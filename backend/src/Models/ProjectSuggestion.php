declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProjectSuggestion extends Model
{
    protected $table = 'project_suggestions';
    
    protected $fillable = [
        'name',
        'description', 
        'suggested_group',
        'rationale',
        'votes',
        'status',
        'submitted_by'
    ];

    protected $casts = [
        'votes' => 'integer'
    ];

    // Scopes for filtering
    public function scopeByGroup(Builder $query, string $group): Builder
    {
        return $query->where('suggested_group', $group);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeOrderByVotes(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('votes', $direction);
    }

    public function scopeOrderByDate(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('created_at', $direction);
    }

    public function scopeOrderByName(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('name', $direction);
    }

    // Get formatted data for API response
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'suggested_group' => $this->suggested_group,
            'rationale' => $this->rationale,
            'votes' => $this->votes,
            'status' => $this->status,
            'submitted_by' => $this->submitted_by,
            'created_at' => $this->created_at ? $this->created_at->format('M j, Y') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('M j, Y') : null
        ];
    }

    // Static methods for getting filtered results
    public static function getByFilters(array $filters = [], string $sortBy = 'votes', string $sortDirection = 'desc', int $limit = null): array
    {
        $query = self::query();

        // Apply filters
        if (!empty($filters['group'])) {
            $query->byGroup($filters['group']);
        }
        
        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        // Apply sorting
        switch ($sortBy) {
            case 'date':
                $query->orderByDate($sortDirection);
                break;
            case 'name':
                $query->orderByName($sortDirection);
                break;
            default:
                $query->orderByVotes($sortDirection);
                break;
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->map(function ($suggestion) {
            return $suggestion->toApiArray();
        })->toArray();
    }

    // Get statistics
    public static function getStats(): array
    {
        return [
            'total' => self::count(),
            'suggested' => self::byStatus('Suggested')->count(),
            'under_review' => self::byStatus('Under Review')->count(),
            'approved' => self::byStatus('Approved')->count(),
            'rejected' => self::byStatus('Rejected')->count()
        ];
    }

    // Get suggestions grouped by category
    public static function getGroupedSuggestions(): array
    {
        $suggestions = self::orderByVotes()->get();
        $grouped = [];

        foreach ($suggestions as $suggestion) {
            $group = $suggestion->suggested_group;
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $suggestion->toApiArray();
        }

        return $grouped;
    }
}
?>