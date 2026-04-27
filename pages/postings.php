<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Feedback Alerts from URL Parameters
if (isset($_GET['success'])) {
    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Published!',
            text: 'Your post is now live on the hub.',
            background: 'radial-gradient(circle at center, #a83d3e 0%, #510708 100%)',
            color: '#ffffff',
            confirmButtonColor: '#a83d3e'
        });
    </script>";
}
if (isset($_GET['error'])) {
    $msg = htmlspecialchars($_GET['error']);
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Publishing Failed',
            text: '$msg',
            background: 'radial-gradient(circle at center, #a83d3e 0%, #510708 100%)',
            color: '#ffffff',
            confirmButtonColor: '#a83d3e'
        });
    </script>";
}
?>

<div class="post-container">
    <div class="post-card fade-up">
        <h1 class="post-title">Publish Post</h1>

        <div class="type-selector">
            <button type="button" class="type-btn active" onclick="switchType('announcement', this)">Announcement</button>
            <button type="button" class="type-btn" onclick="switchType('event', this)">Event</button>
            <button type="button" class="type-btn" onclick="switchType('document', this)">Document</button>
        </div>

        <form id="postForm" action="index.php?page=post_store" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="post_type" id="post_type" value="announcement">

            <div class="input-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" placeholder="Enter title..." required>
            </div>

            <div class="input-group">
                <label for="scope">Publish To:</label>
                <select id="scope" name="scope" onchange="toggleDepartment(this.value)">
                    <?php if ($role === 'Faculty'): ?>
                        <option value="departmental" selected>Departmental Only</option>
                    <?php else: ?>
                        <option value="schoolwide">Schoolwide</option>
                        <option value="departmental">Departmental Only</option>
                    <?php endif; ?>
                </select>
            </div>

            <div id="department-selection" class="input-group" style="<?= ($role === 'Faculty') ? 'display:block;' : 'display:none;' ?>">
                <label for="department">Select Department:</label>
                <select id="department" name="department">
                    <?php
                    require_once "config/dbconnect.php";
                    $deptQuery = "SELECT department_id, department_name FROM department";
                    $deptResult = $conn->query($deptQuery);
                    while ($row = $deptResult->fetch_assoc()) {
                        echo "<option value='{$row['department_id']}'>{$row['department_name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div id="event-specific-fields" style="display: none;">
                <div class="input-group">
                    <label for="event_date">Event Date & Time:</label>
                    <input type="datetime-local" id="event_date" name="event_date">
                </div>
            </div>

            <div class="input-group">
                <label id="content-label" for="content">Announcement Content:</label>
                <textarea id="content" name="content" rows="6" placeholder="Write details here..." required></textarea>
            </div>

            <div class="file-drop-area">
                <span class="fake-btn">
                    <span id="upload-text">Drag & Drop Image or</span>
                    <span class="browse-text">Browse Files</span>
                </span>
                <input class="file-input" type="file" name="attachment" id="attachment">
            </div>

            <button type="button" class="publish-btn" onclick="confirmPost()">Publish Announcement</button>

            <input type="submit" name="publish_post" id="hiddenSubmit" style="display: none;">
        </form>
    </div>
</div>

<script src="Assets/js/posting.js"></script>