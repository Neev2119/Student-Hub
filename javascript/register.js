const registerForm = document.querySelector("#registerForm");

if (registerForm) {
    const studentId = document.querySelector("#studentId");
    const fullName = document.querySelector("#fullName");
    const email = document.querySelector("#email");
    const phone = document.querySelector("#phone");
    const password = document.querySelector("#password");
    const confirmPassword = document.querySelector("#confirmPassword");
    const genderInputs = document.querySelectorAll("input[name='gender']");
    const registerMessage = document.querySelector("#registerMessage");

    const patterns = {
        studentId: /^[A-Za-z0-9-]{4,20}$/,
        name: /^[A-Za-z]+(?:[ '-][A-Za-z]+)+$/,
        email: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,
        phone: /^[0-9]{10}$/,
        password: /^(?=.*[A-Za-z])(?=.*[0-9])(?=.*[@$!%*?&]).{8,}$/
    };

    const validateField = (field, pattern, message) => {
        if (!field) {
            return;
        }

        if (field === email) {
            field.value = field.value.trim();
        }

        field.setCustomValidity(
            field.value.trim() && pattern.test(field.value.trim()) ? "" : message
        );
    };

    const validateGender = () => {
        const selectedGender = [...genderInputs].some((input) => input.checked);
        genderInputs.forEach((input) => {
            input.setCustomValidity(selectedGender ? "" : "Select your gender.");
        });
    };

    const validatePasswords = () => {
        if (!confirmPassword) {
            return;
        }

        if (!confirmPassword.value) {
            confirmPassword.setCustomValidity("Confirm your password.");
        } else if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity("Passwords do not match.");
        } else {
            confirmPassword.setCustomValidity("");
        }
    };

    const runValidation = () => {
        validateField(studentId, patterns.studentId, "Enter a valid student ID.");
        validateField(fullName, patterns.name, "Enter your first and last name.");
        validateField(email, patterns.email, "Enter a valid email address.");
        validateField(phone, patterns.phone, "Enter a 10-digit mobile number.");
        validateField(
            password,
            patterns.password,
            "Use at least 8 characters with a letter, number, and special character."
        );
        validatePasswords();
        validateGender();
    };

    [studentId, fullName, email, phone].forEach((field) => {
        field?.addEventListener("input", () => {
            if (field === studentId) {
                validateField(studentId, patterns.studentId, "Enter a valid student ID.");
            }
            if (field === fullName) {
                validateField(fullName, patterns.name, "Enter your first and last name.");
            }
            if (field === email) {
                validateField(email, patterns.email, "Enter a valid email address.");
            }
            if (field === phone) {
                validateField(phone, patterns.phone, "Enter a 10-digit mobile number.");
            }
        });
    });

    password?.addEventListener("input", () => {
        validateField(
            password,
            patterns.password,
            "Use at least 8 characters with a letter, number, and special character."
        );
        validatePasswords();
    });

    confirmPassword?.addEventListener("input", validatePasswords);
    genderInputs.forEach((input) => {
        input.addEventListener("change", validateGender);
    });

    registerForm.addEventListener("submit", (event) => {
        runValidation();

        if (!registerForm.checkValidity()) {
            event.preventDefault();
            registerMessage.textContent = "Please correct the highlighted fields.";
            registerMessage.className = "form-message error";
            registerForm.reportValidity();
            return;
        }

        registerMessage.textContent = "Submitting registration...";
        registerMessage.className = "form-message success";
    });
}