<div class="post-container">
    <div class="post-card">
        <h1 class="post-title">Publish Post</h1>
        <div class="type-selector">
            <button type="button" class="type-btn active" onclick="switchType('announcement', this)">Announcement</button>
            <button type="button" class="type-btn" onclick="switchType('event', this)">Event</button>
            <button type="button" class="type-btn" onclick="switchType('document', this)">Document</button>
        </div>

        <form action="upload_handler.php" method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" placeholder="Enter title..." required>
            </div>

            <div class="input-group">
                <label for="scope">Publish To:</label>
                <select id="scope" name="scope" onchange="toggleDepartment(this.value)">
                    <option value="schoolwide">Schoolwide</option>
                    <option value="departmental">Departmental Only</option>
                </select>
            </div>

            <div id="department-selection" class="input-group" style="display: none;">
                <label for="department">Select Department:</label>
                <select id="department" name="department">
                    <option value="elementary">Elementary</option>
                    <option value="highschool">High School</option>
                    <option value="shs">Senior High School</option>
                    <option value="college">College</option>
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
                <textarea id="content" name="content" rows="6" placeholder="Write details here..."></textarea>
            </div>

            <div class="file-drop-area">
                <span class="fake-btn">
                    <span id="upload-text">Drag & Drop Image or</span>
                    <span class="browse-text">Browse Files</span>
                </span>
                <input class="file-input" type="file" name="attachment">
            </div>

            <button type="submit" class="publish-btn" id="submit-btn">Publish Announcement</button>
        </form>
    </div>
</div>

<script src="Assets/js/posting.js"></script>