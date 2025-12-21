declare(strict_types=1);

namespace App\Models;

/**
 * FeatureRequest Data Transfer Object
 * Previously an Eloquent model, now a simple data structure.
 */
final class FeatureRequest
{
    public int $id;
    public int $user_id;
    public ?int $project_id = null;
    public string $title;
    public string $description;
    public ?string $category = null;
    public ?string $use_case = null;
    public ?string $expected_benefits = null;
    public string $priority_level = 'medium';
    public string $feature_type = 'feature';
    public string $status = 'Open';
    public ?string $approval_notes = null;
    public ?int $approved_by = null;
    public ?string $approved_at = null;
    public int $total_eggs = 0;
    public int $vote_count = 0;
    public array $tags = [];
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->user_id = (int)($data['user_id'] ?? 0);
            $this->project_id = isset($data['project_id']) ? (int)$data['project_id'] : null;
            $this->title = (string)($data['title'] ?? '');
            $this->description = (string)($data['description'] ?? '');
            $this->category = $data['category'] ?? null;
            $this->use_case = $data['use_case'] ?? null;
            $this->expected_benefits = $data['expected_benefits'] ?? null;
            $this->priority_level = (string)($data['priority_level'] ?? 'medium');
            $this->feature_type = (string)($data['feature_type'] ?? 'feature');
            $this->status = (string)($data['status'] ?? 'Open');
            $this->approval_notes = $data['approval_notes'] ?? null;
            $this->approved_by = isset($data['approved_by']) ? (int)$data['approved_by'] : null;
            $this->approved_at = $data['approved_at'] ?? null;
            $this->total_eggs = (int)($data['total_eggs'] ?? 0);
            $this->vote_count = (int)($data['vote_count'] ?? 0);
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
            $this->updated_at = (string)($data['updated_at'] ?? date('Y-m-d H:i:s'));

            if (isset($data['tags'])) {
                $this->tags = is_string($data['tags']) 
                    ? json_decode($data['tags'], true) 
                    : (array)$data['tags'];
            }
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'use_case' => $this->use_case,
            'expected_benefits' => $this->expected_benefits,
            'priority_level' => $this->priority_level,
            'feature_type' => $this->feature_type,
            'status' => $this->status,
            'approval_notes' => $this->approval_notes,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'total_eggs' => $this->total_eggs,
            'vote_count' => $this->vote_count,
            'tags' => $this->tags,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
?>