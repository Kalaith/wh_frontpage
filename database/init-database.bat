@echo off
REM WebHatchery Frontpage Database Initialization Script
REM This script helps initialize the database for the frontpage application

echo =====================================================
echo WebHatchery Frontpage Database Setup
echo =====================================================
echo.

REM Check if MySQL is available
mysql --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: MySQL client not found in PATH
    echo Please install MySQL client or add it to your PATH
    echo.
    echo You can also run the SQL file manually:
    echo mysql -u username -p database_name ^< frontpage-init.sql
    pause
    exit /b 1
)

echo MySQL client found.
echo.

REM Get database connection details
set /p DB_HOST="Enter MySQL host (default: localhost): "
if "%DB_HOST%"=="" set DB_HOST=localhost

set /p DB_USER="Enter MySQL username: "
if "%DB_USER%"=="" (
    echo ERROR: Username is required
    pause
    exit /b 1
)

set /p DB_NAME="Enter database name: "
if "%DB_NAME%"=="" (
    echo ERROR: Database name is required
    pause
    exit /b 1
)

echo.
echo Connecting to MySQL and running initialization script...
echo Host: %DB_HOST%
echo User: %DB_USER%
echo Database: %DB_NAME%
echo.

REM Run the SQL file
mysql -h %DB_HOST% -u %DB_USER% -p %DB_NAME% < frontpage-init.sql

if %errorlevel% equ 0 (
    echo.
    echo =====================================================
    echo ✅ Database initialization completed successfully!
    echo =====================================================
    echo.
    echo You can now access your frontpage application.
    echo The database has been populated with sample projects.
    echo.
) else (
    echo.
    echo =====================================================
    echo ❌ Database initialization failed!
    echo =====================================================
    echo.
    echo Please check your MySQL credentials and try again.
    echo You can also run the SQL file manually:
    echo mysql -h %DB_HOST% -u %DB_USER% -p %DB_NAME% ^< frontpage-init.sql
    echo.
)

echo Press any key to continue...
pause >nul
