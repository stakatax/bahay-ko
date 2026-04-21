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