let currentSlide = 0;
let slideInterval;
const SLIDE_DURATION = 5000; // 5 seconds

// Start the timer when the page loads
document.addEventListener('DOMContentLoaded', () => {
    startAutoSlide();
});

function startAutoSlide() {
    // Clear any existing interval to prevent multiple timers running
    clearInterval(slideInterval);
    
    slideInterval = setInterval(() => {
        moveSlide(1);
    }, SLIDE_DURATION);
}

function moveSlide(direction) {
    const slides = document.querySelectorAll('.slide');
    if (slides.length === 0) return;

    // Remove active class from current
    slides[currentSlide].classList.remove('active');

    // Calculate next index
    currentSlide += direction;

    // Loop back logic
    if (currentSlide >= slides.length) {
        currentSlide = 0;
    } else if (currentSlide < 0) {
        currentSlide = slides.length - 1;
    }

    // Add active class to new slide
    slides[currentSlide].classList.add('active');

    // IMPORTANT: Reset the timer so it starts counting from 5s again 
    // after a manual button click
    startAutoSlide();
}

// File types the browser can render inline inside an iframe
const PREVIEWABLE_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

function openPreview(fileName, fileType) {
    const ext = fileType.toLowerCase().trim();
    const fileUrl = './Assets/uploads/' + fileName;

    // Strip timestamp prefix for a cleaner display name
    const displayName = fileName.replace(/^\d+_/, '');

    // Update header
    document.getElementById('previewFileName').textContent = displayName;
    document.getElementById('previewBadge').textContent = ext.toUpperCase();
    document.getElementById('previewDownload').href = fileUrl;

    const frame    = document.getElementById('previewFrame');
    const fallback = document.getElementById('previewFallback');

    if (PREVIEWABLE_TYPES.includes(ext)) {
        frame.src = fileUrl;
        frame.style.display = 'block';
        fallback.style.display = 'none';
    } else {
        frame.src = '';
        frame.style.display = 'none';
        document.getElementById('fallbackDownload').href = fileUrl;
        fallback.style.display = 'flex';
    }

    document.getElementById('previewModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePreview() {
    const modal = document.getElementById('previewModal');
    const frame = document.getElementById('previewFrame');

    modal.classList.remove('active');
    frame.src = '';                   // Stop loading / release memory
    document.body.style.overflow = '';
}

// Close when clicking the dark backdrop (not the container itself)
function handleOverlayClick(e) {
    if (e.target === document.getElementById('previewModal')) {
        closePreview();
    }
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePreview();
});