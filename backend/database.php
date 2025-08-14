<?php
// database.php - Mock database for demo purposes
// In a real application, replace this with actual database queries

class MockDatabase {
    private static $users = [
        [
            'id' => 1,
            'email' => 'demo@example.com',
            'firstName' => 'Demo',
            'lastName' => 'User',
            'membershipType' => 'member',
            'created_at' => '2024-01-01 00:00:00'
        ],
        [
            'id' => 2,
            'email' => 'admin@example.com',
            'firstName' => 'Admin',
            'lastName' => 'User',
            'membershipType' => 'admin',
            'created_at' => '2024-01-01 00:00:00'
        ]
    ];
    
    public static function findUserByEmail($email) {
        foreach (self::$users as $user) {
            if (strtolower($user['email']) === strtolower($email)) {
                return $user;
            }
        }
        return null;
    }
    
    public static function createUser($userData) {
        $newUser = [
            'id' => count(self::$users) + 1,
            'email' => $userData['email'],
            'firstName' => $userData['firstName'] ?? '',
            'lastName' => $userData['lastName'] ?? '',
            'membershipType' => $userData['membershipType'] ?? 'member',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        self::$users[] = $newUser;
        return $newUser;
    }
    
    public static function getAllUsers() {
        return self::$users;
    }
}
