@once
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.css">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>

    <style>
        .order-structure-searchable .ts-wrapper {
            width: 100%;
        }

        .order-structure-searchable .ts-control {
            min-height: 44px;
            width: 100%;
            border: 1px solid rgb(148 163 184);
            border-radius: 0.5rem;
            background: white;
            padding: 0.65rem 1rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: rgb(30 41 59);
            box-shadow: none;
        }

        .order-structure-searchable .ts-wrapper.focus .ts-control {
            border-color: rgb(59 130 246);
            box-shadow: 0 0 0 3px rgb(219 234 254 / 0.8);
        }

        .order-structure-searchable .ts-wrapper.disabled .ts-control {
            cursor: not-allowed;
            background: rgb(241 245 249);
            color: rgb(148 163 184);
            opacity: 1;
        }

        .order-structure-searchable .ts-control > input,
        .order-structure-searchable .ts-control .item {
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .order-structure-searchable-dropdown {
            z-index: 90 !important;
            max-height: 270px;
            overflow-y: auto;
            border: 1px solid rgb(203 213 225);
            border-radius: 0.65rem;
            background: white;
            box-shadow: 0 12px 30px rgb(15 23 42 / 0.14);
            font-size: 0.875rem;
        }

        .order-structure-searchable-dropdown .option {
            padding: 0.65rem 0.85rem;
            color: rgb(51 65 85);
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .order-structure-searchable-dropdown .active {
            background: rgb(239 246 255);
            color: rgb(29 78 216);
        }
    </style>

    <script>
        window.OrderStructureSelectPair = {
            create(config = {}) {
                const unitSelect = config.unitSelect;
                const sectionSelect = config.sectionSelect;

                if (!unitSelect || !sectionSelect) {
                    return null;
                }

                const noSectionValue = 'Tidak ada seksi';
                const normalizeSection = (value) => value === 'General' ? noSectionValue : String(value || '').trim();
                const parseSections = (option) => {
                    const raw = option?.dataset?.seksi;

                    if (!raw) {
                        return [];
                    }

                    try {
                        const parsed = JSON.parse(raw);

                        return Array.isArray(parsed)
                            ? parsed.map((value) => String(value || '').trim()).filter(Boolean)
                            : [];
                    } catch (error) {
                        return [];
                    }
                };
                const unitDefinitions = Array.from(unitSelect.options)
                    .filter((option) => option.value)
                    .map((option) => ({
                        value: option.value,
                        text: option.textContent || option.value,
                        sections: parseSections(option),
                    }));
                const activeUnitDefinitions = new Map(
                    unitDefinitions.map((definition) => [definition.value, definition])
                );
                let unitTom = null;
                let sectionTom = null;
                let suppressUnitChange = false;

                const replaceNativeOptions = (select, placeholder, definitions) => {
                    select.replaceChildren();

                    const emptyOption = document.createElement('option');
                    emptyOption.value = '';
                    emptyOption.textContent = placeholder;
                    select.appendChild(emptyOption);

                    definitions.forEach((definition) => {
                        const option = document.createElement('option');
                        option.value = definition.value;
                        option.textContent = definition.text;

                        if (Array.isArray(definition.sections)) {
                            option.dataset.seksi = JSON.stringify(definition.sections);
                        }

                        select.appendChild(option);
                    });
                };

                const resetUnitOptions = () => {
                    activeUnitDefinitions.clear();
                    unitDefinitions.forEach((definition) => {
                        activeUnitDefinitions.set(definition.value, definition);
                    });

                    if (unitTom) {
                        unitTom.clear(true);
                        unitTom.clearOptions();
                        unitTom.addOptions(unitDefinitions);
                        unitTom.refreshOptions(false);
                    } else {
                        replaceNativeOptions(
                            unitSelect,
                            config.unitPlaceholder || 'Cari atau pilih Unit Kerja...',
                            unitDefinitions
                        );
                    }
                };

                const setSectionDisabled = (disabled) => {
                    sectionSelect.disabled = disabled;

                    if (sectionTom) {
                        if (disabled) {
                            sectionTom.disable();
                        } else {
                            sectionTom.enable();
                        }
                    }
                };

                const syncSections = (sectionValue = '', options = {}) => {
                    const unitValue = unitTom ? String(unitTom.getValue() || '') : String(unitSelect.value || '');
                    const allowLegacy = Boolean(options.allowLegacy);
                    const normalizedSection = normalizeSection(sectionValue);
                    const definition = activeUnitDefinitions.get(unitValue);
                    const sections = definition?.sections || [];
                    const sectionDefinitions = [];

                    if (unitValue) {
                        if (sections.length > 0) {
                            sections.forEach((section) => {
                                sectionDefinitions.push({ value: section, text: section });
                            });

                            if (
                                allowLegacy
                                && normalizedSection
                                && !sections.includes(normalizedSection)
                            ) {
                                sectionDefinitions.push({
                                    value: normalizedSection,
                                    text: normalizedSection,
                                });
                            }
                        } else if (
                            allowLegacy
                            && normalizedSection
                            && normalizedSection !== noSectionValue
                        ) {
                            sectionDefinitions.push({
                                value: normalizedSection,
                                text: normalizedSection,
                            });
                        } else {
                            sectionDefinitions.push({
                                value: noSectionValue,
                                text: noSectionValue,
                            });
                        }
                    }

                    if (sectionTom) {
                        sectionTom.clear(true);
                        sectionTom.clearOptions();
                        sectionTom.addOptions(sectionDefinitions);
                        sectionTom.refreshOptions(false);
                    } else {
                        replaceNativeOptions(
                            sectionSelect,
                            unitValue
                                ? (config.sectionPlaceholder || 'Cari atau pilih Seksi...')
                                : 'Pilih Unit Kerja terlebih dahulu',
                            sectionDefinitions
                        );
                    }

                    if (!unitValue) {
                        setSectionDisabled(true);

                        return;
                    }

                    setSectionDisabled(false);

                    const nextValue = sections.length === 0
                        ? (sectionDefinitions.some((item) => item.value === normalizedSection)
                            ? normalizedSection
                            : noSectionValue)
                        : (sectionDefinitions.some((item) => item.value === normalizedSection)
                            ? normalizedSection
                            : '');

                    if (sectionTom) {
                        sectionTom.setValue(nextValue, true);
                        sectionTom.setTextboxValue('');
                    } else {
                        sectionSelect.value = nextValue;
                    }
                };

                const setValues = (unitValue = '', sectionValue = '', options = {}) => {
                    const normalizedUnit = String(unitValue || '').trim();
                    const allowLegacy = Boolean(options.allowLegacy);

                    suppressUnitChange = true;
                    resetUnitOptions();

                    if (allowLegacy && normalizedUnit && !activeUnitDefinitions.has(normalizedUnit)) {
                        const legacyDefinition = {
                            value: normalizedUnit,
                            text: normalizedUnit,
                            sections: [],
                        };
                        activeUnitDefinitions.set(normalizedUnit, legacyDefinition);

                        if (unitTom) {
                            unitTom.addOption(legacyDefinition);
                            unitTom.refreshOptions(false);
                        } else {
                            const option = document.createElement('option');
                            option.value = normalizedUnit;
                            option.textContent = normalizedUnit;
                            option.dataset.seksi = '[]';
                            unitSelect.appendChild(option);
                        }
                    }

                    if (unitTom) {
                        unitTom.setValue(normalizedUnit, true);
                        unitTom.setTextboxValue('');
                    } else {
                        unitSelect.value = normalizedUnit;
                    }

                    suppressUnitChange = false;
                    syncSections(sectionValue, { allowLegacy });
                };

                const reset = () => setValues('', '', { allowLegacy: false });

                if (window.TomSelect) {
                    sectionTom = sectionSelect.tomselect || new window.TomSelect(sectionSelect, {
                        create: false,
                        persist: false,
                        maxItems: 1,
                        maxOptions: null,
                        closeAfterSelect: true,
                        openOnFocus: true,
                        allowEmptyOption: true,
                        placeholder: config.sectionPlaceholder || 'Cari atau pilih Seksi...',
                        dropdownParent: 'body',
                        dropdownClass: 'ts-dropdown order-structure-searchable-dropdown',
                        render: {
                            no_results() {
                                const element = document.createElement('div');
                                element.className = 'no-results';
                                element.textContent = 'Seksi tidak ditemukan.';

                                return element;
                            },
                        },
                    });

                    unitTom = unitSelect.tomselect || new window.TomSelect(unitSelect, {
                        create: false,
                        persist: false,
                        maxItems: 1,
                        maxOptions: null,
                        closeAfterSelect: true,
                        openOnFocus: true,
                        allowEmptyOption: true,
                        placeholder: config.unitPlaceholder || 'Cari atau pilih Unit Kerja...',
                        dropdownParent: 'body',
                        dropdownClass: 'ts-dropdown order-structure-searchable-dropdown',
                        onChange() {
                            if (!suppressUnitChange) {
                                syncSections('', { allowLegacy: false });
                            }
                        },
                        render: {
                            no_results() {
                                const element = document.createElement('div');
                                element.className = 'no-results';
                                element.textContent = 'Unit Kerja tidak ditemukan.';

                                return element;
                            },
                        },
                    });
                } else {
                    unitSelect.addEventListener('change', () => {
                        if (!suppressUnitChange) {
                            syncSections('', { allowLegacy: false });
                        }
                    });
                }

                reset();

                return {
                    setValues,
                    reset,
                    syncSections,
                    refresh() {
                        syncSections(sectionTom ? sectionTom.getValue() : sectionSelect.value);
                    },
                    destroy() {
                        unitTom?.destroy();
                        sectionTom?.destroy();
                        unitTom = null;
                        sectionTom = null;
                    },
                };
            },
        };
    </script>
@endonce
