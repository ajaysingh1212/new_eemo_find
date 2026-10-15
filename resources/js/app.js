const toggle = document.getElementById('sidebar-toggle');
const backdrop = document.getElementById('sidebar-backdrop');
const mobile = () => window.matchMedia('(max-width: 900px)').matches;
function closeSidebar() {
    const wasOpen = document.body.classList.contains('sidebar-open');
    document.body.classList.remove('sidebar-open');
    if (backdrop) backdrop.hidden = true;
    toggle?.setAttribute('aria-expanded', mobile() ? 'false' : String(!document.body.classList.contains('sidebar-collapsed')));
    if (wasOpen) toggle?.focus();
}
toggle?.addEventListener('click', () => {
    if (mobile()) {
        const open = document.body.classList.toggle('sidebar-open');
        backdrop.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (open) document.querySelector('#sidebar a')?.focus();
    } else {
        const collapsed = document.body.classList.toggle('sidebar-collapsed');
        toggle.setAttribute('aria-expanded', String(!collapsed));
    }
});
backdrop?.addEventListener('click', closeSidebar);
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeSidebar();
    if (event.key === 'Tab' && mobile() && document.body.classList.contains('sidebar-open')) {
        const items = [...document.querySelectorAll('#sidebar a, #sidebar button')].filter(el => el.getClientRects().length);
        const first = items[0], last = items.at(-1);
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    }
});
window.addEventListener('resize', closeSidebar);
closeSidebar();
document.querySelectorAll('[data-password-toggle]').forEach(button => {
    const input = document.getElementById(button.dataset.passwordToggle);
    const [showIcon, hideIcon] = button.querySelectorAll('.icon');
    button.addEventListener('click', () => {
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', String(visible));
        showIcon.hidden = visible;
        hideIcon.hidden = !visible;
    });
});
document.querySelectorAll('[data-submenu-toggle]').forEach(toggle => {
    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!open));
        document.getElementById(toggle.dataset.submenuToggle).hidden = open;
    });
});
const dialog = document.getElementById('confirm-dialog');
let pendingForm;
document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!dialog?.showModal) {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
            return;
        }
        event.preventDefault();
        pendingForm = form;
        document.getElementById('confirm-message').textContent = form.dataset.confirm;
        dialog.showModal();
        document.getElementById('confirm-cancel').focus();
    });
});
document.getElementById('confirm-cancel')?.addEventListener('click', () => dialog.close());
document.getElementById('confirm-accept')?.addEventListener('click', () => { dialog.close(); pendingForm?.submit(); });
const clocks = document.querySelectorAll('[data-clock]');
if (clocks.length) {
    const loaded = Date.now();
    const updateClocks = () => {
        clocks.forEach(clock => {
            const start = Date.parse(clock.dateTime);
            clock.textContent = new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23', timeZone: clock.dataset.timezone }).format(new Date(start + Date.now() - loaded)).replace(',', '');
        });
    };
    updateClocks();
    setInterval(updateClocks, 1000);
}
const trafficMap = document.getElementById('admin-traffic-map');
if (trafficMap) {
    const status = document.getElementById('traffic-map-status');
    const apiKey = trafficMap.dataset.apiKey;
    if (!apiKey) {
        status.textContent = 'Map unavailable. Set GOOGLE_MAPS_API_KEY in your .env file.';
    } else {
        window.initAdminTrafficMap = () => {
            const center = { lat: 20.5937, lng: 78.9629 };
            new google.maps.Map(trafficMap, {
                center,
                zoom: 5,
                mapTypeId: google.maps.MapTypeId.SATELLITE,
                mapTypeControl: true,
                streetViewControl: true,
                zoomControl: true,
                rotateControl: true,
                scaleControl: true,
                fullscreenControl: true,
                clickableIcons: false,
            });
            status.remove();
        };
        const script = document.createElement('script');
        const params = new URLSearchParams({
            key: apiKey,
            callback: 'initAdminTrafficMap',
            loading: 'async',
        });
        script.src = `https://maps.googleapis.com/maps/api/js?${params}`;
        script.async = true;
        script.onerror = () => {
            status.textContent = 'Map failed to load. Check the Google Maps API key and enabled APIs.';
        };
        document.head.append(script);
    }
}

