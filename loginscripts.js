document.getElementById("loginForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;
    const message = document.getElementById("signInMessage");

    fetch("login_process.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = "dashboard.php";
        } else {
            message.style.display = "block";
            message.innerText = data.message;
        }
    })
    .catch(() => {
        message.style.display = "block";
        message.innerText = "Server error. Try again.";
    });
});
