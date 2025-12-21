declare(strict_types=1);

namespace App\Models;

/**
 * ProjectSuggestion Data Transfer Object
 * Previously an Eloquent model, now a simple data structure.
 */
final class ProjectSuggestion
{
    public int $id;
    public string $name;
    public string $description;
    public string $suggested_group = 'other';
    public ?string $rationale = null;
    public int $votes = 0;
    public string $status = 'Suggested';
    public ?string $submitted_by = null;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->name = (string)($data['name'] ?? '');
            $this->description = (string)($data['description'] ?? '');
            $this->suggested_group = (string)($data['suggested_group'] ?? 'other');
            $this->rationale = $data['rationale'] ?? null;
            $this->votes = (int)($data['votes'] ?? 0);
            $this->status = (string)($data['status'] ?? 'Suggested');
            $this->submitted_by = $data['submitted_by'] ?? null;
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
            $this->updated_at = (string)($data['updated_at'] ?? date('Y-m-d H:i:s'));
        }
    }

    public function toArray(): array
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
?>