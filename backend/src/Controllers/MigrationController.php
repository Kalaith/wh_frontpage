<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\MigrationRepository;

final class MigrationController
{
    public function __construct(
        private readonly MigrationRepository $migrationRepository
    ) {}

    /**
     * Run pending migrations
     * IP restricted via ALLOWED_ADMIN_IP
     */
    public function runSync(Request $request, Response $response): void
    {
        // IP restriction for security
        $allowedIp = $_ENV['ALLOWED_ADMIN_IP'] ?? throw new \RuntimeException('ALLOWED_ADMIN_IP environment variable is not set');
        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $clientIp = explode(',', $clientIp)[0];
        
        if ($allowedIp && trim($clientIp) !== trim($allowedIp)) {
            $response->withStatus(403)->json(['error' => 'Access denied']);
            return;
        }

        try {
            $result = $this->migrationRepository->runPendingMigrations();
            
            $message = empty($result['applied']) 
                ? 'All migrations already applied' 
                : 'Migrations applied successfully';
                
            $response->success($result, $message);

        } catch (\Exception $e) {
            $response->error('Sync failed: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Run migrations publicly (Temporary)
     */
    public function runPublicMigration(Request $request, Response $response): void
    {
        try {
            $result = $this->migrationRepository->runPendingMigrations();
            
            $message = empty($result['applied']) 
                ? 'All migrations already applied' 
                : 'Migrations applied successfully: ' . implode(', ', $result['applied']);
                
            $response->success($result, $message);

        } catch (\Exception $e) {
            $response->error('Migration failed: ' . $e->getMessage(), 500);
        }
    }
}
