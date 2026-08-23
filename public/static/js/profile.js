document.addEventListener("DOMContentLoaded", () => {
    // Gestion du level du password
    const lvl0 = document.getElementById("lvl-0");
    const lvl1 = document.getElementById("lvl-1");
    const lvl2 = document.getElementById("lvl-2");
    const lvl3 = document.getElementById("lvl-3");
    const lvl4 = document.getElementById("lvl-4");
    const progressbarLvl = [lvl0, lvl1, lvl2, lvl3, lvl4];

    function evaluatePassword(password) {
        if (!password) {
            return { score: 0, state: "Empty *" };
        }

        let score = 0;

        // Longueur
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;

        // Variété
        const hasLower = /[a-z]/.test(password);
        const hasUpper = /[A-Z]/.test(password);
        const hasDigit = /\d/.test(password);
        const hasSpecial = /[^a-zA-Z0-9]/.test(password);

        if (hasLower && hasUpper) score++;
        if (hasDigit) score++;
        if (hasSpecial) score++;

        score = Math.min(score, 5);

        const states = [
            "Empty *",
            "Very weak *",
            "Weak *",
            "Medium *",
            "Strong *",
            "Very strong *",
        ];

        return {
            score,
            state: states[score],
        };
    }

    function clearProgressBar() {
        progressbarLvl.forEach((lvl) => {
            const value = lvl.querySelector('.progressbar-value');
            value.style.width = '0';
        });
    }

    function setProgressBar(progress, color) {
        for (let i = 0; i < progress; i++) {
            const progressbarValue = progressbarLvl[i].querySelector('.progressbar-value');
            progressbarValue.style.width = '100%';

            if (progressbarValue.classList.contains('weak')) {
                progressbarValue.classList.remove('weak');
            }
            if (progressbarValue.classList.contains('middle')) {
                progressbarValue.classList.remove('middle');
            }
            if (progressbarValue.classList.contains('strong')) {
                progressbarValue.classList.remove('strong');
            }

            progressbarValue.classList.add(color);
        }
    }

    const passwordInput = document.getElementById("password");
    passwordInput.addEventListener("input", () => {
        const res = evaluatePassword(passwordInput.value);
        let color;
        if (res.score <= 2) {
            color = 'weak';
        } else if (res.score === 3) {
            color = 'middle';
        } else if (res.score > 3) {
            color = 'strong';
        }

        clearProgressBar();
        setProgressBar(res.score, color);

        const securityInfo = document.getElementById("security-info");
        if (securityInfo.classList.contains('weak')) {
            securityInfo.classList.remove('weak');
        }
        if (securityInfo.classList.contains('middle')) {
            securityInfo.classList.remove('middle');
        }
        if (securityInfo.classList.contains('strong')) {
            securityInfo.classList.remove('strong');
        }
        securityInfo.classList.add(color);
        securityInfo.innerText = res.state;
    });
    passwordInput.dispatchEvent(new Event('input'));

    // Repeat password
    const confirmPasswordInput = document.getElementById("confirm-password");
    confirmPasswordInput.addEventListener("input", checkBothPassword)
    passwordInput.addEventListener("input", checkBothPassword)

    function checkBothPassword() {
        const passwordMessage = document.getElementById("password-message");
        if (passwordInput.value !== confirmPasswordInput.value) {
            if (passwordMessage.classList.contains('hide')) {
                passwordMessage.classList.remove('hide');
            }
        } else {
            if (!passwordMessage.classList.contains('hide')) {
                passwordMessage.classList.add('hide');
            }
        }

        return passwordInput.value === confirmPasswordInput.value
    }
    checkBothPassword();

    // Gestion du select
    const customSelect = document.getElementById("select");
    const customSelectDown = customSelect.querySelector('img');
    const customSelectChoice = document.getElementById("select-choice");
    const customSelectChoices = document.getElementById('select-choices');
    const select = document.querySelector('select');

    Array.from(select.options).forEach((option) => {
        const choice = document.createElement("span");
        choice.innerText = option.innerText;
        choice.dataset.value = option.value;
        choice.classList.add('choice');
        customSelectChoices.appendChild(choice);

        choice.addEventListener('click', () => {
            customSelectChoices.querySelectorAll('.choice').forEach((option) => {
                if (option.classList.contains('selected')) {
                    option.classList.remove('selected');
                }
            })

            choice.classList.add('selected');
            select.selectedIndex = parseInt(choice.dataset.value);
            customSelectChoice.innerText = option.innerText;

            if (!customSelectChoices.classList.contains('hide')){
                customSelectChoices.classList.add('hide');
            }
        });
    });

    customSelectDown.addEventListener('click', () => {
        if (!customSelectChoices.classList.contains('hide')) {
            customSelectChoices.classList.add('hide');
        } else if (customSelectChoices.classList.contains('hide')) {
            customSelectChoices.classList.remove('hide');
        }
    });

    const choice = customSelectChoices.querySelectorAll('.choice')[select.selectedIndex];
    customSelectChoice.innerText = choice.innerText;
    choice.classList.add('selected');

    // Save profile
    const saveProfileButton = document.getElementById("save-profile");
    const firstnameInput = document.getElementById("first-name");
    const lastnameInput = document.getElementById("last-name");
    const usernameInput = document.getElementById("username");
    const emailInput = document.getElementById("input-email");

    saveProfileButton.addEventListener("click", () => {
        fetch("/api/profile/set", {
            method: "POST",
            credentials: "same-origin",
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                firstname: firstnameInput.value,
                lastname: lastnameInput.value,
                username: usernameInput.value,
                email: emailInput.value,
                visibility: select.selectedIndex
            })
        }).then(response => {
            if (response.ok) {
                window.location.reload();
            }
        });
    });

    // Save password
    const changePasswordButton = document.getElementById("change-password");
    changePasswordButton.addEventListener("click", () => {
        if (checkBothPassword()) {
            fetch("/api/profile/password/set", {
                method: "POST",
                credentials: "same-origin",
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    password: passwordInput.value,
                    confirm: confirmPasswordInput.value,
                })
            }).then(response => {
                if (response.ok) {
                    window.location.reload();
                }
            })
        }
    });
});
