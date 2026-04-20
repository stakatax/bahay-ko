const images = [
    "Assets/images/college.jpg",
    "Assets/images/seniorhigh.jpg",
    "Assets/images/juniorhigh.jpg",
    "Assets/images/elementary.jpg"
];

let index = 0;
const heroImg = document.getElementById('heroImg');

setInterval(() => {
    index = (index + 1) % images.length;
    heroImg.src = images[index];
}, 5000);
