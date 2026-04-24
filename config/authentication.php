<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "dbconnect.php";
require_once "logging.php";

// --- REGISTER ---
if (isset($_POST['signup'])) {

    $fname = $_POST['first_name'];
    $mname = $_POST['middle_name'];
    $lname = $_POST['last_name'];
    $studID = $_POST['studentID'];
    $age = $_POST['age'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $dept = $_POST['department'];

    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $role_prefix = "Student";

    $stmtRole = $conn->prepare(
        "SELECT role_id, role_prefix 
             FROM role 
             WHERE role_prefix = ?"
    );

    $stmtRole->bind_param("s", $role_prefix);
    $stmtRole->execute();

    $resultRole = $stmtRole->get_result();
    $roleRow = $resultRole->fetch_assoc();

    if (!$roleRow) {
        header("Location: ../index.php?page=register&error=System Error: Role not found.");
        exit();
    }

    $role_id = $roleRow['role_id'];

    $stmt = $conn->prepare(
        "INSERT INTO user
            (studID, first_name, middle_name, last_name,
            email, password, gender, age,
            role_id, department_id, created_at)

            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );

    $stmt->bind_param(
        "sssssssiii",
        $studID,
        $fname,
        $mname,
        $lname,
        $email,
        $pass,
        $gender,
        $age,
        $role_id,
        $dept
    );

    if ($stmt->execute()) {

        /* 🛑 REMOVED AUTOMATIC LOGIN
           Hindi na natin ise-set ang $_SESSION['user_id'] dito.
        */

        logActivity(
            $conn,
            "REGISTER_ACCOUNT",
            "New user registered: " . $fname . " " . $lname
        );

        // ✅ Redirect sa Login Page na may Success Message
        header("Location: ../index.php?page=login&success=Registration successful! You can now log in.");
        exit();
    } else {
        // Redirect back with error message
        header("Location: ../index.php?page=register&error=Registration failed. ID might already exist.");
        exit();
    }
}


// --- LOGIN ---
if (isset($_POST['signin'])) {

    $studID = $_POST['studentID'];
    $pass = $_POST['password'];

    $stmt = $conn->prepare(
        "SELECT u.*, r.role_prefix 
             FROM user u
             JOIN role r ON u.role_id = r.role_id
             WHERE u.studID = ?"
    );

    $stmt->bind_param("s", $studID);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        if (password_verify($pass, $row['password'])) {

            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role_id'] = $row['role_id'];
            $_SESSION['role'] = $row['role_prefix'];
            $_SESSION['name'] = $row['first_name'] . " " . $row['last_name'];

            logActivity(
                $conn,
                "LOGIN",
                $_SESSION['name'] . " logged in as " . $_SESSION['role']
            );

            if ($row['role_prefix'] === 'Admin') {
                header("Location: ../index.php?page=dashboard");
            } else {
                header("Location: ../index.php?page=home");
            }
            exit();
        } else {
            // SweetAlert error for wrong password
            header("Location: ../index.php?page=login&error=Incorrect password.");
            exit();
        }
    } else {
        // SweetAlert error for user not found
        header("Location: ../index.php?page=login&error=User ID not found.");
        exit();
    }
}
