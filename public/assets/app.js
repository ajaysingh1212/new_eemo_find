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
const loginLocationForm = document.querySelector('form[data-login-location]');
if (loginLocationForm) {
    const loginButton = loginLocationForm.querySelector('button[type="submit"], input[type="submit"]');
    const note = document.getElementById('login-location-note');
    const submitWithLocation = (permissionStatus, position = null) => {
        loginLocationForm.elements.latitude.value = position ? position.coords.latitude : '';
        loginLocationForm.elements.longitude.value = position ? position.coords.longitude : '';
        loginLocationForm.elements.location_status.value = permissionStatus;
        if (note) note.textContent = position
            ? 'Location captured for this sign-in.'
            : 'Location is unavailable; continuing sign-in without it.';
        if (loginButton) loginButton.disabled = false;
        loginLocationForm.dataset.locationReady = 'true';
        loginLocationForm.requestSubmit();
    };
    loginLocationForm.addEventListener('submit', event => {
        if (loginLocationForm.dataset.locationReady === 'true') return;
        event.preventDefault();
        if (loginLocationForm.dataset.locationPending === 'true') return;
        loginLocationForm.dataset.locationPending = 'true';
        if (loginButton) loginButton.disabled = true;
        if (note) note.textContent = 'Requesting location permission…';
        if (!navigator.geolocation) {
            submitWithLocation('unavailable');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            position => submitWithLocation('granted', position),
            error => submitWithLocation(error.code === 1 ? 'denied' : 'unavailable'),
            { enableHighAccuracy: false, timeout: 10000, maximumAge: 0 }
        );
    });
}

