<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Actions\GetGroupedProjectsAction;
use App\Actions\GetProjectsByGroupAction;
use App\Actions\GetHomepageProjectsAction;
use Exception;

/**
 * Read-only projects catalog for the frontpage. The catalog is owned by
 * project_roost (the single source of truth); create/edit/delete happen there.
 */
class ProjectController
{
    public function __construct(
        private readonly GetGroupedProjectsAction $getGroupedProjectsAction,
        private readonly GetProjectsByGroupAction $getProjectsByGroupAction,
        private readonly GetHomepageProjectsAction $getHomepageProjectsAction
    ) {}

    /**
     * Get all projects grouped by category
     */
    public function getProjects(Request $request, Response $response): void
    {
        try {
            // Determine if the requesting user is an admin
            $userRole = $request->getAttribute('user_role', 'user');
            $isAdmin = strtolower((string)$userRole) === 'admin';

            $groupedProjects = $this->getGroupedProjectsAction->execute($isAdmin);

            // Create flat arrays for frontend compatibility
            $allProjects = [];
            $groupedArray = [];
            
            foreach ($groupedProjects as $groupName => $group) {
                // Add projects to flat array
                foreach ($group['projects'] as $project) {
                    $allProjects[] = $project;
                }
                
                // Create grouped array (just projects, not the group wrapper)
                $groupedArray[$groupName] = $group['projects'];
            }

            $data = [
                'version' => '2.0.0',
                'description' => 'WebHatchery Projects',
                'groups' => $groupedProjects,
                'projects' => $allProjects,
                'grouped' => $groupedArray
            ];

            $response->success($data);

        } catch (Exception $e) {
            $response->error('Failed to fetch projects: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get projects for homepage (only those with show_on_homepage = true)
     */
    public function getHomepageProjects(Request $request, Response $response): void
    {
        try {
            // Determine if the requesting user is an admin
            $userRole = $request->getAttribute('user_role', 'user');
            $isAdmin = strtolower((string)$userRole) === 'admin';

            $result = $this->getHomepageProjectsAction->execute($isAdmin);
            $groupedProjects = $result['groups'];
            $archived = $result['archived'];

            // Create flat arrays for frontend compatibility
            $allProjects = [];
            $groupedArray = [];

            foreach ($groupedProjects as $groupName => $group) {
                // Add projects to flat array
                foreach ($group['projects'] as $project) {
                    $allProjects[] = $project;
                }

                // Create grouped array (just projects, not the group wrapper)
                $groupedArray[$groupName] = $group['projects'];
            }

            $data = [
                'version' => '2.0.0',
                'description' => 'WebHatchery Homepage Projects',
                'groups' => $groupedProjects,
                'projects' => $allProjects,
                'grouped' => $groupedArray,
                'archived' => $archived
            ];

            $response->success($data);

        } catch (Exception $e) {
            $response->error('Failed to fetch homepage projects: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get projects by group
     */
    public function getProjectsByGroup(Request $request, Response $response): void
    {
        try {
            $groupName = $request->getParam('group');
            // If the group requested is 'private', ensure the user is admin
            if (strtolower((string)$groupName) === 'private') {
                $userRole = $request->getAttribute('user_role', 'user');
                $isAdmin = strtolower((string)$userRole) === 'admin';
                if (!$isAdmin) {
                    $response->error('Forbidden', 403);
                    return;
                }
            }

            $mapped = $this->getProjectsByGroupAction->execute((string)$groupName);

            $response->success($mapped);

        } catch (Exception $e) {
            $response->error('Failed to fetch projects for group: ' . $e->getMessage(), 500);
        }
    }
}