(() => {
    const backdrop = document.getElementById('user-modal-backdrop');
    const form = document.getElementById('user-form');
    const details = document.getElementById('user-view-details');

    if (!backdrop || !form || !details) return;

    const entity = backdrop.dataset.entity || 'user';
    const entityTitle = entity.charAt(0).toUpperCase() + entity.slice(1);
    const fields = {
        method: document.getElementById('user-form-method'),
        tag: document.getElementById('user-modal-tag'),
        title: document.getElementById('user-modal-title'),
        subtitle: document.getElementById('user-modal-subtitle'),
        submit: document.getElementById('user-submit-button'),
        submitLabel: document.getElementById('user-submit-label'),
        cancel: document.getElementById('user-cancel-button'),
        name: document.getElementById('user-name'),
        email: document.getElementById('user-email'),
        phone: document.getElementById('user-phone'),
        password: document.getElementById('user-password'),
        passwordRequiredMark: document.getElementById('password-required-mark'),
        status: document.getElementById('user-status'),
        statusField: document.getElementById('user-status-field'),
        address: document.getElementById('user-address'),
        image: document.getElementById('user-image'),
        imagePreview: document.getElementById('user-image-preview'),
        imageName: document.getElementById('user-image-name'),
        viewImage: document.getElementById('view-user-image'),
        viewInitials: document.getElementById('view-user-initials'),
    };
    const viewFields = {
        name: document.getElementById('view-user-name'),
        code: document.getElementById('view-user-code'),
        email: document.getElementById('view-user-email'),
        phone: document.getElementById('view-user-phone'),
        address: document.getElementById('view-user-address'),
        status: document.getElementById('view-user-status'),
        lastLogin: document.getElementById('view-user-last-login'),
    };
    const viewEditButton = document.getElementById('view-edit-user');
    let selectedUser = null;
    let previewUrl = null;

    const openModal = (mode, user = null) => {
        const isCreate = mode === 'create';
        const isView = mode === 'view';
        if (!isCreate && !user) return;

        const reopeningAfterValidation = isCreate && backdrop.hasAttribute('data-reopen-create');
        selectedUser = user;
        fields.tag.textContent = `${isCreate ? 'CREATE' : isView ? 'VIEW' : 'UPDATE'} ${entityTitle.toUpperCase()}`;
        fields.title.textContent = isCreate ? `Register a new ${entity}` : isView ? `${entityTitle} details` : `Edit ${entity}`;
        fields.subtitle.textContent = isCreate
            ? `Add a ${entity} record to your registry.`
            : isView
                ? `Review the registered ${entity} information.`
                : `Update this ${entity}’s registry information.`;
        form.hidden = isView;
        details.hidden = !isView;
        fields.method.value = isCreate ? 'POST' : 'PUT';
        fields.submit.hidden = isView;
        fields.submitLabel.textContent = isCreate ? `Create ${entity}` : 'Save changes';
        fields.cancel.textContent = isView ? 'Close' : 'Cancel';
        form.action = isCreate
            ? form.dataset.createAction
            : form.dataset.updateActionTemplate.replace('__USER__', user.id);

        if (!reopeningAfterValidation) {
            fields.name.value = isCreate ? '' : (user.name || '');
            fields.email.value = isCreate ? '' : (user.email || '');
            fields.phone.value = isCreate ? '' : (user.phone || '');
            fields.address.value = isCreate ? '' : (user.address || '');
        }
        fields.password.value = '';
        fields.password.required = isCreate;
        fields.passwordRequiredMark.hidden = !isCreate;
        fields.password.placeholder = isCreate ? 'Enter password' : 'Leave empty to keep current password';
        fields.statusField.hidden = isCreate || isView;
        fields.status.disabled = isCreate || isView;
        fields.status.required = !isCreate && !isView;
        fields.status.value = isCreate ? 'active' : (user.status || 'active');
        fields.image.value = '';
        fields.name.readOnly = isView;
        fields.email.readOnly = isView;
        fields.phone.readOnly = isView;
        fields.address.readOnly = isView;
        fields.password.readOnly = isView;
        fields.image.disabled = isView;
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }
        const initialImage = isCreate ? '' : (user.image || '');
        fields.imagePreview.src = initialImage;
        fields.imagePreview.hidden = !initialImage;
        fields.imageName.textContent = initialImage ? 'Current profile photo' : 'Choose a profile photo';

        if (isView) {
            viewFields.name.textContent = user.name || '';
            viewFields.code.textContent = user.code || '';
            viewFields.email.textContent = user.email || '—';
            viewFields.phone.textContent = user.phone || '—';
            viewFields.address.textContent = user.address || '—';
            viewFields.status.textContent = (user.status || 'inactive').charAt(0).toUpperCase() + (user.status || 'inactive').slice(1);
            viewFields.lastLogin.textContent = user.lastLogin || 'Never';
            fields.viewImage.alt = user.name ? `${user.name} profile` : `${entityTitle} profile`;
            if (user.image) {
                fields.viewImage.src = user.image;
                fields.viewImage.hidden = false;
                fields.viewInitials.hidden = true;
            } else {
                fields.viewImage.removeAttribute('src');
                fields.viewImage.hidden = true;
                fields.viewInitials.textContent = (user.name || entityTitle.charAt(0)).trim().split(/\s+/).slice(0, 2).map(part => part.charAt(0).toUpperCase()).join('');
                fields.viewInitials.hidden = false;
            }
        }

        backdrop.hidden = false;
        document.body.style.overflow = 'hidden';
        (isView ? fields.cancel : fields.name).focus();
    };

    if (form.dataset.demoOnly === 'true') {
        form.addEventListener('submit', event => event.preventDefault());
    }

    const closeModal = () => {
        backdrop.hidden = true;
        document.body.style.overflow = '';
    };

    document.querySelectorAll('[data-open-user-modal]').forEach(button => {
        button.addEventListener('click', () => openModal('create'));
    });
    document.querySelectorAll('[data-close-user-modal]').forEach(button => {
        button.addEventListener('click', closeModal);
    });
    document.querySelectorAll('.view-button').forEach(button => {
        button.addEventListener('click', () => openModal('view', {
            id: button.dataset.userId,
            name: button.dataset.userName,
            email: button.dataset.userEmail,
            phone: button.dataset.userPhone,
            address: button.dataset.userAddress,
            status: button.dataset.userStatus,
            code: button.dataset.userCode,
            lastLogin: button.dataset.userLastLogin,
            image: button.dataset.userImage,
        }));
    });
    document.querySelectorAll('.edit-button').forEach(button => {
        button.addEventListener('click', () => openModal('edit', {
            id: button.dataset.userId,
            name: button.dataset.userName,
            email: button.dataset.userEmail,
            phone: button.dataset.userPhone,
            address: button.dataset.userAddress,
            image: button.dataset.userImage,
            status: button.dataset.userStatus,
        }));
    });
    viewEditButton?.addEventListener('click', () => {
        if (selectedUser) openModal('edit', selectedUser);
    });
    fields.image.addEventListener('change', () => {
        const [file] = fields.image.files;
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = file ? URL.createObjectURL(file) : null;
        fields.imagePreview.src = previewUrl || (selectedUser?.image || '');
        fields.imagePreview.hidden = !fields.imagePreview.src;
        fields.imageName.textContent = file?.name || (selectedUser?.image ? 'Current profile photo' : 'Choose a profile photo');
    });
    backdrop.addEventListener('click', event => {
        if (event.target === backdrop) closeModal();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !backdrop.hidden) closeModal();
    });

    if (backdrop.hasAttribute('data-reopen-create')) openModal('create');
})();
