# Task Manager - Educational Project

## Description

This project is a simple task management system built with PHP and MySQL. It intentionally contains various issues. The goal is to help developers identify, understand, and fix common issues in web applications.

## Security Notice ⚠️

**WARNING**: DO NOT deploy it in a production environment or expose it to the public internet.

## Features

- User registration and authentication
- Task creation, reading, updating, and deletion (CRUD)
- Task status management
- Notification system
- Search functionality

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
