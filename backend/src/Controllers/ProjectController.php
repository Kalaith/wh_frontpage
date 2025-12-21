<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Actions\GetGroupedProjectsAction;
use App\Actions\GetProjectsByGroupAction;
use App\Actions\CreateProjectAction;
use App\Actions\UpdateProjectAction;
use App\Actions\DeleteProjectAction;
use App\Actions\GetHomepageProjectsAction;
use Exception;

class ProjectController
{
    public function __construct(
        private readonly GetGroupedProjectsAction $getGroupedProjectsAction,
        private readonly GetProjectsByGroupAction $getProjectsByGroupAction,
        private readonly CreateProjectAction $createProjectAction,
        private readonly UpdateProjectAction $updateProjectAction,
        private readonly DeleteProjectAction $deleteProjectAction,
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

            $groupedProjects = $this->getHomepageProjectsAction->execute($isAdmin);

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
                'grouped' => $groupedArray
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
    
    /**
     * Create a new project
     */
    public function createProject(Request $request, Response $response): void
    {
        try {
            // Require admin access
            $userRole = $request->getAttribute('user_role', 'user');
            if (strtolower((string)$userRole) !== 'admin') {
                $response->error('Admin access required', 403);
                return;
            }

            $data = $request->getBody();
            $created = $this->createProjectAction->execute($data);

            $response->withStatus(201)->success($created, 'Project created successfully');

        } catch (Exception $e) {
            $response->error('Failed to create project: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update a project
     */
    public function updateProject(Request $request, Response $response): void
    {
        try {
            // Require admin access
            $userRole = $request->getAttribute('user_role', 'user');
            if (strtolower((string)$userRole) !== 'admin') {
                $response->error('Admin access required', 403);
                return;
            }

            $id = (int)$request->getParam('id', 0);
            $data = $request->getBody();
            $updated = $this->updateProjectAction->execute($id, $data);

            if ($updated === null) {
                $response->error('Project not found', 404);
                return;
            }

            $response->success($updated, 'Project updated');

        } catch (Exception $e) {
            $response->error('Failed to update project: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a project
     */
    public function deleteProject(Request $request, Response $response): void
    {
        try {
            // Require admin access
            $userRole = $request->getAttribute('user_role', 'user');
            if (strtolower((string)$userRole) !== 'admin') {
                $response->error('Admin access required', 403);
                return;
            }

            $id = (int)$request->getParam('id', 0);
            $ok = $this->deleteProjectAction->execute($id);

            if (!$ok) {
                $response->error('Project not found', 404);
                return;
            }

            $response->success(null, 'Project deleted');

        } catch (Exception $e) {
            $response->error('Failed to delete project: ' . $e->getMessage(), 500);
        }
    }
}
