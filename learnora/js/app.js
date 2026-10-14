console.log("app.js loaded");

// --- Registration Logic ---
const registerForm = document.getElementById("registerForm");

if (registerForm) {
    registerForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append("fullname", document.getElementById("fullname").value);
        formData.append("email", document.getElementById("email").value);
        formData.append("password", document.getElementById("password").value);

        try {
            const response = await fetch("api/register.php", {
                method: "POST",
                body: formData
            });

            const result = await response.text();
            alert(result);
            if (result.trim() === "success") {
                window.location.href = "login.html";
            }
        } catch (error) {
            console.error("Error:", error);
        }
    });
}

// --- Login Logic ---
const loginForm = document.getElementById("loginForm");

if (loginForm) {
    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append("email", document.getElementById("email").value);
        formData.append("password", document.getElementById("password").value);

        try {
            const response = await fetch("api/login.php", {
                method: "POST",
                body: formData
            });

            const result = await response.text();

            if (result.trim() === "success") {
                window.location.href = "dashboard.html";
            } else {
                alert(result);
            }
        } catch (error) {
            console.error("Error:", error);
        }
    });
}