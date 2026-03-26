document.addEventListener("keydown", (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === "k") {
        const tag = document.activeElement?.tagName;
        if (tag === "INPUT" || tag === "TEXTAREA" || tag === "SELECT") {
            return;
        }
        e.preventDefault();
        window.dispatchEvent(
            new CustomEvent("open-game-search", { bubbles: true }),
        );
    }
});
