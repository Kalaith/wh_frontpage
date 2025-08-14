<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';
    
    protected $fillable = [
        'title',
        'path',
        'description',
        'stage',
        'status',
        'version',
        'group_name',
        'repository_type',
        'repository_url',
        'hidden'
    ];
    
    protected $casts = [
        'hidden' => 'boolean',
    ];
    
    public $timestamps = true;
    
    /**
     * Get projects grouped by group_name
     */
    public static function getGroupedProjects(bool $includePrivate = false)
    {
        $projects = self::where('hidden', false)->get();
        
        $grouped = [];
        foreach ($projects as $project) {
            $groupName = $project->group_name;

            // Skip private group when caller did not request private projects
            if (!$includePrivate && strtolower($groupName) === 'private') {
                continue;
            }
            
            if (!isset($grouped[$groupName])) {
                $grouped[$groupName] = [
                    'name' => ucwords(str_replace('_', ' ', $groupName)),
                    'projects' => []
                ];
            }
            
            $projectData = [
                'id' => $project->id,
                'group_name' => $groupName,
                'title' => $project->title,
                'description' => $project->description,
                'stage' => $project->stage,
                'status' => $project->status,
                'version' => $project->version,
            ];
            
            if ($project->path) {
                $projectData['path'] = $project->path;
            }
            
            if ($project->repository_url) {
                $repo = [
                    'url' => $project->repository_url
                ];
                if ($project->repository_type) {
                    $repo['type'] = $project->repository_type;
                }
                $projectData['repository'] = $repo;
            }
            
            $grouped[$groupName]['projects'][] = $projectData;
        }
        
        return $grouped;
    }

    /**
     * Create the projects table for initialization scripts
     */
    public static function createTable(): void
    {
        $schema = \Illuminate\Database\Capsule\Manager::schema();

        if (!$schema->hasTable('projects')) {
            $schema->create('projects', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('path')->nullable();
                $table->text('description')->nullable();
                $table->string('stage')->default('prototype');
                $table->string('status')->default('prototype');
                $table->string('version')->default('0.1.0');
                $table->string('group_name')->default('other');
                $table->string('repository_type')->nullable();
                $table->text('repository_url')->nullable();
                $table->boolean('hidden')->default(false);
                $table->timestamps();

                // Indexes
                $table->index('group_name');
                $table->index('hidden');
            });

            echo "✅ Created 'projects' table\n";
        } else {
            echo "ℹ️  'projects' table already exists\n";
        }
    }
}
