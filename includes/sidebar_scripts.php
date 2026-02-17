<script src="../js/future-ui.js"></script>
<script>
(function () {
    function dispatchFutureUiInit() {
        document.dispatchEvent(new Event("future-ui:init"));
    }

    function applyDashboardBody() {
        const body = document.body;
        if (!body) {
            return false;
        }

        body.classList.add("future-page", "future-dashboard");
        if (!body.dataset.theme) {
            body.dataset.theme = "nebula";
        }
        dispatchFutureUiInit();

        return true;
    }

    if (!applyDashboardBody()) {
        document.addEventListener("DOMContentLoaded", applyDashboardBody, { once: true });
    }
})();

function ensureSidebarControls() {
    const body = document.body;
    if (!body) {
        return;
    }

    let overlay = document.getElementById("sidebarOverlay");
    if (!overlay) {
        overlay = document.createElement("div");
        overlay.id = "sidebarOverlay";
        overlay.className = "sidebar-overlay";
        body.insertBefore(overlay, body.firstChild);
    }

    let toggleBtn = document.getElementById("sidebarToggleBtn");
    if (!toggleBtn) {
        toggleBtn = document.createElement("button");
        toggleBtn.type = "button";
        toggleBtn.id = "sidebarToggleBtn";
        toggleBtn.className = "sidebar-toggle";
        toggleBtn.setAttribute("aria-label", "Toggle sidebar");
        toggleBtn.setAttribute("onclick", "toggleSidebar()");
        toggleBtn.innerHTML = '<i class="bi bi-list"></i>';
        body.insertBefore(toggleBtn, body.firstChild);
    }
}

if (document.body) {
    ensureSidebarControls();
} else {
    document.addEventListener("DOMContentLoaded", ensureSidebarControls, { once: true });
}

function toggleSidebar() {
    const sidebar = document.getElementById("nexgenSidebar");
    const overlay = document.getElementById("sidebarOverlay");

    if (!sidebar) {
        return;
    }

    const willOpen = !sidebar.classList.contains("show");
    sidebar.setAttribute("aria-hidden", willOpen ? "false" : "true");
    sidebar.classList.toggle("show", willOpen);
    if (overlay) {
        overlay.classList.toggle("show", willOpen);
    }
}

function closeSidebar() {
    const sidebar = document.getElementById("nexgenSidebar");
    const overlay = document.getElementById("sidebarOverlay");

    if (sidebar) {
        sidebar.classList.remove("show");
        sidebar.setAttribute("aria-hidden", "true");
    }

    if (overlay) {
        overlay.classList.remove("show");
    }
}

function resolveModalTarget(trigger) {
    const selector = trigger.getAttribute("data-bs-target") || trigger.getAttribute("href");
    if (!selector || selector === "#") {
        return null;
    }

    try {
        return document.querySelector(selector);
    } catch (error) {
        return null;
    }
}

function cleanupModalArtifacts() {
    if (document.querySelector(".modal.show")) {
        return;
    }

    document.querySelectorAll(".modal-backdrop").forEach(function(backdrop) {
        backdrop.remove();
    });

    if (document.body) {
        document.body.classList.remove("modal-open");
        document.body.style.removeProperty("padding-right");
    }
}

document.addEventListener("click", function(event) {
    if (!(event.target instanceof Element)) {
        return;
    }

    const toggleTrigger = event.target.closest("#sidebarToggleBtn");
    if (!toggleTrigger) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    toggleSidebar();
}, true);

document.addEventListener("click", function(event) {
    const sidebar = document.getElementById("nexgenSidebar");
    const toggleBtn = document.getElementById("sidebarToggleBtn");
    const overlay = document.getElementById("sidebarOverlay");

    if (!sidebar || !toggleBtn) {
        return;
    }

    if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
        if (window.innerWidth <= 768 && sidebar.classList.contains("show")) {
            closeSidebar();
        }
    }
});

document.addEventListener("click", function(event) {
    if (!(event.target instanceof Element) || event.target.id !== "sidebarOverlay") {
        return;
    }

    event.target.classList.remove("show");
    closeSidebar();
});

document.querySelectorAll(".nexgen-sidebar-menu a").forEach(function(link) {
    link.addEventListener("click", function() {
        const sidebar = document.getElementById("nexgenSidebar");
        if (sidebar && window.innerWidth <= 768) {
            closeSidebar();
        }
    });
});

window.addEventListener("resize", function() {
    const sidebar = document.getElementById("nexgenSidebar");
    const overlay = document.getElementById("sidebarOverlay");

    if (!sidebar || !overlay) {
        return;
    }

    if (window.innerWidth > 768) {
        closeSidebar();
        sidebar.setAttribute("aria-hidden", "false");
    }
});

document.addEventListener("show.bs.modal", function() {
    closeSidebar();
});

document.addEventListener("hidden.bs.modal", function() {
    cleanupModalArtifacts();
});

document.addEventListener("click", function(event) {
    if (!(event.target instanceof Element)) {
        return;
    }

    const trigger = event.target.closest('[data-bs-toggle="modal"]');
    if (!trigger) {
        return;
    }

    closeSidebar();

    if (!window.bootstrap || !window.bootstrap.Modal) {
        return;
    }

    const modalEl = resolveModalTarget(trigger);
    if (!modalEl) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    cleanupModalArtifacts();
    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}, true);
</script>
