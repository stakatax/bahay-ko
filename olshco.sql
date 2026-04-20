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