<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class FeatureRequest extends Model
{
    protected $table = 'feature_requests';
    
    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'description', 
        'category',
        'use_case',
        'expected_benefits',
        'priority_level',
        'feature_type',
        'status',
        'approval_notes',
        'approved_by',
        'approved_at',
        'total_eggs',
        'vote_count',
        'tags'
    ];

    protected $casts = [
        'tags' => 'array',
        'user_id' => 'integer',
        'project_id' => 'integer',
        'approved_by' => 'integer',
        'total_eggs' => 'integer',
        'vote_count' => 'integer',
        'approved_at' => 'datetime'
    ];

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function votes()
    {
        return $this->hasMany(FeatureVote::class, 'feature_id');
    }

    // Scopes for filtering
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeOrderByVotes(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('votes', $direction);
    }

    public function scopeOrderByDate(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('created_at', $direction);
    }

    // Get formatted data for API response
    public function toApiArray(): array
    {
        // Ensure tags is always an array
        $tags = $this->tags;
        if (!is_array($tags)) {
            if (is_string($tags)) {
                $decoded = json_decode($tags, true);
                $tags = is_array($decoded) ? $decoded : [];
            } else {
                $tags = [];
            }
        }

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'use_case' => $this->use_case,
            'expected_benefits' => $this->expected_benefits,
            'priority_level' => $this->priority_level,
            'feature_type' => $this->feature_type,
            'status' => $this->status,
            'approval_notes' => $this->approval_notes,
            'total_eggs' => $this->total_eggs,
            'vote_count' => $this->vote_count,
            'tags' => $tags,
            'project_id' => $this->project_id,
            'approved_at' => $this->approved_at ? $this->approved_at->format('M j, Y') : null,
            'created_at' => $this->created_at ? $this->created_at->format('M j, Y') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('M j, Y') : null
        ];

        // Include user info if loaded
        if ($this->relationLoaded('user') && $this->user) {
            $data['user'] = [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'display_name' => $this->user->display_name
            ];
        }

        // Include project info if loaded
        if ($this->relationLoaded('project') && $this->project) {
            $data['project'] = [
                'id' => $this->project->id,
                'title' => $this->project->title,
                'group_name' => $this->project->group_name ?? null
            ];
        }

        // Include approver info if loaded
        if ($this->relationLoaded('approvedBy') && $this->approvedBy) {
            $data['approved_by'] = [
                'id' => $this->approvedBy->id,
                'username' => $this->approvedBy->username,
                'display_name' => $this->approvedBy->display_name
            ];
        }

        return $data;
    }

    // Static methods for getting filtered results
    public static function getByFilters(array $filters = [], string $sortBy = 'votes', string $sortDirection = 'desc', int $limit = null): array
    {
        $query = self::query();

        // Apply filters
        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }
        
        if (!empty($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }
        
        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (!empty($filters['project_id'])) {
            $query->byProject((int)$filters['project_id']);
        }

        // Apply sorting
        switch ($sortBy) {
            case 'date':
                $query->orderByDate($sortDirection);
                break;
            case 'priority':
                $query->orderBy('priority', $sortDirection);
                break;
            default:
                $query->orderByVotes($sortDirection);
                break;
        }

        if ($limit) {
            $query->limit($limit);
        }

        // Load project relationship for better performance
        $query->with('project');

        return $query->get()->map(function ($request) {
            return $request->toApiArray();
        })->toArray();
    }

    // Get statistics
    public static function getStats(): array
    {
        return [
            'total' => self::count(),
            'open' => self::byStatus('Open')->count(),
            'in_progress' => self::byStatus('In Progress')->count(),
            'completed' => self::byStatus('Completed')->count(),
            'closed' => self::byStatus('Closed')->count()
        ];
    }
}
?>