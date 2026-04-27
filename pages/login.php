<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Alert para sa Login Errors (Wrong password, User not found)
if (isset($_GET['error'])) {
    $msg = htmlspecialchars($_GET['error']);
    echo "
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: '$msg',
            background: 'radial-gradient(circle at center, #a83d3e 0%, #510708 100%)',
            color: '#ffffff',
            confirmButtonColor: '#a83d3e'
        });
    </script>";
}

// Alert para sa Registration Success (Galing sa register page)
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
        });
    </script>";
}
?>

<section class="login-page-wrapper">
    <div class="login-hero">
        <img src="Assets/Images/poster.png" class="hero-img" alt="OLSHCO Campus">
        <div class="overlay"></div>

        <div class="login-content">
            <div class="login-content-box">
                <h1 class="welcome-heading fade-up">
                    WELCOME TO: <br>
                    <span class="hub-title">OLSHCO Digital Hub</span>
                    <span class="sys-title">Centralized School Website & Information Management System</span>
                </h1>

                <p class="subtitle fade-up">
                    Rooted in Faith Grounded in Excellence
                </p>
            </div>

            <form action="index.php?page=login_action" method="POST" class="login-form-card fade-up">
                <div class="login-form-content">

                    <div class="form-header">
                        <img src="Assets/Images/nobgols.png" class="form-logo" alt="OLSHCO Logo">
                        <p class="account-prompt">Have an account?</p>
                    </div>

                    <div class="input-group">
                        <input type="text" name="studentID" placeholder="Student ID / Username" required>
                    </div>

                    <div class="input-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>

                    <div class="form-footer">
                        <label class="remember-me">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="?page=forgot" class="forgot-password">Forgot Password</a>
                    </div>

                    <button type="submit" name="signin" class="btn-get-started">Log In</button>

                    <p class="account-prompt" style="margin-top: 15px;">
                        Don't have an account?
                        <a href="?page=register" style="color: white; font-weight: 800; text-decoration: underline;">Sign Up</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</section>