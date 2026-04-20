<?php

    session_start();
    require_once "dbconnect.php";
    require_once "logging.php";

    // REGISTER //
    if (isset($_POST['signup'])) {

        $fname = $_POST['first_name'];
        $mname = $_POST['middle_name'];
        $lname = $_POST['last_name'];
        $email = $_POST['email'];
        $gender = $_POST['gender'];
        $dept = $_POST['department'];
        $studID = $_POST['studentID'];

        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $role_prefix = "Student";

        $stmtRole = 
            $conn->prepare(
                "SELECT role_id
                 FROM role
                 WHERE role_prefix = ?
                "
            );
        
        $stmtRole->bind_param("s", $role_prefix);
        $stmtRole->execute();

        $resultRole = $stmtRole->get_result();
        $roleRow = $resultRole->fetch_assoc();

        if ($roleRow) {
            $_SESSION['role_id'] = $row['role_id'];
            $_SESSION['role'] = $row['role_prefix'];
        } else {
            die("Role not found");
        }

        $stmt = $conn->prepare(
            "INSERT INTO user
            (studID, first_name, middle_name, last_name,
            email, password, gender,
            role_id, department_id, created_at)

            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );

        $stmt->bind_param(
            "sssssssii",
            $studID,
            $fname,
            $mname,
            $lname,
            $email,
            $pass,
            $gender,
            $role_id,
            $dept
        );

        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;

            $_SESSION['role_id'] = $role_id;

            logActivity(
                $conn,
                "SIGNUP",
                "New user registered"
            );

            header("Location: ../index.php");
            exit();
        } else {
            echo "sign up failed";
        }
    }

    // LOGIN //

    if (isset($_POST['signIn'])) {

        $studID = $_POST['studentID'];
        $pass = $_POST['password'];

        $stmt = $conn->prepare(
            "SELECT * FROM user
             WHERE studID = ?"
        );

        $stmt->bind_param("s", $studID);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            if (password_verify($pass, $row['password'])) {

                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role_id'] = $row['role_id'];

                logActivity(
                    $conn,
                    "LOGIN",
                    $_SESSION['name'] . " logged in"
                );

                header("Location: ../index.php");
                exit();

            } else {
                echo "Wrong password";
            }
        } else {
            echo "User not found";
        }
    }


?>