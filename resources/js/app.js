const garageType = document.querySelector('[data-garage-type]');
const electricityField = document.querySelector('[data-electricity-field]');

if (garageType && electricityField) {
    const electricityRate = electricityField.querySelector('input');

    const updateElectricityField = () => {
        const isElectric = garageType.selectedOptions[0]?.dataset.prefix === 'E';

        electricityField.hidden = !isElectric;
        electricityRate.disabled = !isElectric;
        electricityRate.required = isElectric;
    };

    garageType.addEventListener('change', updateElectricityField);
    updateElectricityField();
}

const normalizeMoney = (value) => {
    const amount = value.trim().replace(/^Rp\s*/i, '');

    if (/^[1-9][0-9]{0,2}(?:\.[0-9]{3})+(?:,[0-9]{1,2})?$/.test(amount)) {
        return amount.replaceAll('.', '').replace(',', '.');
    }

    if (/^[1-9][0-9]{0,2}(?:,[0-9]{3})+(?:\.[0-9]{1,2})?$/.test(amount)) {
        return amount.replaceAll(',', '');
    }

    if (/^-?[0-9]+(?:[.,][0-9]{1,2})?$/.test(amount)) {
        return amount.replace(',', '.');
    }

    return null;
};

const formatMoney = (amount) => {
    const [whole, decimals] = amount.split('.');
    const grouped = whole.replace(/^(-?)0+(?=[0-9])/, '$1').replace(/\B(?=([0-9]{3})+(?![0-9]))/g, '.');

    return grouped + (decimals === undefined ? '' : ',' + decimals);
};

document.querySelectorAll('[data-currency]').forEach((input) => {
    const validate = () => {
        input.setCustomValidity(
            input.value.trim() !== '' && normalizeMoney(input.value) === null
                ? 'Enter a Rupiah amount such as 100.000 or 100.000,50.'
                : '',
        );
    };

    const format = () => {
        const amount = normalizeMoney(input.value);

        if (amount !== null) {
            input.value = formatMoney(amount);
        }

        validate();
    };

    input.addEventListener('input', validate);
    input.addEventListener('blur', format);
    format();
});

const nik = document.querySelector('[data-nik]');

if (nik) {
    const validateNik = () => {
        nik.setCustomValidity(
            nik.value === '' || /^[0-9]{16}$/.test(nik.value)
                ? ''
                : 'NIK must contain exactly 16 digits.',
        );
    };

    nik.addEventListener('input', validateNik);
    nik.addEventListener('invalid', validateNik);
    validateNik();
}

const bookingForm = document.querySelector('[data-new-booking]');

