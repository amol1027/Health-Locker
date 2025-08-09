# Health Locker

Health Locker is a web-based application that allows users to securely store and manage health records for themselves and their family members.

## Features

*   User registration and login
*   Add and manage family members
*   Upload and view health records for each family member
*   Set reminders for family members for appointments or medication.
*   Securely stores health records
*   Dashboard to view all family members

## Technologies Used

*   **Backend:** PHP
*   **Database:** MySQL
*   **Frontend:** HTML, Tailwind CSS

## Setup and Installation

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/amol1027/Health-Locker.git
    ```
2.  **Move the project to your web server's root directory.**
    For XAMPP, this is typically the `htdocs` folder.

3.  **Create a database:**
    *   Open phpMyAdmin or your preferred MySQL client.
    *   Create a new database named `health_sys`.

4.  **Import the database schema:**
    *   A SQL file with the required tables will be provided in the future. For now, you will need to create the tables manually.

## Database Configuration

1.  Open the `config/config.php` file.
2.  Update the database credentials if they are different from the default:
    ```php
    $host = 'localhost'; 
    $dbname = 'health_sys';
    $username = 'root';
    $password = ''; 
    ```

## Usage

1.  Open your web browser and navigate to the project's URL (e.g., `http://localhost/Health-Locker/frontend/register.php`).
2.  Register a new account.
3.  Log in with your credentials.
4.  Add family members to your profile.
5.  Upload and manage health records for each family member.
