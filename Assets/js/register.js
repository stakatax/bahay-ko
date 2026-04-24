function confirmRegistration() {
    const form = document.getElementById('registrationForm');
    
    // Check if browser requirements are met (required fields)
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Modern Confirmation Pop-up
    Swal.fire({
        title: 'Submit Registration?',
        text: "Please make sure all your information is correct.",
        icon: 'question',
        showCancelButton: true,
        background: 'radial-gradient(circle at center, #a83d3e 0%, #510708 100%)',
        color: '#ffffff',
        confirmButtonColor: '#a83d3e',
        cancelButtonColor: 'rgba(255,255,255,0.2)',
        confirmButtonText: 'Yes, Register!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Clicks the hidden submit button to send the form data
            document.getElementById('hiddenSubmit').click();
        }
    });
}