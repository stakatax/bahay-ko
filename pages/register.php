<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
/**
 * FEEDBACK LOGIC
 * Nakikinig ito sa 'success' o 'error' parameters mula sa config/authentication.php
 */
if (isset($_GET['success'])) {
    $msg = htmlspecialchars($_GET['success']);
    echo "
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Registration Successful!',
            text: '$msg',
            background: 'radial-gradient(circle at center, #a83d3e 0%, #510708 100%)',
            color: '#ffffff',
            confirmButtonColor: '#a83d3e'
        }).then(() => { window.location.href='?page=login'; });
    </script>";
}

if (isset($_GET['error'])) {
    $msg = htmlspecialchars($_GET['error']);
    echo "
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Registration Failed',
            text: '$msg',
            background: 'radial-gradient(circle at center, #a83d3e 0%, #510708 100%)',
            color: '#ffffff',
            confirmButtonColor: '#a83d3e'
        });
    </script>";
}
?>

<section class="registration-page-wrapper">
    <div class="registration-hero">
        <img src="Assets/Images/poster.png" class="hero-img" alt="OLSHCO Campus">
        <div class="overlay"></div>

        <div class="registration-content">
            <div class="registration-content-box">
                <h1 class="welcome-heading fade-up">
                    WELCOME TO: <br>
                    <span class="hub-title">OLSHCO Digital Hub</span>
                    <span class="sys-title">Centralized School Website & Information Management System</span>
                </h1>
                <p class="subtitle fade-up">Rooted in Faith Grounded in Excellence</p>
            </div>

            <form id="registrationForm" action="config/authentication.php" method="POST" class="registration-form-card fade-up">
                <div class="form-header">
                    <h3>Student Registration</h3>
                    <p class="account-prompt">Complete the details below to join.</p>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" placeholder="Juan" required>
                    </div>
                    <div class="input-group mi-col">
                        <label>M.I.</label>
                        <input type="text" name="middle_name" placeholder="D." maxlength="2">
                    </div>
                    <div class="input-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" placeholder="Dela Cruz" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group flex-3">
                        <label>Student ID</label>
                        <input type="text" name="studentID" placeholder="17-XXXXX" required>
                    </div>
                    <div class="input-group flex-1">
                        <label>Age</label>
                        <input type="number" name="age" placeholder="0" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="juan@email.com" required>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label>Gender</label>
                        <select name="gender" required>
                            <option value="" disabled selected>Select...</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Department</label>
                        <select name="department" required>
                            <option value="" disabled selected>Select...</option>
                            <option value="1">Elementary</option>
                            <option value="2">High School</option>
                            <option value="3">Senior High School</option>
                            <option value="4">College</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="*******" required>
                </div>

                <button type="button" onclick="confirmRegistration()" class="btn-register-now">Register Now</button>

                <input type="submit" name="signup" id="hiddenSubmit" style="display: none;">

                <p class="account-prompt" style="margin-top: 10px; text-align: center;">
                    Already have an account? <a href="?page=login" style="color: white; font-weight: 800;">Log In</a>
                </p>
            </form>
        </div>
    </div>
</section>

<script src="Assets/js/register.js"></script>