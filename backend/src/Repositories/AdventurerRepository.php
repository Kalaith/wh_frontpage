<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Adventurer;
use PDO;

class AdventurerRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getLeaderboard(int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM adventurers 
                ORDER BY xp_total DESC, level DESC, created_at ASC 
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = [];
            while ($row = $stmt->fetch()) {
                $results[] = (new Adventurer($row))->toArray();
            }
            return $results;
        } catch (\Exception $e) {
            // Table might not exist yet if migration hasn't run
            return [];
        }
    }
    
    public function findByGitHubUsername(string $username): ?Adventurer
    {
        $stmt = $this->db->prepare("SELECT * FROM adventurers WHERE github_username = :username");
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        
        return $row ? new Adventurer($row) : null;
    }

    public function findById(int $id): ?Adventurer
    {
        $stmt = $this->db->prepare("SELECT * FROM adventurers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        
        return $row ? new Adventurer($row) : null;
    }

    public function getBadges(int $adventurerId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM adventurer_badges WHERE adventurer_id = :id");
            $stmt->execute(['id' => $adventurerId]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function getMastery(int $adventurerId): array
    {
        try {
            // Join with projects to get name/slug
            $stmt = $this->db->prepare("
                SELECT hm.*, p.title as project_title, p.path as project_path 
                FROM habitat_mastery hm
                JOIN projects p ON hm.project_id = p.id
                WHERE hm.adventurer_id = :id
            ");
            $stmt->execute(['id' => $adventurerId]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }
}
