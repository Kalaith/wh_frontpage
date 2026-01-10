<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Models\ProjectSuggestionComment;

final class ProjectSuggestionCommentRepository
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function getBySuggestionId(int $suggestionId): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM project_suggestion_comments 
            WHERE project_suggestion_id = :id 
            ORDER BY created_at ASC
        ');
        $stmt->execute(['id' => $suggestionId]);
        
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[] = new ProjectSuggestionComment($row);
        }
        
        return $results;
    }

    public function create(array $data): ProjectSuggestionComment
    {
        $stmt = $this->db->prepare('
            INSERT INTO project_suggestion_comments (project_suggestion_id, user_id, user_name, content) 
            VALUES (:project_suggestion_id, :user_id, :user_name, :content)
        ');
        
        $stmt->execute([
            'project_suggestion_id' => $data['project_suggestion_id'],
            'user_id' => $data['user_id'] ?? null,
            'user_name' => $data['user_name'] ?? 'Anonymous',
            'content' => $data['content']
        ]);

        $id = (int)$this->db->lastInsertId();
        $data['id'] = $id;
        
        return new ProjectSuggestionComment($data);
        return new ProjectSuggestionComment($data);
    }

    public function deleteBySuggestionId(int $suggestionId): void
    {
        $stmt = $this->db->prepare('DELETE FROM project_suggestion_comments WHERE project_suggestion_id = :id');
        $stmt->execute(['id' => $suggestionId]);
    }
}
