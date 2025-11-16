// assets/js/app.js
// Enhanced client-side password strength feedback with color and progress bar

document.addEventListener("DOMContentLoaded", function() {
    var pw = document.getElementById("password");
    var pwStrength = document.getElementById("pw-strength");

    // Create progress bar if not present
    var pwBar = document.getElementById("pw-strength-bar");
    if (!pwBar && pwStrength) {
        pwBar = document.createElement("div");
        pwBar.id = "pw-strength-bar";
        pwBar.style.height = "6px";
        pwBar.style.width = "100%";
        pwBar.style.background = "#e0e0e0";
        pwBar.style.borderRadius = "3px";
        pwBar.style.marginTop = "4px";
        pwBar.innerHTML = '<div style="height:100%;width:0%;background:#ccc;border-radius:3px;transition:width 0.3s;"></div>';
        pwStrength.parentNode.insertBefore(pwBar, pwStrength.nextSibling);
    }

    var levels = [
        {text: "Very weak", color: "#dc3545", bar: "#dc3545"},   // red
        {text: "Weak",      color: "#fd7e14", bar: "#fd7e14"},   // orange
        {text: "Okay",      color: "#ffc107", bar: "#ffc107"},   // yellow
        {text: "Good",      color: "#0d6efd", bar: "#0d6efd"},   // blue
        {text: "Strong",    color: "#198754", bar: "#198754"}    // green
    ];

    if (pw && pwStrength && pwBar) {
        pw.addEventListener("input", function() {
            var val = pw.value;
            var score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            var level = levels[score];

            pwStrength.textContent = "Strength: " + level.text;
            pwStrength.style.color = level.color;
            pwStrength.style.fontWeight = "bold";

            // Progress bar
            var barFill = pwBar.firstChild;
            barFill.style.width = ((score + 1) * 20) + "%";
            barFill.style.background = level.bar;
        });
    }
});