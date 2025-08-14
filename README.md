# WebHatchery Frontpage

A modern full-stack web application that serves as the main landing page for WebHatchery projects. Features a React frontend with a PHP backend API for dynamic project management.

## 🌟 Features

- **Dynamic Project Showcase** - Database-driven project listings with categories
- **Modern React Frontend** - Built with TypeScript, Vite, and Tailwind CSS
- **RESTful PHP API** - Slim Framework backend with database integration
- **Environment-Aware Deployment** - Separate configurations for preview and production
- **Responsive Design** - Mobile-first approach with modern UI components

## 🏗️ Architecture

```
frontpage/
├── frontend/          # React TypeScript application
│   ├── src/
│   │   ├── api/       # API client and types
│   │   ├── components/# Reusable UI components
│   │   ├── pages/     # Page components
│   │   ├── services/  # Business logic
│   │   └── types/     # TypeScript definitions
│   └── public/        # Static assets
├── backend/           # PHP API server
│   ├── src/
│   │   ├── Controllers/# API controllers
│   │   ├── Models/    # Database models
│   │   ├── Routes/    # API routes
│   │   └── Middleware/# Request middleware
│   ├── scripts/       # Database migration scripts
│   └── public/        # Web server entry point
└── publish.ps1       # Deployment script
```

## 🚀 Quick Start

### Prerequisites

- **Node.js** (v18 or higher)
- **PHP** (v8.1 or higher)
- **Composer** (PHP package manager)
- **MySQL** (for database)

### Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/Kalaith/wh_frontpage.git
   cd wh_frontpage
   ```

2. **Setup Frontend**
   ```bash
   cd frontend
   npm install
   npm run dev
   ```
   Frontend will be available at `http://localhost:5173`

3. **Setup Backend**
   ```bash
   cd backend
   composer install
   
   # Configure environment
   cp .env.example .env
   # Edit .env with your database credentials
   
   # Create database table
   php scripts/create_projects_table.php
   
   # Import existing projects (optional)
   php scripts/import_projects.php
   
   # Start development server
   php -S localhost:8000 -t public
   ```
   Backend API will be available at `http://localhost:8000`

### Environment Configuration

**Frontend (.env files):**
- `.env.local` - Local development
- `.env.preview` - Preview environment
- `.env.production` - Production environment

**Backend (.env files):**
- `.env` - Development
- `.env.preview` - Preview environment
- `.env.production` - Production environment

Key environment variables:
```bash
# Frontend
VITE_BASE_PATH="/"
VITE_API_BASE_URL="http://localhost:8000/api"

# Backend
DB_HOST=localhost
DB_DATABASE=frontpage
DB_USERNAME=root
DB_PASSWORD=your_password
APP_BASE_PATH=/frontpage/backend
```

## 📦 Deployment

Use the PowerShell deployment script:

```powershell
# Deploy to preview environment
.\publish.ps1 -Environment preview

# Deploy to production
.\publish.ps1 -Environment production

# Clean deploy (removes existing files)
.\publish.ps1 -Environment production -Clean

# Deploy only frontend or backend
.\publish.ps1 -Frontend -Environment preview
.\publish.ps1 -Backend -Environment production
```

### Deployment Structure

```
Production:
webhatchery.au/
├── index.html         # Frontend (served from root)
├── assets/            # Frontend assets
└── frontpage/
    └── backend/       # API endpoints
        └── api/       # /frontpage/backend/api/*

Preview:
localhost/frontpage/
├── index.html         # Frontend
├── assets/            # Frontend assets
└── backend/           # API endpoints
    └── api/           # /frontpage/backend/api/*
```

## 🛠️ API Endpoints

### Health & Info
- `GET /` - API information and available endpoints
- `GET /api/health` - Detailed health check with system info

### Projects
- `GET /api/projects` - Get all projects grouped by category
- `GET /api/projects/{group}` - Get projects for specific group
- `POST /api/projects` - Create new project

### Example API Response
```json
{
  "success": true,
  "data": {
    "version": "2.0.0",
    "description": "WebHatchery Projects",
    "groups": {
      "apps": {
        "name": "Web Applications",
        "projects": [
          {
            "title": "Project Name",
            "description": "Project description",
            "stage": "MVP",
            "status": "fully-working",
            "version": "1.0.0",
            "path": "apps/project/"
          }
        ]
      }
    }
  }
}
```

## 🗄️ Database Schema

### Projects Table
```sql
CREATE TABLE projects (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    path VARCHAR(255) NULL,
    description TEXT NULL,
    stage VARCHAR(255) DEFAULT 'prototype',
    status VARCHAR(255) DEFAULT 'prototype',
    version VARCHAR(255) DEFAULT '0.1.0',
    group_name VARCHAR(255) DEFAULT 'other',
    repository_type VARCHAR(255) NULL,
    repository_url VARCHAR(255) NULL,
    hidden BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 🔧 Development

### Frontend Development
```bash
cd frontend
npm run dev      # Start development server
npm run build    # Build for production
npm run preview  # Preview production build
npm run lint     # Run ESLint
```

### Backend Development
```bash
cd backend
php -S localhost:8000 -t public  # Start development server
composer install                  # Install dependencies
composer update                   # Update dependencies
```

### Adding New Projects
```bash
# Via API
curl -X POST http://localhost:8000/api/projects \
  -H "Content-Type: application/json" \
  -d '{
    "title": "New Project",
    "description": "Project description",
    "stage": "MVP",
    "status": "working",
    "version": "1.0.0",
    "group_name": "apps"
  }'
```

## 📁 Project Structure Details

### Frontend Components
- `Header.tsx` - Main navigation and branding
- `ProjectShowcase.tsx` - Main project grid display
- `ProjectCard.tsx` - Individual project cards
- `ProjectLegend.tsx` - Status and stage explanations
- `Footer.tsx` - Site footer with links

### Backend Structure
- `Models/Project.php` - Eloquent model for projects
- `Controllers/ProjectController.php` - API logic
- `Routes/api.php` - Route definitions
- `Middleware/CorsMiddleware.php` - CORS handling

## 🚨 Troubleshooting

### Common Issues

**500 Internal Server Error**
- Check PHP error logs
- Verify database connection in `.env`
- Ensure all dependencies are installed

**API Not Found (404)**
- Verify backend server is running
- Check API base URL in frontend `.env`
- Confirm route definitions in `api.php`

**Frontend Build Errors**
- Clear node_modules: `rm -rf node_modules && npm install`
- Check TypeScript errors: `npm run type-check`
- Verify environment variables

### Database Migration

If you need to migrate from the old JSON format:
```bash
cd backend
php scripts/create_projects_table.php
php scripts/import_projects.php
```

## 📝 License

This project is part of the WebHatchery ecosystem.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For support or questions about this project, please create an issue in the GitHub repository.
