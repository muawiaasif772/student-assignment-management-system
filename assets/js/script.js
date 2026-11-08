document.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('confirmModal');
    if (modalElement && typeof bootstrap !== 'undefined') {
        var modal = new bootstrap.Modal(modalElement);
        var form = document.getElementById('confirmForm');
        var idInput = document.getElementById('confirmRecordId');
        var actionInput = document.getElementById('confirmAction');
        var titleElement = document.getElementById('confirmTitle');
        var messageElement = document.getElementById('confirmMessage');
        var submitButton = document.getElementById('confirmSubmit');

        document.querySelectorAll('.js-confirm').forEach(function (button) {
            button.addEventListener('click', function () {
                form.action = button.getAttribute('data-url') || '';
                idInput.name = button.getAttribute('data-field') || 'id';
                idInput.value = button.getAttribute('data-id') || '';
                actionInput.value = button.getAttribute('data-action') || '';
                titleElement.textContent = button.getAttribute('data-title') || 'Please confirm';
                messageElement.textContent = button.getAttribute('data-message') || 'Are you sure?';
                submitButton.textContent = button.getAttribute('data-confirm-label') || 'Confirm';
                submitButton.className = 'btn ' + (button.getAttribute('data-confirm-class') || 'btn-danger');
                modal.show();
            });
        });
    }

    document.querySelectorAll('.alert-success').forEach(function (alert) {
        window.setTimeout(function () {
            if (!alert.isConnected || typeof bootstrap === 'undefined' || !alert.classList.contains('alert-dismissible')) {
                return;
            }
            bootstrap.Alert.getOrCreateInstance(alert).close();
        }, 6000);
    });

    document.querySelectorAll('.js-password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-target') || '');
            if (!input) {
                return;
            }
            var show = input.getAttribute('type') === 'password';
            input.setAttribute('type', show ? 'text' : 'password');
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    });

    document.querySelectorAll('.js-file').forEach(function (input) {
        input.addEventListener('change', function () {
            var note = document.getElementById(input.getAttribute('data-note') || '');
            if (!note) {
                return;
            }
            var file = input.files && input.files[0];
            if (!file) {
                note.textContent = '';
                return;
            }
            var max = Number(document.body.getAttribute('data-max-upload') || 0);
            var sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            if (max && file.size > max) {
                note.textContent = 'This file is ' + sizeMb + ' MB. The maximum size is 10 MB.';
            } else {
                note.textContent = file.name + ' (' + sizeMb + ' MB) selected.';
            }
        });
    });
});
