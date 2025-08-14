<?php

namespace App\Constants;

class AppConstants
{
    // Application constants
    public const APP_NAME = 'Frontpage API';
    public const APP_VERSION = '1.0.0';
    
    // Project stages
    public const PROJECT_STAGE_PROTOTYPE = 'prototype';
    public const PROJECT_STAGE_DEVELOPMENT = 'development';
    public const PROJECT_STAGE_PRODUCTION = 'production';
    public const PROJECT_STAGE_MAINTENANCE = 'maintenance';
    
    // Project statuses
    public const PROJECT_STATUS_ACTIVE = 'active';
    public const PROJECT_STATUS_INACTIVE = 'inactive';
    public const PROJECT_STATUS_ARCHIVED = 'archived';
    
    // Default groups
    public const DEFAULT_PROJECT_GROUP = 'other';
    
    // Repository types
    public const REPO_TYPE_GIT = 'git';
    public const REPO_TYPE_SVN = 'svn';
    public const REPO_TYPE_MERCURIAL = 'mercurial';
}
