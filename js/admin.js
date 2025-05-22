// Confirm dangerous actions
function confirmAction(message) {
    return confirm(message);
}

// Add confirmation to dangerous actions
document.addEventListener('DOMContentLoaded', function() {
    // Add confirmation to user disable buttons
    const dangerButtons = document.querySelectorAll('.btn-danger');
    dangerButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirmAction('Are you sure you want to perform this action?')) {
                e.preventDefault();
            }
        });
    });

    // Handle bulk actions if present
    const bulkActionForm = document.querySelector('#bulk-action-form');
    if (bulkActionForm) {
        bulkActionForm.addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('input[name="selected_users[]"]:checked').length;
            if (selected === 0) {
                e.preventDefault();
                alert('Please select at least one user');
            } else {
                if (!confirmAction(`Are you sure you want to perform this action on ${selected} users?`)) {
                    e.preventDefault();
                }
            }
        });
    }

    // Toggle select all checkbox if present
    const selectAll = document.querySelector('#select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_users[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
});

// Handle file upload preview if present
const fileInput = document.querySelector('input[type="file"]');
if (fileInput) {
    fileInput.addEventListener('change', function() {
        const preview = document.querySelector('#file-preview');
        if (preview) {
            preview.innerHTML = '';
            for (const file of this.files) {
                const item = document.createElement('div');
                item.textContent = `${file.name} (${formatFileSize(file.size)})`;
                preview.appendChild(item);
            }
        }
    });
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Handle dynamic form fields if present
const addFieldButton = document.querySelector('#add-field');
if (addFieldButton) {
    addFieldButton.addEventListener('click', function() {
        const container = document.querySelector('#dynamic-fields');
        const fieldCount = container.children.length;
        const newField = document.createElement('div');
        newField.className = 'form-group';
        newField.innerHTML = `
            <input type="text" name="field_name[]" placeholder="Field Name">
            <input type="text" name="field_value[]" placeholder="Field Value">
            <button type="button" class="remove-field">Remove</button>
        `;
        container.appendChild(newField);
    });
}

// Handle remove field button clicks
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-field')) {
        e.target.parentElement.remove();
    }
});
