declare(strict_types=1);

namespace App\Models;

/**
 * FeatureVote Data Transfer Object
 * Previously an Eloquent model, now a simple data structure.
 */
final class FeatureVote
{
    public int $id;
    public int $user_id;
    public int $feature_id;
    public int $eggs_allocated;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->user_id = (int)($data['user_id'] ?? 0);
            $this->feature_id = (int)($data['feature_id'] ?? 0);
            $this->eggs_allocated = (int)($data['eggs_allocated'] ?? 0);
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
            $this->updated_at = (string)($data['updated_at'] ?? date('Y-m-d H:i:s'));
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'feature_id' => $this->feature_id,
            'eggs_allocated' => $this->eggs_allocated,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
