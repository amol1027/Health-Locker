# Health Locker

Health Locker is a web-based application that allows users to securely store and manage health records for themselves and their family members. This application provides a centralized platform to keep track of medical history, appointments, and other important health information.

## Features

*   **User Authentication:** Secure user registration and login system.
*   **Family Member Management:** Add and manage profiles for multiple family members.
*   **Health Record Management:** Upload, view, and organize health records (including PDFs and images) for each family member.
*   **AI-Powered Report Simplification:** Automatically simplifies complex medical reports from PDFs or images into easy-to-understand summaries using AI.
*   **Reminders:** Set reminders for appointments, medications, and other health-related events.
*   **Secure Storage:** Ensures that all health records are stored securely.
*   **Dashboard:** A comprehensive dashboard to view all family members and their recent activities.

## Project Structure

The project is organized into the following directories:

*   `config/`: Contains the database and SMTP configuration file (`config.php`).
*   `database/`: Includes the database schema file (`health_sys.sql`).
*   `frontend/`: Contains the main application logic and user interface files.
*   `user/`: Handles user authentication, including registration, login, and logout.
*   `uploads/`: The directory where uploaded health records are stored.
*   `vendor/`: Contains composer dependencies.

## Database Schema

The database consists of three main tables:

*   `users`: Stores user information, including email and password.
*   `family_members`: Stores information about family members, linked to a user account.
*   `medical_records`: Stores medical records for each family member, including record type, date, and file path.

The database schema and relationships are defined in the `database/health_sys.sql` file.

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
    *   Import the `database/health_sys.sql` file into the `health_sys` database. This will create the necessary tables.

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

1.  Open your web browser and navigate to the project's URL (e.g., `http://localhost/Health-Locker/user/register.php`).
2.  Register a new account.
3.  Log in with your credentials.
4.  Add family members to your profile.
5.  Upload and manage health records for each family member.
