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
    public static function getGroupedProjects()
    {
        $projects = self::where('hidden', false)->get();
        
        $grouped = [];
        foreach ($projects as $project) {
            $groupName = $project->group_name;
            
            if (!isset($grouped[$groupName])) {
                $grouped[$groupName] = [
                    'name' => ucwords(str_replace('_', ' ', $groupName)),
                    'projects' => []
                ];
            }
            
            $projectData = [
                'title' => $project->title,
                'description' => $project->description,
                'stage' => $project->stage,
                'status' => $project->status,
                'version' => $project->version,
            ];
            
            if ($project->path) {
                $projectData['path'] = $project->path;
            }
            
            if ($project->repository_type && $project->repository_url) {
                $projectData['repository'] = [
                    'type' => $project->repository_type,
                    'url' => $project->repository_url
                ];
            }
            
            $grouped[$groupName]['projects'][] = $projectData;
        }
        
        return $grouped;
    }
}
