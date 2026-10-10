const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { test } = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../resources/views/admin/orders/workshop/index.blade.php'), 'utf8');

function loadFunctions(first, next, names, globals = {}) {
    const start = source.indexOf(`const ${first} =`);
    const end = source.indexOf(`const ${next} =`, start);
    assert.ok(start >= 0 && end > start, 'Workshop form functions must be available');
    return vm.runInNewContext(`${source.slice(start, end)}\n({ ${names.join(', ')} });`, globals);
}

function input() {
    return {
        value: '',
        validityMessage: '',
        attributes: {},
        events: {},
        addEventListener(name, listener) { this.events[name] = listener; },
        setCustomValidity(message) { this.validityMessage = message; },
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
    };
}

const currency = () => loadFunctions('normalizeBiayaDigits', 'bindOrderSubmission', ['setBiayaFieldValue', 'bindBiayaField']);

test('Rupiah grouping and zero cents keep the original amount, including large integers', () => {
    const { setBiayaFieldValue } = currency();
    for (const [value, expected] of [
        ['1.250.000', '1250000'], ['Rp 1.250.000,00', '1250000'], ['1250000,00', '1250000'],
        ['Rp. 1.250.000,00', '1250000'], ['0', '0'], ['00012', '12'],
        ['9.999.999.999.999.999', '9999999999999999'], ['', ''], [null, ''],
    ]) {
        const display = input();
        const hidden = input();
        setBiayaFieldValue(display, hidden, value);
        assert.equal(hidden.value, expected, String(value));
        assert.equal(display.validityMessage, '');
    }
});

test('negative, fractional and malformed pasted values stay invalid without becoming another amount', () => {
    const { setBiayaFieldValue } = currency();
    for (const value of ['-1000', '(1000)', '1.250,50', '1.250.000,05', '1,250,000.00', '1.25', '12.34.567', '1e3', 'abc']) {
        const display = input();
        const hidden = input();
        setBiayaFieldValue(display, hidden, value);
        assert.equal(display.value, value);
        assert.equal(hidden.value, value);
        assert.notEqual(display.validityMessage, '');
        setBiayaFieldValue(display, hidden, '1250000');
        assert.equal(hidden.value, '1250000');
        assert.equal(display.validityMessage, '');
        assert.equal(display.attributes['aria-invalid'], undefined);
    }
});

test('typing and deleting digits across grouping boundaries keeps formatting and the numeric value', () => {
    const { bindBiayaField } = currency();
    const display = input();
    const hidden = input();
    bindBiayaField(display, hidden);
    for (const digit of '1250000') {
        display.value += digit;
        display.events.input({ inputType: 'insertText', data: digit });
    }
    assert.equal(display.value, '1.250.000');
    assert.equal(hidden.value, '1250000');
    display.value = display.value.slice(0, -1);
    display.events.input({ inputType: 'deleteContentBackward' });
    assert.equal(display.value, '125.000');
    assert.equal(hidden.value, '125000');
    display.value = 'Rp 1.250.000,00';
    display.events.input({ inputType: 'insertFromPaste' });
    assert.equal(hidden.value, '1250000');
    display.value = '-1000';
    display.events.input({ inputType: 'insertFromPaste' });
    assert.notEqual(display.validityMessage, '');
    assert.equal(hidden.value, '-1000');
});

test('typing cents starting with zero does not silently turn a fraction into whole Rupiah', () => {
    const { bindBiayaField, setBiayaFieldValue } = currency();
    const display = input();
    const hidden = input();
    bindBiayaField(display, hidden);
    setBiayaFieldValue(display, hidden, '1250000');
    for (const character of ',05') {
        display.value += character;
        display.events.input({ inputType: 'insertText', data: character });
    }
    assert.equal(display.value, '1.250.000,05');
    assert.notEqual(display.validityMessage, '');
    assert.equal(hidden.value, '1.250.000,05');
    display.value = '1.250.000,00';
    display.events.input({ inputType: 'insertText', data: '0' });
    display.events.blur();
    assert.equal(display.value, '1.250.000');
    assert.equal(hidden.value, '1250000');
});

test('only one valid submit is allowed; browser back restores the button', () => {
    const window = { events: {}, addEventListener(name, listener) { this.events[name] = listener; } };
    const { bindOrderSubmission } = loadFunctions('bindOrderSubmission', 'syncModalNoteField', ['bindOrderSubmission'], { window });
    const button = { textContent: 'Submit', disabled: false };
    const form = { events: {}, dataset: {}, valid: false, reported: false,
        querySelectorAll: () => [button],
        addEventListener(name, listener) { this.events[name] = listener; },
        checkValidity() { return this.valid; }, reportValidity() { this.reported = true; },
    };
    const event = () => ({ defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } });
    bindOrderSubmission(form);
    const invalid = event();
    form.events.submit(invalid);
    assert.equal(invalid.defaultPrevented, true);
    assert.equal(form.reported, true);
    assert.equal(button.disabled, false);
    form.valid = true;
    const first = event();
    form.events.submit(first);
    assert.equal(first.defaultPrevented, false);
    assert.equal(button.disabled, true);
    const second = event();
    form.events.submit(second);
    assert.equal(second.defaultPrevented, true);
    window.events.pageshow();
    assert.equal(button.disabled, false);
    assert.equal(button.textContent, 'Submit');
    const afterBack = event();
    form.events.submit(afterBack);
    assert.equal(afterBack.defaultPrevented, false);
});

test('regu is required for both workshop categories and is not required for service notes', () => {
    const select = { ...input(), classList: { add() {}, remove() {} }, appendChild() {} };
    const textarea = { ...input(), classList: { add() {}, remove() {} } };
    const hidden = input();
    const document = { getElementById: id => ({ createCatatanSelect: select, createCatatanTextarea: textarea, createCatatan: hidden })[id], createElement: () => ({}) };
    const { syncModalNoteField } = loadFunctions('syncModalNoteField', 'bindModalNoteField', ['syncModalNoteField'], {
        document, defaultWorkshopStatus: 'approved_workshop', userNoteDetailOptions: {
            approved_workshop: ['Regu Fabrikasi'], approved_workshop_jasa: ['Regu Fabrikasi'], approved_jasa: ['Jasa'],
        },
    });
    for (const status of ['approved_workshop', 'approved_workshop_jasa']) {
        syncModalNoteField('create', status, 'Regu Fabrikasi');
        assert.equal(select.required, true);
        assert.equal(select.disabled, false);
        assert.equal(hidden.value, 'Regu Fabrikasi');
    }
    syncModalNoteField('create', 'approved_jasa', 'Jasa');
    assert.equal(select.required, false);
    syncModalNoteField('create', 'pending', 'Catatan bebas');
    assert.equal(select.disabled, true);
    assert.equal(textarea.disabled, false);
    assert.equal(hidden.value, 'Catatan bebas');
});
