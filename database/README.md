# Database Setup Instructions

This directory contains the database initialization files for the WebHatchery Frontpage application.

## Files

- `frontpage-init.sql` - Complete SQL script to create and populate the projects table
- `init-database.bat` - Windows batch script to help run the SQL file
- `README.md` - This documentation file

## Setup Instructions

### Option 1: Using MySQL Command Line (Recommended)

1. Connect to your MySQL server:
   ```bash
   mysql -u username -p database_name
   ```

2. Run the initialization script:
   ```bash
   mysql -u username -p database_name < frontpage-init.sql
   ```

### Option 2: Using phpMyAdmin or MySQL Workbench

1. Open your MySQL administration tool
2. Select your database
3. Import the `frontpage-init.sql` file
4. Execute the script

### Option 3: Using the Web Interface (if available)

If you have the web-based database initialization interface set up:
1. Navigate to `/init-db.html` on your server
2. Click the "Initialize Database" button

## What Gets Created

- **projects** table with the following structure:
  - `id` - Primary key (auto-increment)
  - `title` - Project title
  - `path` - URL path (can be null)
  - `description` - Project description
  - `stage` - Development stage (Static, Fullstack, React, etc.)
  - `status` - Current status (fully-working, MVP, prototype, etc.)
  - `version` - Version number
  - `group_name` - Category (fiction, apps, games, etc.)
  - `repository_type` - Git repository type (if applicable)
  - `repository_url` - Git repository URL (if applicable)
  - `hidden` - Whether the project is hidden from public view
  - `created_at` - Creation timestamp
  - `updated_at` - Last update timestamp

## Sample Data

The script includes sample data for:
- **Fiction Projects**: Stories, Anime Hub, AI Portrait Gallery
- **Web Applications**: Various tools and apps like Meme Generator, Name Generator, Story Forge
- **Games**: Multiple game prototypes including Dungeon Core, Xenomorph Park, Kingdom Wars
- **Game Design Documents**: Planning documents for various game concepts
- **Private Projects**: Hidden projects (not visible on the frontpage)

## Verification

After running the script, you can verify the installation by:

1. Checking the table was created:
   ```sql
   DESCRIBE projects;
   ```

2. Counting projects by category:
   ```sql
   SELECT group_name, COUNT(*) as count FROM projects GROUP BY group_name ORDER BY group_name;
   ```

3. Viewing all projects:
   ```sql
   SELECT id, title, group_name, stage, status, version FROM projects ORDER BY group_name, title;
   ```

## Security Notes

- The script drops and recreates the `projects` table
- Make sure to backup any existing data before running
- The script includes both visible and hidden projects
- Hidden projects have `hidden = 1` and won't appear on the public frontpage

## Troubleshooting

- **Permission denied**: Make sure your MySQL user has CREATE and INSERT privileges
- **Table already exists**: The script includes `DROP TABLE IF EXISTS` to handle this
- **Character encoding issues**: The table uses UTF-8 encoding for international characters
- **Connection issues**: Verify your MySQL server is running and credentials are correct
