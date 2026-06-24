<script setup>
import { computed, ref, watch } from 'vue';
import { ChevronDown, Globe2, Phone } from 'lucide-vue-next';
import { parsePhoneNumberFromString, validatePhoneNumberLength, AsYouType } from 'libphonenumber-js';
import {
    composeInternationalPhone,
    countryDialCodes,
    countryForDialCode,
    defaultCountryCode,
    normalizeDialCode,
    splitInternationalPhone,
} from '@/Data/countryDialCodes';

const props = defineProps({
    modelValue: { type: String, default: '' },
    id: { type: String, default: 'phone_number' },
    label: { type: String, default: 'Phone number' },
    placeholder: { type: String, default: '' },
    helper: { type: String, default: 'Choose a country code, then type digits only.' },
    error: { type: [String, Array], default: '' },
    required: { type: Boolean, default: false },
    defaultCountryCode: { type: String, default: defaultCountryCode },
    autocomplete: { type: String, default: 'tel-national' },
});

const emit = defineEmits(['update:modelValue']);

const countryExamples = Object.freeze({
    LB: '01222333',
    US: '2015550123',
    CA: '4165550123',
    AE: '501234567',
    SA: '501234567',
    QA: '33123456',
    KW: '51234567',
    BH: '36001234',
    OM: '92123456',
    JO: '791234567',
    SY: '944123456',
    IQ: '7701234567',
    EG: '1001234567',
    FR: '0612345678',
    GB: '07123456789',
    DE: '15123456789',
    IT: '3123456789',
    ES: '612345678',
    TR: '5012345678',
});

const selectedCountryCode = ref(countryForDialCode(props.defaultCountryCode));
const localNumber = ref('');
const touched = ref(false);
let syncingFromParent = false;

const externalError = computed(() => Array.isArray(props.error) ? props.error[0] : props.error);
const selectedCountry = computed(() => (
    countryDialCodes.find((country) => country.code === selectedCountryCode.value)
    || countryDialCodes.find((country) => country.dialCode === normalizeDialCode(props.defaultCountryCode))
    || countryDialCodes.find((country) => country.code === 'LB')
));
const selectedDialCode = computed(() => selectedCountry.value?.dialCode || normalizeDialCode(props.defaultCountryCode));
const maxLocalDigits = computed(() => Math.max(6, Math.min(15, 16 - selectedDialCode.value.replace(/\D+/g, '').length)));
const localPlaceholder = computed(() => props.placeholder || countryExamples[selectedCountryCode.value] || '70123456');
const rawInternationalNumber = computed(() => composeInternationalPhone(selectedDialCode.value, localNumber.value));
const parsedPhone = computed(() => {
    if (!localNumber.value) return null;
    return parsePhoneNumberFromString(localNumber.value, selectedCountryCode.value) || null;
});
const validInternationalNumber = computed(() => parsedPhone.value?.isValid() ? parsedPhone.value.number : '');
const formattedPreview = computed(() => {
    if (!localNumber.value) return '';

    if (parsedPhone.value?.isValid()) {
        return parsedPhone.value.formatNational();
    }

    return new AsYouType(selectedCountryCode.value).input(localNumber.value);
});
const localValidationMessage = computed(() => {
    if (!localNumber.value) {
        return props.required && touched.value ? 'Phone number is required.' : '';
    }

    const lengthStatus = validatePhoneNumberLength(localNumber.value, selectedCountryCode.value);

    if (lengthStatus === 'TOO_SHORT') {
        return `This number is too short for ${selectedCountry.value.name}.`;
    }

    if (lengthStatus === 'TOO_LONG') {
        return `This number is too long for ${selectedCountry.value.name}.`;
    }

    if (lengthStatus === 'INVALID_LENGTH') {
        return `This number length does not match ${selectedCountry.value.name}.`;
    }

    if (!parsedPhone.value?.isValid()) {
        return `Enter a valid ${selectedCountry.value.name} phone number.`;
    }

    return '';
});
const shownError = computed(() => externalError.value || ((touched.value || localNumber.value) ? localValidationMessage.value : ''));
const helperText = computed(() => {
    if (shownError.value) return '';

    const example = localPlaceholder.value ? ` Example: ${localPlaceholder.value}.` : '';
    return `${props.helper}${example}`;
});
const previewText = computed(() => {
    if (!localNumber.value || shownError.value) return '';
    return validInternationalNumber.value
        ? `Saved as ${validInternationalNumber.value} (${formattedPreview.value})`
        : `Typing: ${formattedPreview.value}`;
});

function syncFromValue(value) {
    if (syncingFromParent) return;

    const phone = splitInternationalPhone(value, props.defaultCountryCode);
    selectedCountryCode.value = phone.countryCode || countryForDialCode(phone.dialCode, selectedCountryCode.value);
    localNumber.value = cleanLocalNumber(phone.localNumber);
}

function cleanLocalNumber(value) {
    return String(value || '').replace(/\D+/g, '').slice(0, maxLocalDigits.value);
}

function publish() {
    if (syncingFromParent) return;

    syncingFromParent = true;
    localNumber.value = cleanLocalNumber(localNumber.value);
    emit('update:modelValue', validInternationalNumber.value || rawInternationalNumber.value);
    queueMicrotask(() => {
        syncingFromParent = false;
    });
}

watch(() => props.modelValue, syncFromValue, { immediate: true });
watch(() => props.defaultCountryCode, (code) => {
    if (!props.modelValue) {
        selectedCountryCode.value = countryForDialCode(code);
        publish();
    }
});
watch(selectedCountryCode, () => {
    localNumber.value = cleanLocalNumber(localNumber.value);
    publish();
});

function updateLocalNumber(event) {
    touched.value = true;
    localNumber.value = cleanLocalNumber(event.target.value);
    publish();
}

function handleBlur() {
    touched.value = true;
    publish();
}
</script>

<template>
    <div class="phone-field">
        <label v-if="label" :for="id" class="phone-field-label">{{ label }}</label>

        <div :class="['phone-field-shell', { 'phone-field-shell-error': shownError }]">
            <div class="phone-country-control">
                <Globe2 class="h-4 w-4 text-black/45" aria-hidden="true" />
                <select
                    v-model="selectedCountryCode"
                    class="phone-country-select"
                    :aria-label="`${label} country code`"
                >
                    <option
                        v-for="country in countryDialCodes"
                        :key="`${country.code}-${country.dialCode}`"
                        :value="country.code"
                    >
                        {{ country.name }} {{ country.dialCode }}
                    </option>
                </select>
                <span class="phone-country-code">{{ selectedDialCode }}</span>
                <ChevronDown class="h-4 w-4 text-black/40" aria-hidden="true" />
            </div>

            <div class="phone-local-control">
                <Phone class="h-4 w-4 text-black/35" aria-hidden="true" />
                <input
                    :id="id"
                    :value="localNumber"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    :maxlength="maxLocalDigits"
                    :placeholder="localPlaceholder"
                    :autocomplete="autocomplete"
                    :required="required"
                    class="phone-local-input"
                    @input="updateLocalNumber"
                    @blur="handleBlur"
                />
            </div>
        </div>

        <p v-if="helperText" class="phone-field-help">
            {{ helperText }}
            <span v-if="previewText" class="phone-field-preview">{{ previewText }}</span>
        </p>
        <p v-if="shownError" class="phone-field-error">{{ shownError }}</p>
    </div>
</template>