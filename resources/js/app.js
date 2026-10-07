import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const successMessage = document.querySelector('[data-swal-success]');
if (successMessage) {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: successMessage.textContent.trim(),
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#1678b8',
        timer: 2800,
        timerProgressBar: true,
    });
}

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        if (form.dataset.confirmed === 'true') return;

        event.preventDefault();
        const action = event.submitter?.textContent.trim() || 'Lanjutkan';
        const destructive = /hapus|nonaktifkan|arsipkan/i.test(action);
        const title = /nonaktifkan/i.test(action)
            ? 'Nonaktifkan data ini?'
            : /^aktifkan/i.test(action)
                ? 'Aktifkan akun ini?'
            : /hapus/i.test(action)
                ? 'Hapus data ini?'
                : /arsipkan/i.test(action)
                    ? 'Arsipkan data ini?'
                    : 'Konfirmasi tindakan';
        const result = await Swal.fire({
            icon: 'warning',
            title,
            text: form.dataset.confirm,
            showCancelButton: true,
            confirmButtonText: action,
            cancelButtonText: 'Batal',
            confirmButtonColor: destructive ? '#e6293f' : '#1678b8',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
            focusCancel: true,
        });

        if (result.isConfirmed) {
            form.dataset.confirmed = 'true';
            form.requestSubmit(event.submitter);
        }
    });
});

document.querySelectorAll('[data-login-form]').forEach((form) => {
    const fields = {
        email: form.querySelector('#email'),
        password: form.querySelector('#password'),
    };

    const showError = (field, message) => {
        const error = form.querySelector(`#${field.id}-error`);
        field.setAttribute('aria-invalid', 'true');
        error.textContent = message;
        error.hidden = false;
    };

    const clearError = (field) => {
        const error = form.querySelector(`#${field.id}-error`);
        field.removeAttribute('aria-invalid');
        error.textContent = '';
        error.hidden = true;
    };

    Object.values(fields).forEach((field) => field.addEventListener('input', () => clearError(field)));

    form.addEventListener('submit', (event) => {
        let firstInvalid = null;

        if (!fields.email.value.trim()) {
            showError(fields.email, 'Email wajib diisi.');
            firstInvalid = fields.email;
        } else if (!fields.email.validity.valid) {
            showError(fields.email, 'Format email tidak valid.');
            firstInvalid = fields.email;
        }

        if (!fields.password.value) {
            showError(fields.password, 'Password wajib diisi.');
            firstInvalid ??= fields.password;
        }

        if (firstInvalid) {
            event.preventDefault();
            firstInvalid.focus();
        }
    });
});

document.querySelectorAll('[data-login-cooldown]').forEach((panel) => {
    const lockedUntil = Number(panel.dataset.lockedUntil) * 1000;
    const duration = Number(panel.dataset.duration) || 300;
    const countdown = panel.querySelector('[data-login-countdown]');
    const progress = panel.querySelector('[data-login-progress]');
    const progressbar = panel.querySelector('[role="progressbar"]');
    const message = panel.querySelector('[data-login-message]');
    const submit = document.querySelector('[data-login-submit]');

    const update = () => {
        const seconds = Math.max(0, Math.ceil((lockedUntil - Date.now()) / 1000));
        const minutes = Math.floor(seconds / 60);
        const remainder = seconds % 60;
        countdown.textContent = `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
        progress.style.width = `${Math.min(100, (seconds / duration) * 100)}%`;
        progressbar.setAttribute('aria-valuenow', String(seconds));

        if (seconds === 0) {
            clearInterval(timer);
            submit.disabled = false;
            message.textContent = 'Anda dapat mencoba masuk kembali.';
            panel.classList.add('is-complete');
        }
    };

    const timer = setInterval(update, 250);
    update();
});

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;
    const label = button.dataset.passwordLabel || 'password';

    button.addEventListener('click', () => {
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        button.setAttribute('aria-pressed', String(!visible));
        button.setAttribute('aria-label', `${visible ? 'Tampilkan' : 'Sembunyikan'} ${label}`);
        button.title = `${visible ? 'Tampilkan' : 'Sembunyikan'} ${label}`;
        button.querySelector('[data-password-show]').hidden = !visible;
        button.querySelector('[data-password-hide]').hidden = visible;
    });
});

document.querySelectorAll('[data-validated-form]').forEach((form) => {
    const controls = [...form.querySelectorAll('input:not([type="hidden"]), select, textarea')]
        .filter((control) => control.type !== 'checkbox');

    const showError = (control, message) => {
        const error = form.querySelector(`#${control.id}-error`);
        if (!error) return;
        control.setAttribute('aria-invalid', 'true');
        error.textContent = message;
        error.hidden = false;
    };

    const clearError = (control) => {
        const error = form.querySelector(`#${control.id}-error`);
        control.removeAttribute('aria-invalid');
        if (!error) return;
        error.textContent = '';
        error.hidden = true;
    };

    controls.forEach((control) => {
        control.addEventListener('input', () => clearError(control));
        control.addEventListener('change', () => clearError(control));
    });

    form.querySelectorAll('[data-digits-only]').forEach((control) => {
        control.addEventListener('input', () => {
            const digits = control.value.replace(/\D/g, '');
            if (control.value !== digits) control.value = digits;
        });
    });

    form.addEventListener('submit', (event) => {
        let firstInvalid = null;
        const invalidate = (control, message) => {
            showError(control, message);
            firstInvalid ??= control;
        };

        controls.filter((control) => !control.disabled).forEach((control) => {
            const label = form.querySelector(`label[for="${control.id}"]`)?.textContent.trim() || 'Bidang ini';
            if (control.required && !control.value.trim()) {
                invalidate(control, `${label} wajib diisi.`);
            } else if (control.type === 'email' && control.value && !control.validity.valid) {
                invalidate(control, 'Format email tidak valid.');
            } else if (control.validity.patternMismatch) {
                invalidate(control, `${label} hanya boleh berisi angka.`);
            }
        });

        const password = form.querySelector('#password');
        const confirmation = form.querySelector('#password_confirmation');
        if (password?.value && (password.value.length < 8 || !/[A-Za-z]/.test(password.value) || !/\d/.test(password.value))) {
            invalidate(password, 'Password minimal 8 karakter dan harus mengandung huruf serta angka.');
        }
        if (password?.value && confirmation && confirmation.value !== password.value) {
            invalidate(confirmation, 'Konfirmasi password tidak sama.');
        }

        if (firstInvalid) {
            event.preventDefault();
            firstInvalid.focus();
        }
    });
});

document.querySelectorAll('[data-account-form]').forEach((form) => {
    const role = form.querySelector('[name="role"]');
    if (!role) return;
    const sync = () => {
        form.querySelectorAll('[data-profile-role]').forEach((group) => {
            const visible = group.dataset.profileRole === role.value ||
                (group.dataset.profileRole === 'profile' && ['teacher', 'student'].includes(role.value));
            group.hidden = !visible;
            group.querySelectorAll('input, select').forEach((input) => { input.disabled = !visible; });
        });
        const status = form.querySelector('[name="profile_status"]');
        if (status) {
            [...status.options].forEach((option) => {
                option.disabled = role.value === 'teacher' && ['graduated', 'transferred'].includes(option.value);
            });
            if (status.selectedOptions[0]?.disabled) status.value = 'active';
        }
    };
    role.addEventListener('change', sync);
    sync();
});
