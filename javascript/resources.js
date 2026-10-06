const resourceList = document.querySelector("#resourceList");

if (resourceList) {
    const resourceConfigs = {
        events: {
            label: "events",
            endpoint: "../data/events.json",
            title: (item) => item.title,
            category: (item) => item.category,
            searchable: (item) => [item.title, item.category, item.location, item.description],
            sortDate: (item) => item.date,
            render: (item) => `
                <article class="resource-card">
                    <div class="resource-card-top"><span class="resource-tag">${item.category}</span><time datetime="${item.date}">${formatDate(item.date)}</time></div>
                    <h3>${item.title}</h3>
                    <p>${item.description}</p>
                    <span class="resource-meta">${item.location}</span>
                </article>`
        },
        students: {
            label: "students",
            endpoint: "../data/students.json",
            title: (item) => item.name,
            category: (item) => item.department,
            searchable: (item) => [item.name, item.course, item.year, item.department, item.email, ...item.interests],
            render: (item) => `
                <article class="resource-card profile-card">
                    <div class="profile-initials" aria-hidden="true">${getInitials(item.name)}</div>
                    <div><span class="resource-tag">${item.department}</span><h3>${item.name}</h3></div>
                    <p>${item.course} &middot; ${item.year}</p>
                    <a class="resource-meta" href="mailto:${item.email}">${item.email}</a>
                    <div class="interest-list">${item.interests.map((interest) => `<span>${interest}</span>`).join("")}</div>
                </article>`
        },
        faqs: {
            label: "FAQs",
            endpoint: "../data/faqs.json",
            title: (item) => item.question,
            category: (item) => item.category,
            searchable: (item) => [item.question, item.category, item.answer],
            render: (item) => `
                <details class="resource-card faq-resource-card">
                    <summary><span class="resource-tag">${item.category}</span><strong>${item.question}</strong></summary>
                    <p>${item.answer}</p>
                </details>`
        }
    };

    const state = {
        resource: "events",
        items: [],
        filteredItems: [],
        page: 1,
        pageSize: 6,
        search: "",
        category: "all",
        sort: "default"
    };

    const searchInput = document.querySelector("#resourceSearch");
    const filterSelect = document.querySelector("#resourceFilter");
    const sortSelect = document.querySelector("#resourceSort");
    const emptyState = document.querySelector("#resourceEmpty");
    const pagination = document.querySelector("#resourcePagination");
    const count = document.querySelector("#resourceCount");
    const status = document.querySelector("#resourceStatus");

    const formatDate = (date) => new Intl.DateTimeFormat("en", {
        day: "numeric",
        month: "short",
        year: "numeric"
    }).format(new Date(`${date}T00:00:00`));

    const getInitials = (name) => name.split(" ").map((part) => part[0]).join("").slice(0, 2);
    const getPriorityClass = (priority) => priority.toLowerCase().replaceAll(" ", "-");

    const updateCategories = () => {
        const categories = [...new Set(state.items.map((item) => resourceConfigs[state.resource].category(item)))].sort();
        filterSelect.innerHTML = `<option value="all">All categories</option>${categories.map((category) => `<option value="${category}">${category}</option>`).join("")}`;
        filterSelect.value = categories.includes(state.category) ? state.category : "all";
        state.category = filterSelect.value;
    };

    const updateSortOptions = () => {
        const hasDates = Boolean(resourceConfigs[state.resource].sortDate);
        sortSelect.querySelectorAll("option").forEach((option) => {
            const isDateSort = option.value.startsWith("date-");
            option.disabled = isDateSort && !hasDates;
        });
    };

    const applyFilters = () => {
        const config = resourceConfigs[state.resource];
        const searchTerm = state.search.toLowerCase();
        state.filteredItems = state.items.filter((item) => {
            const matchesSearch = !searchTerm || config.searchable(item).join(" ").toLowerCase().includes(searchTerm);
            const matchesCategory = state.category === "all" || config.category(item) === state.category;
            return matchesSearch && matchesCategory;
        });

        if (state.sort !== "default") {
            const [property, direction] = state.sort.split("-");
            state.filteredItems.sort((first, second) => {
                const firstValue = property === "date" ? config.sortDate?.(first) || "" : config.title(first).toLowerCase();
                const secondValue = property === "date" ? config.sortDate?.(second) || "" : config.title(second).toLowerCase();
                return direction === "asc" ? firstValue.localeCompare(secondValue) : secondValue.localeCompare(firstValue);
            });
        }

        state.page = Math.min(state.page, Math.max(1, Math.ceil(state.filteredItems.length / state.pageSize)));
        renderItems();
    };

    const renderItems = () => {
        const config = resourceConfigs[state.resource];
        const start = (state.page - 1) * state.pageSize;
        const visibleItems = state.filteredItems.slice(start, start + state.pageSize);
        resourceList.innerHTML = visibleItems.map(config.render).join("");
        resourceList.setAttribute("aria-busy", "false");
        emptyState.hidden = visibleItems.length > 0;
        count.textContent = `${state.filteredItems.length} ${config.label}`;
        status.textContent = state.filteredItems.length ? `Showing ${start + 1}-${Math.min(start + state.pageSize, state.filteredItems.length)} of ${state.filteredItems.length}` : "No matching results";
        renderPagination();
    };

    const renderPagination = () => {
        const pageCount = Math.ceil(state.filteredItems.length / state.pageSize);
        pagination.innerHTML = "";
        if (pageCount < 2) {
            return;
        }
        const buttons = [];
        for (let pageNumber = 1; pageNumber <= pageCount; pageNumber += 1) {
            buttons.push(`<button type="button" class="page-button${pageNumber === state.page ? " active" : ""}" data-page="${pageNumber}" aria-label="Go to page ${pageNumber}" aria-current="${pageNumber === state.page ? "page" : "false"}">${pageNumber}</button>`);
        }
        pagination.innerHTML = buttons.join("");
        pagination.querySelectorAll(".page-button").forEach((button) => {
            button.addEventListener("click", () => {
                state.page = Number(button.dataset.page);
                renderItems();
            });
        });
    };

    const loadResource = async (resourceName) => {
        const config = resourceConfigs[resourceName];
        state.resource = resourceName;
        state.page = 1;
        state.search = "";
        state.category = "all";
        state.sort = "default";
        searchInput.value = "";
        sortSelect.value = "default";
        updateSortOptions();
        resourceList.setAttribute("aria-busy", "true");
        resourceList.innerHTML = "<p class=\"resource-loading\">Loading resources...</p>";
        try {
            const response = await fetch(config.endpoint);
            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }
            state.items = await response.json();
            updateCategories();
            applyFilters();
        } catch (error) {
            resourceList.setAttribute("aria-busy", "false");
            resourceList.innerHTML = "<p class=\"resource-error\">Unable to load this resource list. Please try again later.</p>";
            count.textContent = "Resource unavailable";
            status.textContent = "Loading failed";
            console.error("StudentHub resource loading failed:", error);
        }
    };

    document.querySelectorAll(".resource-tab").forEach((tab) => {
        tab.addEventListener("click", () => {
            document.querySelectorAll(".resource-tab").forEach((otherTab) => {
                const isActive = otherTab === tab;
                otherTab.classList.toggle("active", isActive);
                otherTab.setAttribute("aria-selected", String(isActive));
            });
            loadResource(tab.dataset.resource);
        });
    });

    searchInput.addEventListener("input", () => {
        state.search = searchInput.value.trim();
        state.page = 1;
        applyFilters();
    });
    filterSelect.addEventListener("change", () => {
        state.category = filterSelect.value;
        state.page = 1;
        applyFilters();
    });
    sortSelect.addEventListener("change", () => {
        state.sort = sortSelect.value;
        state.page = 1;
        applyFilters();
    });

    loadResource(state.resource);
}
