

function switchType(type, btn) {
    const form = document.getElementById('postForm'); // Kunin ang form
    const eventFields = document.getElementById('event-specific-fields');
    const submitBtn = document.querySelector('.publish-btn');
    const typeInput = document.getElementById('post_type');

    // 1. Linisin ang 'active' class sa mga buttons
    document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    typeInput.value = type;

    // 2. BAGUHIN ANG ACTION NG FORM DITO:
    if (type === 'event') {
        form.action = 'config/upload_event.php'; // Mapupunta na ito sa events table
        eventFields.style.display = 'block';
        submitBtn.innerText = "Publish Event";
    } 
    else if (type === 'document') {
        form.action = 'config/upload_document.php'; // Mapupunta sa documents table
        eventFields.style.display = 'none';
        submitBtn.innerText = "Upload Document";
    } 
    else {
        form.action = 'config/upload_announcement.php'; // Balik sa announcement
        eventFields.style.display = 'none';
        submitBtn.innerText = "Publish Announcement";
    }
}

// Function for Department Toggle
function toggleDepartment(value) {
    const deptDiv = document.getElementById('department-selection');
    deptDiv.style.display = (value === 'departmental') ? 'block' : 'none';
}

// SWEETALERT CONFIRMATION
function confirmPost() {
    const form = document.getElementById('postForm');
    const type = document.getElementById('post_type').value;

    // Check if required fields are filled
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    Swal.fire({
        title: 'Ready to Publish?',
        text: "This will be visible to " + document.getElementById('scope').value + " users.",
        icon: 'question',
        showCancelButton: true,
        background: 'radial-gradient(circle at center, #a83d3e 0%, #510708 100%)',
        color: '#ffffff',
        confirmButtonColor: '#a83d3e',
        cancelButtonColor: 'rgba(255,255,255,0.2)',
        confirmButtonText: 'Yes, Publish!',
        cancelButtonText: 'Review'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('hiddenSubmit').click();
        }
    });
}