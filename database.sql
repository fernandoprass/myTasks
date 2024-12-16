-- Create database
CREATE DATABASE IF NOT EXISTS task_manager;
USE task_manager;

-- Create users table (intentionally insecure - storing passwords in plain text)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(50),  -- Intentionally using plain text password
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create tasks table
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    description TEXT,
    user_id INT,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    task_id INT,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (task_id) REFERENCES tasks(id)
);

-- Insert sample data
INSERT INTO users (username, password, email) VALUES
('admin', 'admin123', 'admin@example.com'),
('john', 'password123', 'john@example.com'),
('mary', 'mary123', 'mary@example.com');

INSERT INTO tasks (title, description, user_id, status, due_date) VALUES
('Complete Project', 'Finish the task management system', 1, 'pending', '2024-12-31'),
('Review Code', 'Review the new features', 2, 'in_progress', '2024-12-25'),
('Test Application', 'Perform system testing', 3, 'pending', '2024-12-28');

INSERT INTO notifications (user_id, task_id, message) VALUES
(1, 1, 'New task assigned to you'),
(2, 2, 'Task status updated'),
(3, 3, 'Task due date approaching');
