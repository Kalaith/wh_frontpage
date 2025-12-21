declare(strict_types=1);

namespace App\Models;

/**
 * EggTransaction Data Transfer Object
 * Previously an Eloquent model, now a simple data structure.
 */
final class EggTransaction
{
    public int $id;
    public int $user_id;
    public int $amount;
    public string $transaction_type;
    public string $description;
    public ?int $reference_id = null;
    public ?string $reference_type = null;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->user_id = (int)($data['user_id'] ?? 0);
            $this->amount = (int)($data['amount'] ?? 0);
            $this->transaction_type = (string)($data['transaction_type'] ?? 'earn');
            $this->description = (string)($data['description'] ?? '');
            $this->reference_id = isset($data['reference_id']) ? (int)$data['reference_id'] : null;
            $this->reference_type = $data['reference_type'] ?? null;
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
            $this->updated_at = (string)($data['updated_at'] ?? date('Y-m-d H:i:s'));
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'amount' => $this->amount,
            'transaction_type' => $this->transaction_type,
            'description' => $this->description,
            'reference_id' => $this->reference_id,
            'reference_type' => $this->reference_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
