const body = document.body;
const nav = document.querySelector(".top-nav");
const hero = document.querySelector(".hero");

const createButton = (className, label, text) => {
    const button = document.createElement("button");
    button.className = className;
    button.type = "button";
    button.setAttribute("aria-label", label);
    button.textContent = text;
    return button;
};

const setupNavigation = () => {
    if (!nav) {
        return;
    }

    if (hero) {
        hero.classList.add("has-nav");
        hero.append(nav);
    }

    const navigationLinks = [
        ["index.html", "Home"],
        ["dashboard.html", "Dashboard"],
        ["attendance.html", "Attendance"],
        ["assignments.html", "Assignments"],
        ["courses.htm", "Courses"],
        ["timetable.html", "Timetable"],
        ["faq.html", "FAQ"],
        ["resources.html", "Resources"],
        ["contact.html", "Contact"],
        ["feedback.html", "Feedback"]
    ];

    const currentPage = window.location.pathname.split("/").pop() || "index.html";
    nav.innerHTML = navigationLinks
        .map(([href, label]) => {
            const activeState = href === currentPage ? ' aria-current="page"' : "";
            return `<a href="${href}"${activeState}>${label}</a>`;
        })
        .join("");

    const menuToggle = createButton("menu-toggle", "Toggle navigation menu", "☰");
    const siteTools = document.createElement("div");
    const themeToggle = createButton("theme-toggle", "Switch to dark theme", "☾");
    const heroInner = document.querySelector(".hero-inner");
    const loginLink = heroInner?.querySelector(".btn");

    siteTools.className = "site-tools";
    siteTools.append(themeToggle);

    if (loginLink) {
        siteTools.append(loginLink);
    }

    nav.prepend(menuToggle);
    heroInner?.append(siteTools);
    menuToggle.setAttribute("aria-expanded", "false");

    const closeMenu = () => {
        nav.classList.remove("is-open");
        menuToggle.setAttribute("aria-expanded", "false");
        menuToggle.textContent = "☰";
    };

    menuToggle.addEventListener("click", () => {
        const isOpen = nav.classList.toggle("is-open");
        menuToggle.setAttribute("aria-expanded", String(isOpen));
        menuToggle.textContent = isOpen ? "×" : "☰";
    });

    nav.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", closeMenu);
    });

    const savedTheme = localStorage.getItem("studenthub-theme");
    const setTheme = (theme) => {
        const isDark = theme === "dark";
        body.dataset.theme = isDark ? "dark" : "light";
        themeToggle.textContent = isDark ? "☀" : "☾";
        themeToggle.setAttribute(
            "aria-label",
            isDark ? "Switch to light theme" : "Switch to dark theme"
        );
    };

    setTheme(savedTheme || "light");
    themeToggle.addEventListener("click", () => {
        const nextTheme = body.dataset.theme === "dark" ? "light" : "dark";
        localStorage.setItem("studenthub-theme", nextTheme);
        setTheme(nextTheme);
    });
};

const setupNotification = () => {
    if (document.querySelector(".notification-banner")) {
        return;
    }

    const banner = document.createElement("aside");
    const main = document.querySelector("main");
    const modalBackdrop = document.createElement("div");

    banner.className = "notification-banner";
    banner.setAttribute("role", "status");
    banner.innerHTML = `
        <p><strong>StudentHub update:</strong> Your weekly study snapshot is ready.</p>
        <button class="banner-action" type="button">View update</button>
        <button class="banner-dismiss" type="button" aria-label="Dismiss notification">×</button>
    `;

    modalBackdrop.className = "modal-backdrop";
    modalBackdrop.hidden = true;
    modalBackdrop.innerHTML = `
        <section class="modal" role="dialog" aria-modal="true" aria-labelledby="update-title">
            <div class="modal-header">
                <h2 id="update-title">Your weekly update</h2>
                <button class="modal-close" type="button" aria-label="Close update">×</button>
            </div>
            <p>Three assignments are waiting this week. Review your deadlines and check tomorrow's timetable before you start studying.</p>
            <a class="btn primary" href="assignments.html">Open assignments</a>
        </section>
    `;

    const closeModal = () => {
        modalBackdrop.hidden = true;
    };

    banner.querySelector(".banner-action").addEventListener("click", () => {
        modalBackdrop.hidden = false;
        modalBackdrop.querySelector(".modal-close").focus();
    });

    banner.querySelector(".banner-dismiss").addEventListener("click", () => banner.remove());
    modalBackdrop.querySelector(".modal-close").addEventListener("click", closeModal);
    modalBackdrop.addEventListener("click", (event) => {
        if (event.target === modalBackdrop) {
            closeModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !modalBackdrop.hidden) {
            closeModal();
        }
    });

    main?.before(banner);
    body.append(modalBackdrop);
};

const setupFaqAccordion = () => {
    const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach((item) => {
        item.addEventListener("toggle", () => {
            if (!item.open) {
                return;
            }

            faqItems.forEach((otherItem) => {
                if (otherItem !== item) {
                    otherItem.open = false;
                }
            });
        });
    });
};

const setupHomeSlider = () => {
    if (!document.querySelector(".home-page")) {
        return;
    }

    const slider = document.createElement("section");
    const main = document.querySelector("main");
    slider.className = "campus-slider";
    slider.setAttribute("aria-label", "Campus highlights");
    slider.innerHTML = `
        <article class="slide active">
            <img src="../images/campus2.png" alt="Students studying together on campus">
            <div class="slide-copy">
                <span class="slide-kicker">Campus life / 01</span>
                <h2>Find your focus</h2>
                <p>Make space for good ideas, useful conversations, and steady progress.</p>
            </div>
        </article>
        <article class="slide">
            <img src="../images/campus1.png" alt="Green campus surroundings">
            <div class="slide-copy">
                <span class="slide-kicker">Campus life / 02</span>
                <h2>Make campus yours</h2>
                <p>Discover welcoming places to learn between classes.</p>
            </div>
        </article>
        <div class="slider-controls">
            <button class="slider-control previous" type="button" aria-label="Previous campus highlight">&#8592;</button>
            <div class="slider-dots" aria-label="Choose campus highlight">
                <button class="slider-dot active" type="button" aria-label="Show campus highlight 1"></button>
                <button class="slider-dot" type="button" aria-label="Show campus highlight 2"></button>
            </div>
            <button class="slider-control next" type="button" aria-label="Next campus highlight">&#8594;</button>
        </div>
    `;

    const stats = document.querySelector(".home-stats");
    stats ? stats.before(slider) : main?.append(slider);

    const slides = slider.querySelectorAll(".slide");
    const dots = slider.querySelectorAll(".slider-dot");
    let currentSlide = 0;

    const showSlide = (index) => {
        currentSlide = (index + slides.length) % slides.length;
        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle("active", slideIndex === currentSlide);
        });

        dots.forEach((dot, dotIndex) => {
            const isCurrent = dotIndex === currentSlide;
            dot.classList.toggle("active", isCurrent);
            dot.setAttribute("aria-current", String(isCurrent));
        });
    };

    slider.querySelector(".previous").addEventListener("click", () => showSlide(currentSlide - 1));
    slider.querySelector(".next").addEventListener("click", () => showSlide(currentSlide + 1));
    dots.forEach((dot, dotIndex) => {
        dot.addEventListener("click", () => showSlide(dotIndex));
    });

    setInterval(() => showSlide(currentSlide + 1), 6000);
};

setupNavigation();
setupNotification();
setupFaqAccordion();
setupHomeSlider();
