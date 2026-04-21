<?php

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once "dbconnect.php";
    require_once "logging.php";

    // REGISTER //
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
            die("Role not found");
        }

        $role_id = $roleRow['role_id'];

        $_SESSION['role_id'] = $role_id;
        $_SESSION['role'] = $role_prefix;

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

            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['name'] = $fname . " " . $lname;

            logActivity(
                $conn,
                "REGISTER_ACCOUNT",
                "New user registered: " . $_SESSION['name']
            );

            header("Location: ../index.php");
            exit();

        } else {
            echo "Sign up failed: " . $stmt->error;
        }
    }


    // LOGIN //

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

                // ✅ SESSION DATA
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role_id'] = $row['role_id'];
                $_SESSION['role'] = $row['role_prefix']; // ⭐ THIS IS THE KEY FIX
                $_SESSION['name'] = $row['first_name'] . " " . $row['last_name'];

                // optional: role-based redirect
                if ($row['role_prefix'] === 'Admin') {
                    header("Location: ../index.php?page=dashboard");
                } elseif ($row['role_prefix'] === 'Faculty') {
                    header("Location: ../index.php?page=home");
                } else {
                    header("Location: ../index.php?page=home");
                }

                logActivity(
                    $conn,
                    "LOGIN",
                    $_SESSION['name'] . " logged in as " . $_SESSION['role']
                );

                exit();

            } else {
                echo "Wrong password";
            }

        } else {
            echo "User not found";
        }
    }


?>