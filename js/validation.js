// Form validation functions
function validateForm(formElement) {
    const password = formElement.querySelector('#password');
    const confirmPassword = formElement.querySelector('#confirm_password');
    
    if (password && confirmPassword) {
        if (password.value !== confirmPassword.value) {
            alert("Passwords do not match!");
            return false;
        }
    }

    const email = formElement.querySelector('#email');
    if (email && !validateEmail(email.value)) {
        alert("Please enter a valid email address!");
        return false;
    }

    const phone = formElement.querySelector('#phone');
    if (phone && !validatePhone(phone.value)) {
        alert("Please enter a valid phone number!");
        return false;
    }

    return true;
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^\+?[\d\s-]{10,}$/;
    return re.test(phone);
}

// Add event listeners to forms
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });

    // File input preview (for profile image)
    const fileInput = document.querySelector('#profile_image');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) { // 5MB limit
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload an image file (JPEG, PNG, or GIF)');
                    this.value = '';
                    return;
                }
            }
        });
    }
});

// Show/hide password functionality
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}
