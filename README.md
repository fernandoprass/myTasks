# Task Manager - Educational Project

A deliberately vulnerable task management system designed for educational purposes to help junior developers learn about web security.

## Description

This project is a simple task management system built with PHP and MySQL. It intentionally contains various security vulnerabilities for educational purposes. The goal is to help developers identify, understand, and fix common security issues in web applications.

## Security Notice ⚠️

**WARNING**: This application contains intentional security vulnerabilities. DO NOT deploy it in a production environment or expose it to the public internet. It is designed solely for educational purposes in a controlled, local environment.

## Features

- User registration and authentication
- Task creation, reading, updating, and deletion (CRUD)
- Task status management
- Notification system
- Search functionality

## Intentional Security Issues

This application contains the following security vulnerabilities:
1. SQL Injection vulnerabilities
2. Cross-Site Scripting (XSS)
3. Cross-Site Request Forgery (CSRF)
4. Insecure password storage
5. Session management issues
6. Missing input validation
7. Directory traversal vulnerabilities
8. Information disclosure

## Project Structure

```
MyTasks/
├── assets/
│   └── css/
│       └── style.css
├── controllers/
│   ├── AuthController.php
│   ├── TaskController.php
│   └── NotificationController.php
├── includes/
│   └── config.php
├── views/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── tasks.php
│   └── notifications.php
└── database.sql
```

## Installation

1. Clone this repository
2. Create a MySQL database named 'task_manager'
3. Import the `database.sql` file into your database
4. Configure your database connection in `includes/config.php`
5. Start your PHP server
6. Access the application through your web browser

## Educational Objectives

Students should:
1. Identify security vulnerabilities in the code
2. Understand why each vulnerability is dangerous
3. Implement fixes using security best practices
4. Learn about secure coding practices

## License

MIT License

Copyright (c) 2024 Task Manager Educational Project

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

## Disclaimer

This project is for educational purposes only. The authors are not responsible for any misuse or damage caused by the intentional security vulnerabilities in this code.