if (bookingForm) {
    const vehicle = bookingForm.querySelector('[name="vehicle_type"]');
    const rentalType = bookingForm.querySelector('[name="booking_type"]');
    const startDate = bookingForm.querySelector('[name="start_date"]');
    const endDate = bookingForm.querySelector('[name="end_date"]');
    const duration = bookingForm.querySelector('[data-booking-duration]');
    const type = bookingForm.querySelector('[name="garage_type_id"]');
    const garage = bookingForm.querySelector('[name="garage_id"]');
    const status = bookingForm.querySelector('[data-availability-status]');
    let pendingRequest;

    const parseDate = (value) => {
        if (!/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/.test(value)) {
            return null;
        }

        const date = new Date(`${value}T00:00:00Z`);

        return Number.isNaN(date.getTime()) ? null : date;
    };

    const dateString = (date) => date.toISOString().slice(0, 10);
    const addDays = (date, count) => new Date(date.getTime() + count * 86400000);

    const updateDates = (resetEnd = false) => {
        const start = parseDate(startDate.value);

        if (!start) {
            duration.textContent = '';
            return;
        }

        const monthly = rentalType.value === 'monthly';
        const daysInStartMonth = new Date(Date.UTC(start.getUTCFullYear(), start.getUTCMonth() + 1, 0)).getUTCDate();
        const lastDay = new Date(Date.UTC(start.getUTCFullYear(), start.getUTCMonth() + 2, 0));
        const monthEnd = new Date(Date.UTC(start.getUTCFullYear(), start.getUTCMonth() + 1, Math.min(start.getUTCDate(), lastDay.getUTCDate())));
        const maximum = dateString(monthly ? monthEnd : addDays(start, daysInStartMonth - 1));

        endDate.min = dateString(addDays(start, 1));
        endDate.max = maximum > '9999-12-31' || maximum.startsWith('+') ? '9999-12-31' : maximum;
        endDate.readOnly = monthly;

        if (monthly) {
            endDate.value = maximum;
        } else if (resetEnd && (!endDate.value || endDate.value < endDate.min || endDate.value > endDate.max)) {
            endDate.value = endDate.min;
        }

        const end = parseDate(endDate.value);
        const days = end ? Math.round((end - start) / 86400000) : 0;
        duration.textContent = days > 0 ? `Duration: ${days} ${days === 1 ? 'day' : 'days'}` : '';
    };

    const toggleField = (selector, visible) => {
        const field = bookingForm.querySelector(selector);
        const input = field.querySelector('input');

        field.hidden = !visible;
        input.disabled = !visible;
        input.required = visible;
    };

    const updateFields = () => {
        const electric = vehicle.value === 'mobil_listrik';
        const allowed = {
            sepeda: ['S'], sepeda_motor: ['S'], mobil: ['C'], mobil_listrik: ['C', 'E'],
        }[vehicle.value] ?? [];

        Array.from(type.options).forEach((option) => {
            const valid = option.value === '' || allowed.includes(option.dataset.prefix);

            option.hidden = !valid;
            option.disabled = !valid;
        });

        if (type.selectedOptions[0]?.disabled) {
            type.value = '';
        }

        toggleField('[data-plate-field]', vehicle.value !== '' && vehicle.value !== 'sepeda');
        toggleField('[data-battery-field]', electric);
        toggleField('[data-charge-field]', electric && type.selectedOptions[0]?.dataset.prefix === 'E');
    };

    const loadGarages = async () => {
        pendingRequest?.abort();
        const previous = garage.value;

        garage.replaceChildren(new Option('Select a garage', ''));
        garage.disabled = true;

        if (!vehicle.value || !type.value || !startDate.checkValidity() || !endDate.checkValidity()) {
            status.textContent = 'Choose valid vehicle and rental details.';
            return;
        }

        const request = new AbortController();
        pendingRequest = request;
        status.textContent = 'Loading garages…';
        const query = new URLSearchParams({
            vehicle_type: vehicle.value,
            garage_type_id: type.value,
            booking_type: rentalType.value,
            start_date: startDate.value,
            end_date: endDate.value,
        });

        try {
            const response = await fetch(`${bookingForm.dataset.availabilityUrl}?${query}`, {
                headers: { Accept: 'application/json' }, signal: request.signal,
            });

            if (!response.ok) {
                throw new Error('Garage lookup failed.');
            }

            const data = await response.json();

            if (request.signal.aborted) {
                return;
            }

            data.garages.forEach((item) => {
                const price = formatMoney(item.daily_rate);
                garage.add(new Option(`${item.code} — Rp ${price} / day`, String(item.id)));
            });
            garage.value = Array.from(garage.options).some((option) => option.value === previous) ? previous : '';
            garage.disabled = data.garages.length === 0;
            status.textContent = data.garages.length === 0 ? 'No garages available for these dates.' : '';
        } catch (error) {
            if (error.name !== 'AbortError') {
                status.textContent = 'Could not load garages. Change the garage type to try again.';
            }
        }
    };

    bookingForm.addEventListener('input', () => {
        const summary = document.querySelector('[data-booking-quote]');

        if (summary) {
            summary.hidden = true;
        }
    });

    [vehicle, rentalType, startDate, endDate, type].forEach((input) => {
        input.addEventListener('change', () => {
            updateFields();
            updateDates(input === startDate || input === rentalType);
            loadGarages();
        });
    });

    updateFields();
    updateDates();
    loadGarages();
}
