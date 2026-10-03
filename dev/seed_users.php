<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/dbconnect.php';

echo "<h2>Seeding Users...</h2>";

$password = password_hash("password123", PASSWORD_DEFAULT);

/*
|--------------------------------------------------------------------------
| Helper Function
|--------------------------------------------------------------------------
*/

function createUser(
    $conn,
    $studID,
    $first,
    $middle,
    $last,
    $email,
    $gender,
    $age,
    $birthdate,
    $role,
    $department,
    $password
) {

    $check = $conn->prepare("
        SELECT user_id
        FROM user
        WHERE studID=?
    ");

    $check->bind_param("s", $studID);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        echo "Skipped {$studID}<br>";
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO user
        (
            studID,
            first_name,
            middle_name,
            last_name,
            email,
            created_at,
            updated_at,
            password,
            gender,
            age,
            birthdate,
            status,
            failed_attempts,
            role_id,
            department_id
        )
        VALUES
        (
            ?,?,?,?,?,NOW(),NOW(),?,?,?,?,?,0,?,?
        )
    ");

    $status = "Active";

    $stmt->bind_param(
        "sssssssissii",
        $studID,
        $first,
        $middle,
        $last,
        $email,
        $password,
        $gender,
        $age,
        $birthdate,
        $status,
        $role,
        $department
    );

    $stmt->execute();

    echo "Created {$studID}<br>";
}


/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

createUser(
    $conn,
    "99-0001",
    "System",
    "",
    "Administrator",
    "admin@olshco.edu.ph",
    "Male",
    30,
    "1996-01-01",
    1,
    4,
    $password
);

/*
|--------------------------------------------------------------------------
| FACULTY
|--------------------------------------------------------------------------
*/

createUser(
    $conn,
    "24-9001",
    "Maria",
    "",
    "Santos",
    "maria.santos@olshco.edu.ph",
    "Female",
    34,
    "1992-05-10",
    2,
    4,
    $password
);

createUser(
    $conn,
    "24-9002",
    "John",
    "",
    "Reyes",
    "john.reyes@olshco.edu.ph",
    "Male",
    41,
    "1985-08-12",
    2,
    4,
    $password
);


/*
|--------------------------------------------------------------------------
| STUDENTS
|--------------------------------------------------------------------------
*/

$students = [

    ["24-0001", "Juan", "", "Dela Cruz", "juan@olshco.edu.ph", "Male"],
    ["24-0002", "Maria", "", "Garcia", "maria@olshco.edu.ph", "Female"],
    ["24-0003", "Kevin", "", "Ramos", "kevin@olshco.edu.ph", "Male"],
    ["24-0004", "Angela", "", "Torres", "angela@olshco.edu.ph", "Female"],
    ["24-0005", "Joshua", "", "Rivera", "joshua@olshco.edu.ph", "Male"],
    ["24-0006", "Patricia", "", "Lopez", "patricia@olshco.edu.ph", "Female"],
    ["24-0007", "Christian", "", "Perez", "christian@olshco.edu.ph", "Male"],
    ["24-0008", "Nicole", "", "Flores", "nicole@olshco.edu.ph", "Female"],
    ["24-0009", "Mark", "", "Aquino", "mark@olshco.edu.ph", "Male"],
    ["24-0010", "Ashley", "", "Castro", "ashley@olshco.edu.ph", "Female"]

];

foreach ($students as $student) {

    createUser(

        $conn,

        $student[0],
        $student[1],
        $student[2],
        $student[3],
        $student[4],
        $student[5],

        rand(18, 23),

        "2005-01-01",

        3,

        4,

        $password
    );
}

echo "<hr>";
echo "<h3>User Seeder Complete.</h3>";
echo "<strong>Default Password:</strong> password123";
