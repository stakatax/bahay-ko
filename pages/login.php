<section class="login-page-wrapper">
    <div class="login-hero">
        <img src="Assets/Images/poster.png" class="hero-img" alt="OLSHCO Campus">
        <div class="overlay"></div>

        <div class="login-content">
            <div class="login-content-box">


                <h1 class="welcome-heading fade-up fade-up-delay">
                    WELCOME TO: <br>
                    <span class="hub-title">OLSHCO Digital Hub</span>
                    <span class="sys-title">Centralized School Website & Information Management System</span>
                </h1>

                <p class="subtitle fade-up fade-up-delay-2">
                    Rooted in Faith Grounded in Excellence
                </p>
            </div>

            <form action="process_login.php" method="POST" class="login-form-card fade-up fade-up-delay-2">
                <div class="login-form-content">

                    <div class="form-header">
                        <img src="Assets/Images/nobgols.png" class="form-logo" alt="Form Logo">
                        <p class="account-prompt">Have an account?</p>
                    </div>

                    <div class="input-group">
                        <input type="text" name="username" placeholder="Username" required>
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

                    <button type="submit" class="btn-get-started">Get Started</button>

                    <p class="account-prompt" style="margin-top: 10px;">
                        Don't have an account? <a href="?page=register" style="color: white; font-weight: 800;">Sign Up</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</section>