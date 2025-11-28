# PHP Project with SQLite

This project has been modernized to use SQLite and PDO, making it easy to run and deploy.

## Prerequisites

1.  **PHP**: You need PHP installed on your system.
    *   **Windows**: Download from [windows.php.net](https://windows.php.net/download/). Extract it to a folder (e.g., `C:\php`). Add this folder to your system PATH.
    *   **Mac/Linux**: Usually installed by default, or install via `brew install php` or `apt install php`.
2.  **PHP Extensions**: Ensure the following extensions are enabled in your `php.ini` file:
    *   `extension=pdo_sqlite`
    *   `extension=sqlite3`
    *   `extension=mbstring` (optional but recommended)

## Setup

1.  **Initialize Database**:
    Open a terminal/command prompt in the project directory and run:
    ```bash
    php setup_database.php
    ```
    This will create a `database.sqlite` file and seed it with initial data.

## Running Locally

1.  **Start Server**:
    You can use the built-in PHP server. Run the provided `run.bat` file (on Windows) or execute:
    ```bash
    php -S localhost:8000
    ```
2.  **Access Application**:
    Open your browser and visit: [http://localhost:8000](http://localhost:8000)

## Deployment (Online Server)

1.  **Upload Files**: Upload all project files to your web server (e.g., via FTP or Git).
2.  **Database**:
    *   Ensure the `database.sqlite` file is uploaded.
    *   **Important**: Make sure the directory containing `database.sqlite` has write permissions for the web server user, so the application can write to the database.
3.  **Configuration**:
    *   The `db_connect.php` file is already configured to use the SQLite database in the same directory. No further configuration is usually needed.
4.  **Security**:
    *   Protect your `database.sqlite` file from direct download by configuring your web server (e.g., using `.htaccess` for Apache).
    *   Example `.htaccess` rule:
        ```apache
        <Files "database.sqlite">
            Order allow,deny
            Deny from all
        </Files>
        ```

## User Credentials (Default)

*   **Admin**:
    *   Email: `admin01@gmail.com`
    *   Password: (As seeded in `setup_database.php`)
*   **Client**:
    *   Email: `jefin03@gmail.com`
    *   Password: (As seeded in `setup_database.php`)