(() => {
    const weather = document.getElementById('admin-weather');
    const locationButton = document.getElementById('admin-location-button');
    if (!weather || !locationButton) return;

    const temperature = document.getElementById('admin-weather-temperature');
    const condition = document.getElementById('admin-weather-condition');
    const feelsLike = document.getElementById('admin-weather-feels-like');
    const humidity = document.getElementById('admin-weather-humidity');
    const wind = document.getElementById('admin-weather-wind');
    const locationLabel = document.getElementById('admin-weather-coordinates');
    const status = document.getElementById('admin-weather-status');
    const icons = [...document.querySelectorAll('#admin-weather-icon .icon')];
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let currentCoordinates = null;

    const weatherDescription = code => {
        if (code === 0) return ['Clear', 'sun'];
        if ([1, 2].includes(code)) return ['Partly cloudy', 'cloud'];
        if (code === 3) return ['Overcast', 'cloud'];
        if ([45, 48].includes(code)) return ['Fog', 'cloud'];
        if ([51, 53, 55, 56, 57].includes(code)) return ['Drizzle', 'cloud-rain'];
        if ([61, 63, 65, 66, 67, 80, 81, 82, 95, 96, 99].includes(code)) return ['Rain / storm', 'cloud-rain'];
        if ([71, 73, 75, 77, 85, 86].includes(code)) return ['Snow', 'cloud'];
        return ['Current conditions', 'cloud'];
    };

    const showMessage = message => {
        condition.textContent = message;
        status.textContent = message;
    };

    const showWeather = async coordinates => {
        currentCoordinates = coordinates;
        showMessage('Loading weather…');
        try {
            const query = new URLSearchParams({
                latitude: String(coordinates.latitude),
                longitude: String(coordinates.longitude),
                current: 'temperature_2m,apparent_temperature,relative_humidity_2m,wind_speed_10m,weather_code',
                temperature_unit: 'celsius',
                timezone: 'auto',
            });
            const response = await fetch(`https://api.open-meteo.com/v1/forecast?${query}`);
            if (!response.ok) throw new Error(`Weather service returned ${response.status}.`);
            const result = await response.json();
            if (typeof result.current?.temperature_2m !== 'number' || typeof result.current?.weather_code !== 'number') {
                throw new Error('Weather service returned incomplete current conditions.');
            }
            const [description, iconName] = weatherDescription(result.current.weather_code);
            temperature.textContent = `${Math.round(result.current.temperature_2m)}°C`;
            feelsLike.textContent = typeof result.current.apparent_temperature === 'number'
                ? `${Math.round(result.current.apparent_temperature)}°`
                : '--°';
            humidity.textContent = typeof result.current.relative_humidity_2m === 'number'
                ? `${Math.round(result.current.relative_humidity_2m)}%`
                : '--%';
            wind.textContent = typeof result.current.wind_speed_10m === 'number'
                ? `${Math.round(result.current.wind_speed_10m)} km/h`
                : '-- km/h';
            condition.textContent = description;
            status.textContent = `Current weather: ${temperature.textContent}, ${description}. Feels like ${feelsLike.textContent}, humidity ${humidity.textContent}, wind ${wind.textContent}.`;
            icons.forEach(icon => { icon.hidden = icon.dataset.weatherIcon !== iconName; });
        } catch (error) {
            showMessage('Weather unavailable');
            console.error('Unable to load current weather from Open-Meteo.', error);
        }
    };

    const saveLocation = async (permissionStatus, coordinates = null) => {
        const payload = { permission_status: permissionStatus };
        if (coordinates) {
            payload.latitude = coordinates.latitude;
            payload.longitude = coordinates.longitude;
        }
        const response = await fetch(weather.dataset.locationUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        if (!response.ok) throw new Error(`Unable to save location (HTTP ${response.status}).`);
        const result = await response.json();
        if (result.saved !== true) throw new Error('The server did not confirm the location update.');
    };

    const requestLocation = () => {
        locationButton.disabled = true;
        showMessage('Requesting location permission…');
        if (!navigator.geolocation) {
            saveLocation('unavailable')
                .then(() => showMessage('Location is not supported by this browser.'))
                .catch(error => {
                    showMessage('Could not save location status');
                    console.error('Unable to save location status.', error);
                })
                .finally(() => { locationButton.disabled = false; });
            return;
        }
        navigator.geolocation.getCurrentPosition(async position => {
            const coordinates = { latitude: position.coords.latitude, longitude: position.coords.longitude };
            try {
                await saveLocation('granted', coordinates);
                weather.dataset.latitude = String(coordinates.latitude);
                weather.dataset.longitude = String(coordinates.longitude);
                locationLabel.textContent = `${coordinates.latitude.toFixed(4)}°, ${coordinates.longitude.toFixed(4)}°`;
                await showWeather(coordinates);
            } catch (error) {
                showMessage('Could not save location');
                console.error('Unable to save current location.', error);
            } finally {
                locationButton.disabled = false;
            }
        }, async error => {
            const permissionStatus = error.code === 1 ? 'denied' : 'unavailable';
            try {
                await saveLocation(permissionStatus);
                showMessage(permissionStatus === 'denied' ? 'Location permission denied' : 'Location unavailable');
                if (currentCoordinates) await showWeather(currentCoordinates);
            } catch (saveError) {
                showMessage('Could not save location status');
                console.error('Unable to save location status.', saveError);
            } finally {
                locationButton.disabled = false;
            }
        }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 0 });
    };

    locationButton.addEventListener('click', requestLocation);
    icons[0] && (icons[0].dataset.weatherIcon = 'sun');
    icons[1] && (icons[1].dataset.weatherIcon = 'cloud');
    icons[2] && (icons[2].dataset.weatherIcon = 'cloud-rain');

    const latitude = Number(weather.dataset.latitude);
    const longitude = Number(weather.dataset.longitude);
    if (weather.dataset.latitude !== '' && weather.dataset.longitude !== '' && Number.isFinite(latitude) && Number.isFinite(longitude)) {
        showWeather({ latitude, longitude });
    } else if (weather.dataset.permissionStatus === 'denied') {
        showMessage('Location permission denied');
    } else if (weather.dataset.permissionStatus === 'unavailable') {
        showMessage('Location unavailable');
    }
})();
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

    if (!backdrop || !form) return;

    const entity = backdrop.dataset.entity || 'user';
    const entityTitle = entity.charAt(0).toUpperCase() + entity.slice(1);
    const fields = {
        method: document.getElementById('user-form-method'),
        tag: document.getElementById('user-modal-tag'),
        title: document.getElementById('user-modal-title'),
        subtitle: document.getElementById('user-modal-subtitle'),
        submitLabel: document.getElementById('user-submit-label'),
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
    };
    let selectedUser = null;
    let previewUrl = null;

    const openModal = (mode, user = null) => {
        const isCreate = mode === 'create';
        if (!isCreate && !user) return;

        const reopeningAfterValidation = isCreate && backdrop.hasAttribute('data-reopen-create');
        selectedUser = user;
        fields.tag.textContent = isCreate ? `CREATE ${entityTitle.toUpperCase()}` : `UPDATE ${entityTitle.toUpperCase()}`;
        fields.title.textContent = isCreate ? `Register a new ${entity}` : `Edit ${entity}`;
        fields.subtitle.textContent = isCreate
            ? `Add a ${entity} record to your registry.`
            : `Update this ${entity}’s registry information.`;
        fields.method.value = isCreate ? 'POST' : 'PUT';
        fields.submitLabel.textContent = isCreate ? `Create ${entity}` : 'Save changes';
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
        fields.statusField.hidden = isCreate;
        fields.status.disabled = isCreate;
        fields.status.required = !isCreate;
        fields.status.value = isCreate ? 'active' : (user.status || 'active');
        fields.image.value = '';
        fields.name.readOnly = false;
        fields.email.readOnly = false;
        fields.phone.readOnly = false;
        fields.address.readOnly = false;
        fields.password.readOnly = false;
        fields.image.disabled = false;
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }
        const initialImage = isCreate ? '' : (user.image || '');
        fields.imagePreview.src = initialImage;
        fields.imagePreview.hidden = !initialImage;
        fields.imageName.textContent = initialImage ? 'Current profile photo' : 'Choose a profile photo';

        backdrop.hidden = false;
        document.body.style.overflow = 'hidden';
        fields.name.focus();
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

