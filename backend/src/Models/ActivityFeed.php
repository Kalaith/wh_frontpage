declare(strict_types=1);

namespace App\Models;

/**
 * ActivityFeed Data Transfer Object
 * Previously an Eloquent model, now a simple data structure.
 */
final class ActivityFeed
{
    public int $id;
    public string $type;
    public string $action;
    public string $title;
    public ?string $description = null;
    public ?int $reference_id = null;
    public ?string $reference_type = null;
    public ?string $user = null;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->type = (string)($data['type'] ?? 'info');
            $this->action = (string)($data['action'] ?? '');
            $this->title = (string)($data['title'] ?? '');
            $this->description = $data['description'] ?? null;
            $this->reference_id = isset($data['reference_id']) ? (int)$data['reference_id'] : null;
            $this->reference_type = $data['reference_type'] ?? null;
            $this->user = $data['user'] ?? null;
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
            $this->updated_at = (string)($data['updated_at'] ?? date('Y-m-d H:i:s'));
        }
    }

    public function toArray(): array
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
?>