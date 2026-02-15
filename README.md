1. Introduction
Heritage Crestline Realty is a fully database-driven luxury real estate web application developed using PHP and MySQL. The purpose of this project was to design and implement a dynamic property management system that simulates the functionality of a professional real estate platform.
The system enables users to browse properties, save favourites, submit enquiries, and book property viewings. Additionally, it includes a dedicated administrative panel for managing properties, users, and database records.
This project demonstrates full-stack development skills, relational database design, and backend logic implementation.

2. Project Objectives
The main objectives of this project were:
To design a structured relational database for a real estate system
To implement dynamic web pages using PHP and MySQL
To develop secure user authentication and session handling
To create modular, reusable components (header and footer separation)
To implement CRUD (Create, Read, Update, Delete) functionality
To simulate real-world property listing and management systems

3. System Architecture
The system follows a standard client-server architecture:
Frontend (Client Side):
HTML
CSS
JavaScript
Backend (Server Side):
PHP
Database Layer:
MySQL relational database
The application uses PHP to handle server-side logic and database communication. All dynamic content is fetched from and stored in the MySQL database.

4. Database Design
The application is powered by eight relational database tables:
users
properties
propert_images
property_types
favourites
inquiries
viewings
staff
The database design follows relational principles:
Primary and foreign key relationships
Normalised data structure
Separation of concerns between users, properties, and interactions
Efficient querying for large property datasets
This structure ensures scalability and data consistency.

5. Key Functionalities
5.1 User Features
User registration and login
Secure session-based authentication
Browse properties by category:
Student Accommodation
Villa
Penthouse
Apartment
Bungalow
View detailed property pages with images and descriptions
Add and remove properties from favourites
Submit property inquiries
Book property viewings
Access a personal dashboard to manage favourites, inquiries, and bookings
5.2 Admin Features
Admin login authentication
Add new property listings
Edit existing properties
Delete property records
Manage property images
View and manage user inquiries
Monitor bookings and user interactions
The admin panel provides full control over the database content and property listings.

6. Implementation Details
6.1 Modular Structure
The system uses reusable components such as:
header.php
footer.php
config.php (database connection)
This improves maintainability and separation of concerns.
6.2 CRUD Operations
The project implements complete CRUD functionality:
Create – Add properties, users, bookings
Read – Display property listings and dashboard data
Update – Modify property details
Delete – Remove properties and records
6.3 Session Management
User authentication is handled using PHP sessions to ensure:
Secure login persistence
Restricted access to dashboards
Separation between admin and user roles

7. Security Considerations
The system includes:
Session-based authentication
Basic input validation
Role-based access control (admin vs user)
Structured database interaction
Future improvements would include the use of prepared statements and enhanced input sanitisation for improved protection against SQL injection.

8. Challenges Faced
During development, the following challenges were encountered:
Managing relational database connections across multiple tables
Ensuring correct foreign key relationships
Handling dynamic data rendering using PHP
Maintaining clean, modular code structure
Debugging session and authentication issues
These challenges helped strengthen backend debugging and problem-solving skills.

9. Learning Outcomes
Through this project, the following technical skills were developed:
Full-stack web development
PHP-MySQL integration
Relational database modelling
CRUD implementation
Session management
Backend debugging and troubleshooting
Modular application design
The project significantly improved understanding of how real-world database-driven systems are built and maintained.

10. Future Improvements
The system can be further enhanced by:
Implementing prepared statements for improved security
Adding advanced property filtering and search functionality
Integrating email notifications for bookings
Improving UI responsiveness
Deploying the application on a live hosting platform
Implementing role-based authentication using hashed passwords

11. Conclusion
Heritage Crestline Realty successfully demonstrates the development of a dynamic, database-driven web application with both user and administrative functionalities. The project reflects strong backend development skills, relational database understanding, and the ability to build a structured and scalable system.
This project showcases practical experience in software development and represents a significant step toward building production-ready web applications.
