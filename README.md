# EDUS Web

Academic healthcare appointment management system built as a full-stack web application. It supports patient self-service and administrative workflows through a browser-based interface and PHP JSON APIs.

## Features

- Patient registration and authentication
- Patient and administrator roles
- Appointment search, booking, rescheduling, and cancellation
- Doctor, specialty, schedule, and patient administration
- Appointment status reporting
- Transactional schedule availability updates
- Responsive interface with asynchronous API calls

## Technology

- HTML5, CSS3, Bootstrap 5
- JavaScript, jQuery, AJAX
- PHP and PDO
- MySQL
- Mermaid documentation
- XAMPP for local development

## Architecture

```text
Browser interface
      |
JavaScript / AJAX
      |
PHP JSON endpoints
      |
PDO data access
      |
MySQL database
```

The database contains four principal entities: users, doctors, schedules, and appointments. Foreign keys preserve relationships between patients, doctors, available schedules, and booked appointments.

## Run locally

1. Install XAMPP with Apache, PHP, and MySQL.
2. Clone the repository into the XAMPP `htdocs` directory.
3. Import `backend/db/schema.sql` into MySQL.
4. Review the local connection settings in `backend/config/database.php`.
5. Start Apache and MySQL.
6. Open `http://localhost/Edus_WEB/`.

The seed administrator is intended for local demonstration only. Replace or remove all default credentials before deploying outside a local environment.

## Learning outcomes

- Designed a relational schema with foreign keys
- Implemented session-based authentication and role authorization
- Built JSON endpoints with prepared SQL statements
- Used transactions and row locking to protect appointment availability
- Connected a responsive front end to server-side APIs
- Documented the system and its relational model

## Scope

This is an academic portfolio project. It uses synthetic demonstration data and is not connected to the Costa Rican Social Security Fund or any production healthcare system.

