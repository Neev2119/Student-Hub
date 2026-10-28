const loginForm = document.querySelector("#loginForm");

if (loginForm) {
    const emailField = document.querySelector("#email");
    const passwordField = document.querySelector("#password");


const validateEmail = () => {
    if (!emailField) return;

    emailField.value = emailField.value.trim();
    emailField.setCustomValidity("");

    if (!emailField.value) {
        emailField.setCustomValidity("Email is required.");
    } else if (!emailField.validity.valid) {
        emailField.setCustomValidity("Enter a valid email address.");
    }
};


    if (emailField) {
        emailField.addEventListener("input", validateEmail);
    }

    passwordField?.addEventListener("input", () => {
        passwordField.setCustomValidity("");
    });

    loginForm.addEventListener("submit", (event) => {
        validateEmail();

        if (!loginForm.checkValidity()) {
            event.preventDefault();
            loginForm.reportValidity();
            return;
        }

        if (passwordField && passwordField.value.trim().length < 8) {
            event.preventDefault();
            passwordField.setCustomValidity("Password must be at least 8 characters long.");
            loginForm.reportValidity();
        }
    });
}