(() => {
    const createDialog = document.getElementById('support-create-dialog');
    const groupCreateDialog = document.getElementById('group-create-dialog');
    const groupForm = document.getElementById('group-create-form');
    const creatorPicker = document.getElementById('group-creator-picker');
    const creatorTrigger = document.getElementById('group-creator-trigger');
    const creatorDropdown = document.getElementById('group-creator-dropdown');
    const creatorValue = document.getElementById('created_by');
    const creatorCurrent = document.getElementById('group-creator-current');
    const creatorSearch = document.getElementById('group-creator-search');
    const creatorNoResults = document.getElementById('group-creator-no-results');
    const memberPicker = document.getElementById('group-member-control');
    const memberTrigger = document.getElementById('group-member-trigger');
    const memberDropdown = document.getElementById('group-member-dropdown');
    const memberSearch = document.getElementById('group-member-search');
    const memberOptions = document.getElementById('group-member-options');
    const selectedMembers = document.getElementById('group-selected-members');
    const memberCount = document.getElementById('group-member-count');
    const memberPlaceholder = document.getElementById('group-member-placeholder');
    const noMemberResults = document.getElementById('group-member-no-results');
    const openDialog = dialog => {
        if (dialog?.showModal && !dialog.open) dialog.showModal();
    };

    const setPickerOpen = (trigger, dropdown, search, open) => {
        if (!trigger || !dropdown) return;
        trigger.setAttribute('aria-expanded', String(open));
        dropdown.hidden = !open;
        if (open) search?.focus();
    };

    if (creatorPicker && creatorTrigger && creatorDropdown && creatorValue && creatorCurrent && creatorSearch && creatorNoResults) {
        const creatorOptions = [...creatorDropdown.querySelectorAll('.group-picker-option')];
        const filterCreators = () => {
            const search = creatorSearch.value.trim().toLocaleLowerCase();
            let visibleCount = 0;
            creatorOptions.forEach(option => {
                const matches = option.dataset.creatorSearch.includes(search);
                option.hidden = !matches;
                if (matches) visibleCount++;
            });
            creatorNoResults.hidden = visibleCount > 0;
        };

        creatorTrigger.addEventListener('click', () => {
            setPickerOpen(creatorTrigger, creatorDropdown, creatorSearch, creatorDropdown.hidden);
        });
        creatorSearch.addEventListener('input', filterCreators);
        creatorOptions.forEach(option => {
            option.addEventListener('click', () => {
                creatorValue.value = option.dataset.creatorId;
                creatorCurrent.textContent = option.dataset.creatorName;
                creatorCurrent.dataset.userCode = option.dataset.creatorCode;
                creatorSearch.setCustomValidity('');
                creatorOptions.forEach(item => item.classList.toggle('is-selected', item === option));
                setPickerOpen(creatorTrigger, creatorDropdown, creatorSearch, false);
            });
        });

        const initialCreator = creatorOptions.find(option => option.dataset.creatorId === creatorValue.value);
        if (initialCreator) {
            creatorCurrent.textContent = initialCreator.dataset.creatorName;
            creatorCurrent.dataset.userCode = initialCreator.dataset.creatorCode;
            initialCreator.classList.add('is-selected');
        }
    }

    if (memberSearch && memberOptions && selectedMembers && memberCount && noMemberResults) {
        const options = [...memberOptions.querySelectorAll('.group-member-option')];
        const updateSelectedMembers = () => {
            const checked = options.filter(option => option.querySelector('input').checked);
            const countLabel = `${checked.length} ${checked.length === 1 ? 'user' : 'users'} selected`;
            memberCount.textContent = countLabel;
            if (memberPlaceholder) memberPlaceholder.textContent = checked.length ? `${checked.length} group ${checked.length === 1 ? 'member' : 'members'} selected` : 'Click to select group members';
            memberSearch.setCustomValidity(checked.length ? '' : memberSearch.dataset.validationAttempted ? 'Select at least one group member.' : '');
            selectedMembers.replaceChildren();

            checked.forEach(option => {
                const checkbox = option.querySelector('input');
                const name = option.querySelector('.group-member-option-copy strong').textContent;
                const chip = document.createElement('span');
                chip.className = 'group-selected-chip';

                const label = document.createElement('span');
                label.textContent = name;

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.setAttribute('aria-label', `Remove ${name}`);
                remove.textContent = '×';
                remove.addEventListener('click', () => {
                    checkbox.checked = false;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                });

                chip.append(label, remove);
                selectedMembers.append(chip);
            });
        };

        memberSearch.addEventListener('input', () => {
            const search = memberSearch.value.trim().toLocaleLowerCase();
            let visibleCount = 0;

            options.forEach(option => {
                const matches = option.dataset.memberSearch.includes(search);
                option.hidden = !matches;
                if (matches) visibleCount++;
            });

            noMemberResults.hidden = visibleCount > 0;
        });

        memberTrigger?.addEventListener('click', () => {
            setPickerOpen(memberTrigger, memberDropdown, memberSearch, memberDropdown.hidden);
        });
        options.forEach(option => {
            option.querySelector('input').addEventListener('change', updateSelectedMembers);
        });
        updateSelectedMembers();
    }

    groupForm?.addEventListener('submit', event => {
        if (creatorValue && !creatorValue.value) {
            event.preventDefault();
            creatorSearch.dataset.validationAttempted = 'true';
            creatorSearch.setCustomValidity('Select a group owner.');
            setPickerOpen(creatorTrigger, creatorDropdown, creatorSearch, true);
            creatorSearch.reportValidity();
            return;
        }

        if (memberOptions && !memberOptions.querySelector('input[name="group_members[]"]:checked')) {
            event.preventDefault();
            memberSearch.dataset.validationAttempted = 'true';
            memberSearch.setCustomValidity('Select at least one group member.');
            setPickerOpen(memberTrigger, memberDropdown, memberSearch, true);
            memberSearch.reportValidity();
        }
    });

    document.addEventListener('click', event => {
        if (creatorPicker && !creatorPicker.contains(event.target)) {
            setPickerOpen(creatorTrigger, creatorDropdown, creatorSearch, false);
        }
        if (memberPicker && !memberPicker.contains(event.target)) {
            setPickerOpen(memberTrigger, memberDropdown, memberSearch, false);
        }
    });
    [creatorSearch, memberSearch].forEach(search => search?.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            setPickerOpen(creatorTrigger, creatorDropdown, creatorSearch, false);
            setPickerOpen(memberTrigger, memberDropdown, memberSearch, false);
        }
    }));

    document.querySelector('[data-open-support-create]')?.addEventListener('click', () => openDialog(createDialog));
    document.querySelector('[data-open-group-create]')?.addEventListener('click', () => openDialog(groupCreateDialog));
    document.querySelectorAll('[data-open-support-ticket]').forEach(button => {
        button.addEventListener('click', () => openDialog(document.getElementById(button.dataset.openSupportTicket)));
    });
    document.querySelectorAll('.support-dialog.is-reopen').forEach(openDialog);
    document.querySelectorAll('[data-close-support-dialog]').forEach(button => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });
    document.querySelectorAll('.support-dialog').forEach(dialog => {
        dialog.addEventListener('click', event => {
            if (event.target === dialog) dialog.close();
        });
    });
})();
