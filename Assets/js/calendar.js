const container = document.querySelector('.event-container');
const modal = document.getElementById("eventModal");
const modalDate = document.getElementById("modalDate");
const addBtn = document.querySelector(".add-event-btn");

container.addEventListener('wheel', (e) => {
    if (e.deltaY !== 0) {
        e.preventDefault();
        container.scrollLeft += e.deltaY;
    }
});

let selectedDate = null

document.addEventListener("click", function (e) {

    const cell = e.target.closest(".day-cell");
    if (!cell) return;

    e.preventDefault();

    selectedDate = cell.dataset.date;

    document.querySelectorAll(".day-cell.selected")
        .forEach(el => el.classList.remove("selected"));

    cell.classList.add("selected");

    renderEvents(selectedDate);
});

function renderEvents(date) {

    const container = document.getElementById("eventList");
    const events = EVENTS[date] || [];

    let html = "";

    if (events.length === 0) {
        html = `
            <div class="event-mini-card">
                <h4>No Events</h4>
                <p>No scheduled events for this date.</p>
            </div>
        `;
    } else {
        events.forEach(ev => {
            html += `
                <div class="event-mini-card">
                    <h4>${ev.title}</h4>
                    <p>${ev.type}</p>
                    <small>${date}</small>
                </div>
            `;
        });
    }

    container.innerHTML = html;
}

/* OPEN MODAL */
function openModal() {
    if (!selectedDate) {
        console.log("Please select a date first.");
        return;
    }

    document.getElementById("modalDate").textContent = selectedDate;
    modal.style.display = "flex";
}

/* CLOSE MODAL */
function closeModal() {
    modal.style.display = "none";
}

/* CLOSE WHEN CLICKING OUTSIDE */
window.onclick = function (e) {
    if (e.target === modal) {
        closeModal();
    }
};

/* OPEN BUTTON */
if (addBtn) {
    addBtn.addEventListener("click", () => openModal());
}

function saveEvent() {
    const title = document.getElementById("eventTitle").value.trim();
    const type = document.getElementById("eventType").value;
    const date = document.getElementById("modalDate").textContent;

    if (!title) {
        alert("Please enter an event title.");
        return;
    }

    // prevent saving if no valid date selected
    if (!date || date === "Selected Date" || date === "Add New Event") {
        alert("Please select a date first.");
        return;
    }

    fetch("save_event.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            date: date,
            title: title,
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            closeModal();
            location.href =
                `?page=calendar&month=<?=$month?>&year=<?=$year?>&date=${selectedDate}#calendar`;
        } else {
            alert("Failed to save event.");
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Something went wrong.");
    });
}