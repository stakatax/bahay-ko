<section class="registration-page-wrapper">
    <div class="registration-hero">
        <img src="Assets/Images/poster.png" class="hero-img" alt="OLSHCO Campus">
        <div class="overlay"></div>

        <div class="registration-content">
            <div class="registration-content-box">
                <h1 class="welcome-heading fade-up fade-up-delay">
                    WELCOME TO: <br>
                    <span class="hub-title">OLSHCO Digital Hub</span>
                    <span class="sys-title">Centralized School Website & Information Management System</span>
                </h1>

                <p class="subtitle fade-up fade-up-delay-2">
                    Rooted in Faith Grounded in Excellence
                </p>
            </div>

            <form action="process_registration.php" method="POST" class="registration-form-card fade-up fade-up-delay-2">
                <div class="form-header">
                    <h3>Student Registration</h3>
                    <p class="account-prompt">Complete the details below to join.</p>
                </div>

                <div class="form-row three-col">
                    <div class="input-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" placeholder="Juan" required>
                    </div>
                    <div class="input-group mi-col">
                        <label>M.I.</label>
                        <input type="text" name="mi" placeholder="D." maxlength="2">
                    </div>
                    <div class="input-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" placeholder="Dela Cruz" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group flex-3">
                        <label>Student ID</label>
                        <input type="text" name="student_id" placeholder="17-XXXXX" required>
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
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Department</label>
                        <select name="department" required>
                            <option value="" disabled selected>Select...</option>
                            <option>Elementary</option>
                            <option>High School</option>
                            <option>Senior High School</option>
                            <option>College</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-register-now">Register Now</button>
            </form>
        </div>
    </div>
</section>