<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <script src="https://cdn.tailwindcss.com"></script>

  <title>Fullscreen Layout</title>

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
    }

    .fade-slide {
      animation: fadeSlide 0.4s ease;
    }

    @keyframes fadeSlide {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>

<body class="m-0">

  <!-- FULLSCREEN BACKGROUND -->
  <div class="w-full h-screen flex items-center justify-center
              bg-gradient-to-r from-[#3b0000] via-[#7a0c0c] to-[#d27a3c]">

    <!-- CONTENT WRAPPER (keeps your original layout size) -->
    <div class="w-[1100px]">

      <!-- NAVBAR -->
      <div class="flex justify-center mb-10">
        <div class="flex items-center gap-6 px-6 py-3 rounded-full
                    bg-white/20 backdrop-blur-md shadow-md text-white text-sm">

          <img src="https://via.placeholder.com/35" class="rounded-full">

          <button class="nav-btn font-semibold text-yellow-300" data-tab="philosophy">PHILOSOPHY</button>
          <button class="nav-btn" data-tab="vision">VISION</button>
          <button class="nav-btn" data-tab="mission">MISSION</button>
          <button class="nav-btn" data-tab="core">COREVALUES</button>
          <button class="nav-btn" data-tab="history">HISTORY OF SCHOOL</button>
          <button class="nav-btn" data-tab="contact">CONTACT</button>
          <button class="nav-btn" data-tab="login">LOGIN</button>

        </div>
      </div>

      <!-- TITLE -->
      <h1 id="title"
          class="text-center text-5xl font-bold text-yellow-300 tracking-wide mb-6">
          PHILOSOPHY
      </h1>

      <!-- IMAGE -->
      <div class="flex justify-center mb-6">
        <img id="image"
          src="https://images.unsplash.com/photo-1596495578065-6e0763fa1178"
          class="w-[700px] h-[260px] object-cover rounded-xl shadow-lg">
      </div>

      <!-- CONTENT -->
      <p id="content"
        class="text-center text-white text-[15px] leading-relaxed px-10 fade-slide">

        Our Lady of the Sacred Heart College of Guimba, Inc. believes that education shall lead young men and women into human fullness combining their life and work skills with their sacred love for all persons as desired by the oneness of the heart of Jesus and Mary. The school gracefully dedicates its existence in cultivating student-centered learning for the holistic development of a person's intellect and physical well being, and social, and spiritual life.

      </p>

    </div>
  </div>

  <!-- SCRIPT (unchanged) -->
  <script>
    const tabs = {
      philosophy: {
        title: "PHILOSOPHY",
        content: `Our Lady of the Sacred Heart College of Guimba, Inc. believes that education shall lead young men and women into human fullness combining their life and work skills with their sacred love for all persons as desired by the oneness of the heart of Jesus and Mary.`,
        image: "https://images.unsplash.com/photo-1596495578065-6e0763fa1178"
      },
      vision: {
        title: "VISION",
        content: `A premier educational institution committed to excellence.`,
        image: "https://images.unsplash.com/photo-1503676260728-1c00da094a0b"
      }
    };

    const title = document.getElementById("title");
    const content = document.getElementById("content");
    const image = document.getElementById("image");

    document.querySelectorAll(".nav-btn").forEach(btn => {
      btn.addEventListener("click", () => {

        document.querySelectorAll(".nav-btn").forEach(b => {
          b.classList.remove("text-yellow-300", "font-semibold");
        });
        btn.classList.add("text-yellow-300", "font-semibold");

        const tab = tabs[btn.dataset.tab];
        if (!tab) return;

        content.classList.remove("fade-slide");
        void content.offsetWidth;
        content.classList.add("fade-slide");

        title.textContent = tab.title;
        content.textContent = tab.content;
        image.src = tab.image;
      });
    });
  </script>

</body>
</html>