document.addEventListener('DOMContentLoaded', () => {
    const palettes = [
        ["#6366F1", "#22D3EE", "#A855F7"],
        ["#0EA5E9", "#22C55E", "#A78BFA"],
        ["#F97316", "#EF4444", "#EC4899"],
        ["#06B6D4", "#3B82F6", "#9333EA"],
        ["#22C55E", "#84CC16", "#14B8A6"],
        // ["#1E293B", "#334155", "#6366F1"],
        ["#F472B6", "#A78BFA", "#60A5FA"],
    ];

    function setPalette() {
        const palette = palettes[Math.floor(Math.random() * palettes.length)];

        document.documentElement.style.setProperty("--c1", palette[0]);
        document.documentElement.style.setProperty("--c2", palette[1]);
        document.documentElement.style.setProperty("--c3", palette[2]);
    }

    document.getElementById("app-logo").addEventListener("click", () => {
        setPalette();
    })

    setPalette();

    // Storage request
    const storageRequestButton = document.getElementById("storage-request-button");
    storageRequestButton.addEventListener("click", () => {
        fetch('/api/request/create', {
            method: "POST",
            credentials: "same-origin",
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                value: 1,
                unit: 'GB'
            })
        }).then(response => {})
    });
});
