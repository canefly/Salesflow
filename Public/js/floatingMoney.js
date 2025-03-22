
// floatingMoney.js

document.addEventListener("DOMContentLoaded", () => {
    const container = document.createElement("div");
    container.className = "floating-container";
    document.body.appendChild(container);

    const symbols = ["$", "●", "•", "¤"];
    const total = 20;

    for (let i = 0; i < total; i++) {
        const el = document.createElement("span");
        el.className = "float-item";
        el.innerText = symbols[Math.floor(Math.random() * symbols.length)];
        el.style.left = Math.random() * 100 + "vw";
        el.style.animationDuration = 5 + Math.random() * 5 + "s";
        el.style.fontSize = 12 + Math.random() * 24 + "px";
        el.style.opacity = 0.1 + Math.random() * 0.3;
        container.appendChild(el);
    }
});
