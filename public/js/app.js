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

// Tìm kiếm và phân trang bằng AJAX: giữ nguyên bảng, chỉ thay nội dung khi có kết quả mới.
document.querySelectorAll('form[data-live-search]').forEach((form) => {
    const input = form.querySelector('input[name=search]');
    const target = document.querySelector(form.dataset.target);
    const pagination = document.getElementById('student-pagination');
    const status = document.getElementById('live-search-status');
    if (!input || !target) {
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
            status.textContent = `${payload.total} sinh viên.`;
        }
        window.history.replaceState({}, '', url);
    };

    const search = () => {
        const url = new URL(window.location.href);
        if (input.value.trim() === '') {
            url.searchParams.delete('search');
        } else {
            url.searchParams.set('search', input.value);
        }
        url.searchParams.delete('page');

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

    input.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(search, 350);
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
