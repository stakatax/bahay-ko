function switchType(type, element) {
    document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('active'));
    element.classList.add('active');

    document.getElementById('post_type').value = type;

    const form = document.querySelector('.post-card form');
    const eventFields = document.getElementById('event-specific-fields');
    const uploadText = document.getElementById('upload-text');
    const submitBtn = document.getElementById('submit-btn');
    const contentLabel = document.getElementById('content-label');

    if (type === 'event') {
        form.action = "/config/upload_event.php";

        eventFields.style.display = 'block';
        uploadText.innerText = "Upload Event Poster (Optional) or";
        submitBtn.innerText = "Publish Event";
        contentLabel.innerText = "Event Description:";
    } 
    else if (type === 'document') {
        form.action = "/config/upload_document.php";

        eventFields.style.display = 'none';
        uploadText.innerText = "Upload PDF/Document (Required) or";
        submitBtn.innerText = "Upload Document";
        contentLabel.innerText = "Document Summary:";
    } 
    else {
        form.action = "/config/upload_announcement.php";

        eventFields.style.display = 'none';
        uploadText.innerText = "Drag & Drop Image or";
        submitBtn.innerText = "Publish Announcement";
        contentLabel.innerText = "Announcement Content:";
    }
}

// NEW FUNCTION: Show/Hide Department list based on Scope
function toggleDepartment(val) {
    const deptDiv = document.getElementById('department-selection');
    if (val === 'departmental') {
        deptDiv.style.display = 'flex'; // Shows the department dropdown
    } else {
        deptDiv.style.display = 'none'; // Hides it for Schoolwide
    }
}