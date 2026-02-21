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

    public function findOrCreateByGitHubUsername(string $username): ?Adventurer
    {
        $existing = $this->findByGitHubUsername($username);
        if ($existing) {
            return $existing;
        }

        // Fallback: create an adventurer profile for an existing app user.
        $userStmt = $this->db->prepare("
            SELECT id, username
            FROM users
            WHERE username = :username_exact OR display_name = :display_name_exact
            LIMIT 1
        ");
        $userStmt->execute([
            'username_exact' => $username,
            'display_name_exact' => $username,
        ]);
        $user = $userStmt->fetch();

        if (!$user) {
            return null;
        }

        $userId = (int)$user['id'];
        $byUser = $this->findByUserId($userId);
        if ($byUser) {
            return $byUser;
        }

        $insertStmt = $this->db->prepare("
            INSERT INTO adventurers (user_id, github_username, class, xp_total, level, glow_streak, created_at, updated_at)
            VALUES (:user_id, :github_username, 'hatchling', 0, 1, 0, NOW(), NOW())
        ");
        $insertStmt->execute([
            'user_id' => $userId,
            'github_username' => (string)$user['username'],
        ]);

        return $this->findByUserId($userId);
    }

    public function findById(int $id): ?Adventurer
    {
        $stmt = $this->db->prepare("SELECT * FROM adventurers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        
        return $row ? new Adventurer($row) : null;
    }

    public function findByUserId(int $userId): ?Adventurer
    {
        $stmt = $this->db->prepare("SELECT * FROM adventurers WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
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

    public function getRank(int $adventurerId): ?string
    {
        $stmt = $this->db->prepare("SELECT `rank` FROM adventurers WHERE id = :id");
        $stmt->execute(['id' => $adventurerId]);
        $rank = $stmt->fetchColumn();
        return $rank !== false ? (string)$rank : null;
    }

    public function getXp(int $adventurerId): int
    {
        $stmt = $this->db->prepare("SELECT xp_total FROM adventurers WHERE id = :id");
        $stmt->execute(['id' => $adventurerId]);
        $xp = $stmt->fetchColumn();
        return $xp !== false ? (int)$xp : 0;
    }

    public function updateRank(int $adventurerId, string $newRank): bool
    {
        $stmt = $this->db->prepare("UPDATE adventurers SET `rank` = :rank, updated_at = NOW() WHERE id = :id");
        return $stmt->execute([
            'rank' => $newRank,
            'id' => $adventurerId
        ]);
    }
}
