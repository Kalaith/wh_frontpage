<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Actions\GetGroupedProjectsAction;
use App\Actions\GetProjectsByGroupAction;
use App\Actions\CreateProjectAction;
use App\Actions\UpdateProjectAction;
use App\Actions\DeleteProjectAction;

class ProjectController
{
    /**
     * Get all projects grouped by category
     */
    public function getProjects(Request $request, Response $response): Response
    {
        try {
            $action = new GetGroupedProjectsAction();

            // Determine if the requesting user is an admin
            $userRole = $request->getAttribute('user_role', 'user');
            $isAdmin = strtolower((string)$userRole) === 'admin';

            $groupedProjects = $action->execute($isAdmin);

            $data = [
                'version' => '2.0.0',
                'description' => 'WebHatchery Projects and Build Configuration',
                'groups' => $groupedProjects
            ];

            $payload = json_encode(['success' => true, 'data' => $data]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode(['success' => false, 'error' => ['message' => 'Failed to fetch projects', 'details' => $e->getMessage()]]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * Get projects by group
     */
    public function getProjectsByGroup(Request $request, Response $response, array $args): Response
    {
        try {
            $groupName = $args['group'];
            // If the group requested is 'private', ensure the user is admin
            if (strtolower($groupName) === 'private') {
                $userRole = $request->getAttribute('user_role', 'user');
                $isAdmin = strtolower((string)$userRole) === 'admin';
                if (!$isAdmin) {
                    $payload = json_encode(['success' => false, 'error' => ['message' => 'Forbidden']]);
                    $response->getBody()->write($payload);
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
                }
            }

            $action = new GetProjectsByGroupAction();
            $mapped = $action->execute($groupName);

            $payload = json_encode(['success' => true, 'data' => $mapped]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode(['success' => false, 'error' => ['message' => 'Failed to fetch projects for group', 'details' => $e->getMessage()]]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * Create a new project
     */
    public function createProject(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $action = new CreateProjectAction();
            $created = $action->execute($data);

            $payload = json_encode(['success' => true, 'data' => $created, 'message' => 'Project created successfully']);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $payload = json_encode(['success' => false, 'error' => ['message' => 'Failed to create project', 'details' => $e->getMessage()]]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Update a project
     */
    public function updateProject(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)($args['id'] ?? 0);
            $data = $request->getParsedBody();
            $action = new UpdateProjectAction();
            $updated = $action->execute($id, $data);

            if ($updated === null) {
                $payload = json_encode(['success' => false, 'error' => ['message' => 'Project not found']]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $payload = json_encode(['success' => true, 'data' => $updated, 'message' => 'Project updated']);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode(['success' => false, 'error' => ['message' => 'Failed to update project', 'details' => $e->getMessage()]]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Delete a project
     */
    public function deleteProject(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)($args['id'] ?? 0);
            $action = new DeleteProjectAction();
            $ok = $action->execute($id);

            if (!$ok) {
                $payload = json_encode(['success' => false, 'error' => ['message' => 'Project not found']]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $payload = json_encode(['success' => true, 'message' => 'Project deleted']);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode(['success' => false, 'error' => ['message' => 'Failed to delete project', 'details' => $e->getMessage()]]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
