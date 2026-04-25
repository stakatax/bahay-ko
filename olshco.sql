CREATE DATABASE olshcodb;

USE olshcodb;

CREATE TABLE role (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_prefix VARCHAR(150) UNIQUE
);

CREATE TABLE department (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) UNIQUE
);

CREATE TABLE user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,

    studID VARCHAR(50) UNIQUE NOT NULL,

    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(100) NOT NULL,

    email VARCHAR(100) UNIQUE,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    password VARCHAR(255) NOT NULL,

    gender ENUM('Male','Female','Other') NOT NULL,

    age INT NOT NULL,

    role_id INT,
    department_id INT,

    FOREIGN KEY (role_id)
        REFERENCES role(role_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    FOREIGN KEY (department_id)
        REFERENCES department(department_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50),

    user_id INT,

    FOREIGN KEY (user_id)
        REFERENCES user(user_id)
        ON DELETE SET NULL
);

CREATE TABLE documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'active',
    user_id INT,
    
    FOREIGN KEY (user_id)
        REFERENCES user(user_id)
        ON DELETE SET NULL
);

CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(100) NOT NULL,
    event_date DATE,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50),

    user_id INT,

    FOREIGN KEY (user_id)
        REFERENCES user(user_id)
        ON DELETE SET NULL
);

CREATE TABLE actions (
    action_id INT AUTO_INCREMENT PRIMARY KEY,
    action_name VARCHAR(100) NOT NULL
);

CREATE TABLE activity_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,

    description VARCHAR(255) NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,

    action_id INT,
    user_id INT,

    FOREIGN KEY (action_id)
        REFERENCES actions(action_id),

    FOREIGN KEY (user_id)
        REFERENCES user(user_id)
);


INSERT INTO actions (action_id, action_name) VALUES
(1, 'LOGIN'),
(2, 'REGISTER_ACCOUNT'),
(3, 'LOGOUT'),
(4, 'VIEW_DASHBOARD'),
(5, 'ADD_USER'),
(6, 'VIEW_USER'),
(7, 'UPDATE_PROFILE'),
(8, 'ACTIVATE_ACCOUNT'),
(9, 'DEACTIVATE_ACCOUNT'),
(10, 'POST_ANNOUNCEMENT'),
(11, 'VIEW_ANNOUNCEMENT'),
(12, 'UPDATE_POST'),
(13, 'DELETE_POST'),
(14, 'ARCHIVE_POST'),
(15, 'APPROVE_CONTENT'),
(16, 'UPLOAD_DOCUMENT'),
(17, 'POST_DOCUMENT'),
(18, 'DOWNLOAD_DOCUMENT'),
(19, 'POST_EVENT'),
(20, 'MANAGE_USERS'),
(21, 'ACCESS_ACTIVITY_LOG');

INSERT INTO role (role_prefix)
VALUES ('Admin'),
        ('Faculty'),
        ('Student');

INSERT INTO department (department_name)
VALUES ('Elementary'),
        ('Junior High'),
        ('Senior High'),
        ('College');

INSERT INTO user (studID, first_name, middle_name, last_name, email, created_at, password, gender, age, role_id, department_id)
VALUES
(1234567, 'admin', 't', 'test', 'admin@gmail.com', NOW(), 'admin', 'male', 25, 1, 4),
(7654321, 'faculty', 't', 'test', 'faculty@gmail.com', NOW(), 'faculty', 'female', 22, 2, 4);