<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Project;

class ProjectController
{
    /**
     * Get all projects grouped by category
     */
    public function getProjects(Request $request, Response $response): Response
    {
        try {
            $groupedProjects = Project::getGroupedProjects();
            
            $data = [
                'version' => '2.0.0',
                'description' => 'WebHatchery Projects and Build Configuration',
                'groups' => $groupedProjects
            ];
            
            $payload = json_encode([
                'success' => true,
                'data' => $data
            ]);
            
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'error' => [
                    'message' => 'Failed to fetch projects',
                    'details' => $e->getMessage()
                ]
            ]);
            
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
    
    /**
     * Get projects by group
     */
    public function getProjectsByGroup(Request $request, Response $response, array $args): Response
    {
        try {
            $groupName = $args['group'];
            
            $projects = Project::where('group_name', $groupName)
                ->where('hidden', false)
                ->get();
            
            $payload = json_encode([
                'success' => true,
                'data' => $projects
            ]);
            
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'error' => [
                    'message' => 'Failed to fetch projects for group',
                    'details' => $e->getMessage()
                ]
            ]);
            
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
    
    /**
     * Create a new project
     */
    public function createProject(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            $project = Project::create([
                'title' => $data['title'],
                'path' => $data['path'] ?? null,
                'description' => $data['description'] ?? '',
                'stage' => $data['stage'] ?? 'prototype',
                'status' => $data['status'] ?? 'prototype',
                'version' => $data['version'] ?? '0.1.0',
                'group_name' => $data['group_name'] ?? 'other',
                'repository_type' => $data['repository']['type'] ?? null,
                'repository_url' => $data['repository']['url'] ?? null,
                'hidden' => $data['hidden'] ?? false
            ]);
            
            $payload = json_encode([
                'success' => true,
                'data' => $project,
                'message' => 'Project created successfully'
            ]);
            
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
            
        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'error' => [
                    'message' => 'Failed to create project',
                    'details' => $e->getMessage()
                ]
            ]);
            
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
