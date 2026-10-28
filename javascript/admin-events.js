const eventTable = document.querySelector(".admin-event-table[data-event-api]");

if (eventTable) {
    const apiUrl = eventTable.dataset.eventApi;
    const eventForm = document.querySelector(".admin-event-form");
    const eventRecords = document.querySelector("#adminEventRecords");
    const eventMessage = document.querySelector("#adminEventMessage");
    const posterPreview = document.querySelector("#adminEventPosterPreview");
    const editHeading = document.querySelector("#event-form h3");
    const submitButton = eventForm.querySelector('button[type="submit"]');
    const csrfToken = eventForm.querySelector('[name="csrf_token"]').value;
    const eventFields = ["title", "event_date", "event_time", "venue", "category", "organizer", "description"];
    let events = [];

    const showMessage = (message, type = "success") => {
        eventMessage.textContent = message;
        eventMessage.hidden = !message;
        eventMessage.classList.remove("success", "error");
        if (message) {
            eventMessage.classList.add(type);
        }
        eventMessage.setAttribute("role", type === "error" ? "alert" : "status");
    };

    const makeCell = (text) => {
        const cell = document.createElement("td");
        cell.textContent = text;
        return cell;
    };

    const makeActionCell = (eventItem) => {
        const cell = document.createElement("td");
        const actions = document.createElement("div");
        actions.className = "admin-row-actions";

        const editButton = document.createElement("button");
        editButton.type = "button";
        editButton.className = "admin-edit-link admin-event-action";
        editButton.dataset.eventEdit = String(eventItem.id);
        editButton.textContent = "Edit";
        actions.append(editButton);

        const deleteButton = document.createElement("button");
        deleteButton.type = "button";
        deleteButton.className = "admin-delete-button";
        deleteButton.dataset.eventDelete = String(eventItem.id);
        deleteButton.textContent = "Delete";
        actions.append(deleteButton);
        cell.append(actions);
        return cell;
    };

    const renderEvents = () => {
        eventRecords.replaceChildren();

        if (events.length === 0) {
            const row = document.createElement("tr");
            const cell = document.createElement("td");
            cell.colSpan = 7;
            cell.textContent = "No events have been added yet.";
            row.append(cell);
            eventRecords.append(row);
        } else {
            events.forEach((eventItem) => {
                const row = document.createElement("tr");
                const posterCell = document.createElement("td");
                if (eventItem.image) {
                    const poster = document.createElement("img");
                    poster.className = "admin-event-thumbnail";
                    poster.src = eventItem.image;
                    poster.alt = "";
                    posterCell.append(poster);
                } else {
                    const noPoster = document.createElement("span");
                    noPoster.className = "admin-event-no-poster";
                    noPoster.textContent = "No poster";
                    posterCell.append(noPoster);
                }

                const titleCell = makeCell("");
                const title = document.createElement("strong");
                title.textContent = eventItem.title;
                const description = document.createElement("span");
                description.className = "admin-event-description-preview";
                description.textContent = eventItem.description;
                titleCell.append(title, description);

                row.append(
                    posterCell,
                    titleCell,
                    makeCell(`${eventItem.date} ${eventItem.time}`),
                    makeCell(eventItem.location),
                    makeCell(eventItem.category),
                    makeCell(eventItem.organizer || ""),
                    makeActionCell(eventItem)
                );
                eventRecords.append(row);
            });
        }

        const count = events.length;
        const countLabel = document.querySelector(".admin-records-heading p");
        if (countLabel) {
            countLabel.textContent = `${count} event${count === 1 ? "" : "s"}`;
        }
    };

    const requestEvents = async () => {
        const response = await fetch(apiUrl, {
            headers: { Accept: "application/json" },
            credentials: "same-origin"
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || "Events could not be loaded.");
        }
        if (!Array.isArray(result)) {
            throw new Error("The event API returned an invalid event list.");
        }
        events = result;
        renderEvents();
    };

    const resetEventForm = () => {
        eventForm.reset();
        eventForm.elements.action.value = "add";
        eventForm.elements.event_id.value = "";
        eventHeading.textContent = "Add an event";
        submitButton.textContent = "Add event";
        posterPreview.hidden = true;
        posterPreview.querySelector("img").removeAttribute("src");
    };

    const eventHeading = editHeading;

    eventForm.addEventListener("submit", async (submitEvent) => {
        submitEvent.preventDefault();
        const formData = new FormData(eventForm);
        const action = formData.get("action");
        submitButton.disabled = true;
        showMessage(action === "update" ? "Saving event changes..." : "Adding event...");

        try {
            const response = await fetch(apiUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-Token": csrfToken
                },
                body: formData,
                credentials: "same-origin"
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || "The event could not be saved.");
            }

            resetEventForm();
            await requestEvents();
            showMessage(result.message);
        } catch (error) {
            showMessage(error.message || "The event could not be saved.", "error");
            console.error("StudentHub event save failed:", error);
        } finally {
            submitButton.disabled = false;
        }
    });

    eventRecords.addEventListener("click", async (clickEvent) => {
        const editButton = clickEvent.target.closest("[data-event-edit]");
        if (editButton) {
            const selectedEvent = events.find((eventItem) => String(eventItem.id) === editButton.dataset.eventEdit);
            if (!selectedEvent) {
                showMessage("The selected event could not be found.", "error");
                return;
            }

            eventFields.forEach((field) => {
                eventForm.elements[field].value = field === "event_date"
                    ? selectedEvent.date
                    : field === "event_time"
                        ? selectedEvent.time
                        : field === "venue"
                            ? selectedEvent.location
                            : selectedEvent[field] || "";
            });
            eventForm.elements.action.value = "update";
            eventForm.elements.event_id.value = selectedEvent.id;
            eventForm.elements.remove_poster.checked = false;
            eventHeading.textContent = "Edit event";
            submitButton.textContent = "Save changes";
            if (selectedEvent.image) {
                posterPreview.querySelector("img").src = selectedEvent.image;
                posterPreview.hidden = false;
            } else {
                posterPreview.hidden = true;
                posterPreview.querySelector("img").removeAttribute("src");
            }
            document.querySelector("#event-form").scrollIntoView({ behavior: "smooth" });
            eventForm.elements.title.focus();
            return;
        }

        const deleteButton = clickEvent.target.closest("[data-event-delete]");
        if (!deleteButton) {
            return;
        }
        if (!window.confirm("Delete this event and its registrations? This cannot be undone.")) {
            return;
        }

        deleteButton.disabled = true;
        showMessage("Deleting event...");
        try {
            const response = await fetch(apiUrl, {
                method: "DELETE",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken
                },
                body: JSON.stringify({ event_id: deleteButton.dataset.eventDelete }),
                credentials: "same-origin"
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || "The event could not be deleted.");
            }
            if (eventForm.elements.event_id.value === deleteButton.dataset.eventDelete) {
                resetEventForm();
            }
            await requestEvents();
            showMessage(result.message);
        } catch (error) {
            showMessage(error.message || "The event could not be deleted.", "error");
            console.error("StudentHub event deletion failed:", error);
        } finally {
            deleteButton.disabled = false;
        }
    });

    requestEvents().catch((error) => {
        showMessage(error.message || "Events could not be loaded.", "error");
        console.error("StudentHub event loading failed:", error);
    });
}
