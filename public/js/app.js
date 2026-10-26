document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');

    if (form && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');

    if (!button) {
        return;
    }

    const password = document.getElementById('password');

    if (!(password instanceof HTMLInputElement)) {
        return;
    }

    const isVisible = password.type === 'text';
    password.type = isVisible ? 'password' : 'text';
    button.textContent = isVisible ? 'Hiện' : 'Ẩn';
    button.setAttribute('aria-label', isVisible ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
});

// CSP 'script-src' chặn inline script nên mọi xử lý DOM đều đặt ở file này.

const roleSelect = document.getElementById('role');
const classField = document.getElementById('class-field');

if (roleSelect && classField) {
    const toggleClassField = () => classField.classList.toggle('d-none', roleSelect.value !== 'student');
    roleSelect.addEventListener('change', toggleClassField);
    toggleClassField();
}

const avatarInput = document.getElementById('avatar');
const avatarPreview = document.getElementById('avatar-preview');

if (avatarInput && avatarPreview) {
    const fallback = avatarPreview.getAttribute('src');
    avatarInput.addEventListener('change', () => {
        const file = avatarInput.files && avatarInput.files[0];
        avatarPreview.src = file ? URL.createObjectURL(file) : fallback;
    });
}

// Tìm kiếm và phân trang bằng AJAX: giữ nguyên bảng, chỉ thay nội dung khi có kết quả mới.
document.querySelectorAll('form[data-live-search]').forEach((form) => {
    const target = document.querySelector(form.dataset.target);
    const pagination = document.getElementById(form.dataset.pagination || 'student-pagination');
    const status = document.getElementById('live-search-status');
    const unit = form.dataset.unit || 'sinh viên';
    const fields = form.querySelectorAll('input[name], select[name]');
    if (!target || fields.length === 0) {
        return;
    }

    let debounce;
    let controller;

    const apply = (payload, url) => {
        target.innerHTML = payload.rows;
        if (pagination) {
            pagination.innerHTML = payload.pagination;
        }
        if (status) {
            status.textContent = `${payload.total} ${unit}.`;
        }
        window.history.replaceState({}, '', url);
    };

    const buildUrl = () => {
        const url = new URL(window.location.href);
        fields.forEach((field) => {
            const value = typeof field.value === 'string' ? field.value.trim() : field.value;
            if (value === '') {
                url.searchParams.delete(field.name);
            } else {
                url.searchParams.set(field.name, value);
            }
        });
        url.searchParams.delete('page');

        return url;
    };

    const search = () => {
        const url = buildUrl();

        if (controller) {
            controller.abort();
        }
        controller = new AbortController();

        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            signal: controller.signal,
        })
            .then((response) => response.json())
            .then((payload) => apply(payload, url.toString()))
            .catch(() => {
                // Yêu cầu bị hủy hoặc mất kết nối: giữ bảng hiện tại.
            });
    };

    const scheduleSearch = () => {
        clearTimeout(debounce);
        debounce = setTimeout(search, 350);
    };

    fields.forEach((field) => {
        field.addEventListener(field.tagName === 'INPUT' ? 'input' : 'change', scheduleSearch);
    });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        clearTimeout(debounce);
        search();
    });

    if (pagination) {
        pagination.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!link) {
                return;
            }
            event.preventDefault();
            fetch(link.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            })
                .then((response) => response.json())
                .then((payload) => apply(payload, link.href))
                .catch(() => {
                    window.location.href = link.href;
                });
        });
    }
});
