
<section class="reg-hero">
    <img src="Assets/Images/poster.png" class="reg-hero-img">
    <div class="reg-overlay"></div>

    <div class="content registration-split">
        <div class="content-box branding-box fade-up">
            <p class="welcome-prefix">WELCOME TO:</p>
            <h1 class="title">
                OUR LADY <br>
                OF SACRED <br>
                HEART COLLEGE
            </h1>
            <p class="subtitle">Rooted in Faith Grounded in Excellence</p>
        </div>

        <div class="registration-form-card fade-up fade-up-delay">
            <div class="form-header">
                <h3>Student Registration</h3>
                <p>Complete the details below to join our community.</p>
            </div>

            <form action="confg/authentication.php" method="POST">
                <div class="form-row three-col">
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
                        <input type="text" name="studID" placeholder="17-XXXXX" required>
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

                <button type="submit" class="btn-register-now">Register Now</button>
            </form>
        </div>
    </div>
</section>