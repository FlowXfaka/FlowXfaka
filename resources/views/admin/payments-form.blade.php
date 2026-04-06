@extends('admin.layout')

@push('head')
<style>
.admin-payment-editor-card {
    gap: 1.35rem;
}

.admin-payment-editor-form {
    gap: 1.25rem;
}

.admin-payment-grid {
    align-items: start;
}

.admin-form-field--readonly {
    align-self: start;
}

.admin-provider-config-group {
    display: none;
}

.admin-provider-config-group.is-active {
    display: contents;
}

.admin-payment-choice.is-hidden {
    display: none;
}

.admin-readonly-box--field {
    min-height: 2.95rem;
    display: flex;
    align-items: center;
    padding: 0 1rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(27, 36, 48, 0.1);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.92);
}

.admin-readonly-box--field p {
    color: var(--ink);
    font-size: 1rem;
    font-weight: 700;
    line-height: 1;
    word-break: normal;
}

.admin-form-field__hint {
    display: block;
    margin-top: 0.45rem;
    color: rgba(27, 36, 48, 0.62);
    font-size: 0.84rem;
    line-height: 1.55;
}

.admin-radio-row--choices {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(9rem, max-content));
    gap: 0.85rem;
}

.admin-radio-pill--choice {
    position: relative;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    align-items: center;
    gap: 0.85rem;
    min-height: 4rem;
    padding: 0.9rem 1rem;
    border: 1px solid rgba(27, 36, 48, 0.1);
    border-radius: 1.15rem;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 249, 253, 0.9));
    box-shadow: 0 0.85rem 2rem rgba(15, 23, 42, 0.06);
    cursor: pointer;
    transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
}

.admin-radio-pill--choice:hover {
    transform: translateY(-0.08rem);
    border-color: rgba(10, 132, 255, 0.2);
}

.admin-radio-pill--choice.is-selected,
.admin-radio-pill--choice:has(input:checked) {
    border-color: rgba(10, 132, 255, 0.28);
    background: linear-gradient(180deg, rgba(10, 132, 255, 0.12), rgba(255, 255, 255, 0.98));
    box-shadow: 0 1rem 2.2rem rgba(10, 132, 255, 0.12);
}

.admin-radio-pill--choice input {
    position: absolute;
    inset: 0;
    opacity: 0;
    pointer-events: none;
}

.admin-radio-pill__dot {
    width: 1.45rem;
    height: 1.45rem;
    position: relative;
    border-radius: 999rem;
    border: 1.5px solid rgba(15, 23, 42, 0.25);
    background: #fff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.95);
}

.admin-radio-pill__dot::after {
    content: "";
    position: absolute;
    inset: 0.23rem;
    border-radius: 50%;
    background: linear-gradient(180deg, #1d8bff 0%, #0a6ad7 100%);
    transform: scale(0);
    transition: transform 0.18s ease;
}

.admin-radio-pill--choice.is-selected .admin-radio-pill__dot,
.admin-radio-pill--choice:has(input:checked) .admin-radio-pill__dot {
    border-color: rgba(10, 132, 255, 0.55);
}

.admin-radio-pill--choice.is-selected .admin-radio-pill__dot::after,
.admin-radio-pill--choice:has(input:checked) .admin-radio-pill__dot::after {
    transform: scale(1);
}

.admin-radio-pill__label {
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    color: var(--ink);
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: 0.01em;
}

.admin-checkbox-row--toggle {
    width: fit-content;
    min-height: 3.35rem;
    padding: 0.35rem 1rem 0.35rem 0.45rem;
    gap: 0.8rem;
    border-radius: 1.2rem;
    border-color: rgba(27, 36, 48, 0.1);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 249, 253, 0.92));
    box-shadow: 0 0.8rem 1.9rem rgba(15, 23, 42, 0.05);
}

.admin-checkbox-row--toggle input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.admin-checkbox-row__switch {
    width: 3.35rem;
    height: 2rem;
    position: relative;
    flex: 0 0 auto;
    border-radius: 999rem;
    background: rgba(148, 163, 184, 0.3);
    box-shadow: inset 0 0 0 1px rgba(27, 36, 48, 0.08);
    transition: background 0.18s ease;
}

.admin-checkbox-row__switch::after {
    content: "";
    position: absolute;
    top: 0.18rem;
    left: 0.18rem;
    width: 1.64rem;
    height: 1.64rem;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 0.35rem 0.85rem rgba(15, 23, 42, 0.16);
    transition: transform 0.18s ease;
}

.admin-checkbox-row--toggle.is-selected .admin-checkbox-row__switch,
.admin-checkbox-row--toggle:has(input:checked) .admin-checkbox-row__switch {
    background: linear-gradient(180deg, #1d8bff 0%, #0a6ad7 100%);
}

.admin-checkbox-row--toggle.is-selected .admin-checkbox-row__switch::after,
.admin-checkbox-row--toggle:has(input:checked) .admin-checkbox-row__switch::after {
    transform: translateX(1.35rem);
}

.admin-checkbox-row__text {
    color: var(--ink);
    font-size: 0.98rem;
    font-weight: 800;
    white-space: nowrap;
}

@media (max-width: 56rem) {
    .admin-radio-row--choices {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .admin-checkbox-row--toggle {
        width: 100%;
        justify-content: flex-start;
    }
}

@media (max-width: 40rem) {
    .admin-radio-row--choices {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@section('content')
    @php
        $providerOptions = $providerOptions ?? ['alipay' => 'Alipay'];
        $providerMetadata = is_array($providerMetadata ?? null) ? $providerMetadata : [];
        $selectedProvider = old('provider', $paymentRecord->provider ?? array_key_first($providerOptions) ?? 'alipay');
        if (! array_key_exists($selectedProvider, $providerOptions)) {
            $selectedProvider = array_key_first($providerOptions) ?? 'alipay';
        }
        $selectedProviderMeta = $providerMetadata[$selectedProvider] ?? [
            'notify' => '-',
            'return' => '-',
            'route_path' => '/payments/alipay/start',
            'fields' => [],
            'payment_scenes' => ['general'],
            'payment_methods' => ['page'],
            'default_payment_scene' => 'general',
            'default_payment_method' => 'page',
        ];
        $sceneLabels = [
            'pc' => '&#30005;&#33041; PC',
            'mobile' => '&#25163;&#26426;',
            'general' => '&#36890;&#29992;',
        ];
        $methodLabels = [
            'page' => '&#36339;&#36716;',
            'scan' => '&#25195;&#30721;',
        ];
        $currentScene = old('payment_scene', $paymentRecord->payment_scene ?? ($selectedProviderMeta['default_payment_scene'] ?? 'general'));
        $currentMethod = old('payment_method', $paymentRecord->payment_method ?? ($selectedProviderMeta['default_payment_method'] ?? 'page'));
    @endphp
    <section class="admin-stack">
        <article class="admin-card admin-form-card admin-payment-editor-card">
            <div class="admin-card-head admin-card-head--stack">
                <div>
                    <h2>{!! $mode === 'edit' ? '&#32534;&#36753;&#25903;&#20184;&#36890;&#36947;' : '&#26032;&#22686;&#25903;&#20184;&#36890;&#36947;' !!}</h2>
                </div>
                <a class="admin-button-light" href="{{ route('admin.payments') }}">&#36820;&#22238;&#21015;&#34920;</a>
            </div>

            <form method="POST" action="{{ $mode === 'edit' ? route('admin.payments.update', $paymentRecord) : route('admin.payments.store') }}" class="admin-payment-form admin-payment-editor-form" data-payment-editor-form>
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="admin-payment-grid">
                    @if ($paymentRecord)
                        <div class="admin-form-field admin-form-field--readonly">
                            <span>ID</span>
                            <div class="admin-readonly-box admin-readonly-box--field">
                                <p>{{ $paymentRecord->id }}</p>
                            </div>
                        </div>
                    @endif

                    <label class="admin-form-field" data-field-target="name">
                        <span>&#25903;&#20184;&#21517;&#31216;</span>
                        <input type="text" name="name" value="{{ old('name', $paymentRecord->name ?? '') }}" maxlength="40" required>
                    </label>

                    <label class="admin-form-field" data-field-target="provider">
                        <span>&#25903;&#20184;&#25552;&#20379;&#26041;</span>
                        <select name="provider" data-payment-provider-select required>
                            @foreach ($providerOptions as $providerKey => $providerLabel)
                                <option value="{{ $providerKey }}" {{ $selectedProvider === $providerKey ? 'selected' : '' }}>{{ $providerLabel }}</option>
                            @endforeach
                        </select>
                    </label>

                    @foreach ($providerOptions as $providerKey => $providerLabel)
                        @php
                            $providerMeta = $providerMetadata[$providerKey] ?? ['fields' => []];
                            $providerFields = $providerMeta['fields'] ?? [];
                            $isActiveProvider = $selectedProvider === $providerKey;
                        @endphp
                        <div class="admin-provider-config-group {{ $isActiveProvider ? 'is-active' : '' }}" data-provider-config-group="{{ $providerKey }}">
                            @foreach ($providerFields as $field)
                                @php
                                    $fieldKey = trim((string) ($field['key'] ?? ''));
                                    if ($fieldKey === '') {
                                        continue;
                                    }
                                    $fieldLabel = (string) ($field['label'] ?? $fieldKey);
                                    $fieldValue = old('provider_config.' . $fieldKey);
                                    if ($fieldValue === null && $paymentRecord && $paymentRecord->provider === $providerKey) {
                                        $fieldValue = $paymentRecord->configValue($fieldKey);
                                    }
                                    $fieldValue = is_string($fieldValue) ? $fieldValue : '';
                                    $fieldInput = ($field['input'] ?? 'text') === 'textarea' ? 'textarea' : 'text';
                                    $fieldRows = max(3, (int) ($field['rows'] ?? 5));
                                    $fieldMaxLength = max(0, (int) ($field['maxlength'] ?? 0));
                                    $fieldRequired = (bool) ($field['required'] ?? true);
                                    $fieldHint = trim((string) ($field['hint'] ?? ''));
                                    $fieldWrapperClass = $fieldInput === 'textarea'
                                        ? 'admin-form-field admin-form-field--full'
                                        : 'admin-form-field';
                                @endphp
                                <label class="{{ $fieldWrapperClass }}" data-field-target="provider_config.{{ $fieldKey }}">
                                    <span>{{ $fieldLabel }}</span>
                                    @if ($fieldInput === 'textarea')
                                        <textarea
                                            name="provider_config[{{ $fieldKey }}]"
                                            rows="{{ $fieldRows }}"
                                            data-provider-config-input
                                            data-required="{{ $fieldRequired ? '1' : '0' }}"
                                            {{ $isActiveProvider ? '' : 'disabled' }}
                                            {{ $fieldRequired && $isActiveProvider ? 'required' : '' }}>{{ $fieldValue }}</textarea>
                                    @else
                                        <input
                                            type="text"
                                            name="provider_config[{{ $fieldKey }}]"
                                            value="{{ $fieldValue }}"
                                            data-provider-config-input
                                            data-required="{{ $fieldRequired ? '1' : '0' }}"
                                            {{ $fieldMaxLength > 0 ? 'maxlength=' . $fieldMaxLength : '' }}
                                            {{ $isActiveProvider ? '' : 'disabled' }}
                                            {{ $fieldRequired && $isActiveProvider ? 'required' : '' }}
                                        >
                                    @endif
                                    @if ($fieldHint !== '')
                                        <small class="admin-form-field__hint">{{ $fieldHint }}</small>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    @endforeach

                    <label class="admin-form-field" data-field-target="payment_mark">
                        <span>&#25903;&#20184;&#26631;&#35782;</span>
                        <input type="text" name="payment_mark" value="{{ old('payment_mark', $paymentRecord->payment_mark ?? '') }}" maxlength="40" required>
                    </label>

                    <label class="admin-form-field" data-field-target="route_path">
                        <span>&#25903;&#20184;&#22788;&#29702;&#36335;&#30001;</span>
                        <input type="text" name="route_path" value="{{ old('route_path', $paymentRecord->route_path ?? ($selectedProviderMeta['route_path'] ?? '/payments/alipay/start')) }}" maxlength="120" required data-payment-route-path>
                    </label>

                    <div class="admin-form-field admin-form-field--full" data-field-target="payment_scene">
                        <span>&#25903;&#20184;&#22330;&#26223;</span>
                        <div class="admin-radio-row admin-radio-row--choices">
                            @foreach ($sceneLabels as $sceneValue => $sceneLabel)
                                <label class="admin-radio-pill admin-radio-pill--choice admin-payment-choice" data-payment-choice="payment_scene" data-choice-value="{{ $sceneValue }}">
                                    <input type="radio" name="payment_scene" value="{{ $sceneValue }}" {{ $currentScene === $sceneValue ? 'checked' : '' }}>
                                    <span class="admin-radio-pill__dot" aria-hidden="true"></span>
                                    <span class="admin-radio-pill__label">{!! $sceneLabel !!}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="admin-form-field admin-form-field--full" data-field-target="payment_method">
                        <span>&#25903;&#20184;&#26041;&#24335;</span>
                        <div class="admin-radio-row admin-radio-row--choices">
                            @foreach ($methodLabels as $methodValue => $methodLabel)
                                <label class="admin-radio-pill admin-radio-pill--choice admin-payment-choice" data-payment-choice="payment_method" data-choice-value="{{ $methodValue }}">
                                    <input type="radio" name="payment_method" value="{{ $methodValue }}" {{ $currentMethod === $methodValue ? 'checked' : '' }}>
                                    <span class="admin-radio-pill__dot" aria-hidden="true"></span>
                                    <span class="admin-radio-pill__label">{!! $methodLabel !!}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <label class="admin-checkbox-row admin-checkbox-row--toggle admin-form-field--full" data-field-target="is_enabled">
                        <input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $paymentRecord->is_enabled ?? true) ? 'checked' : '' }}>
                        <span class="admin-checkbox-row__switch" aria-hidden="true"></span>
                        <span class="admin-checkbox-row__text">&#26159;&#21542;&#21551;&#29992;</span>
                    </label>

                    <div class="admin-readonly-grid admin-form-field--full">
                        <div class="admin-readonly-box">
                            <strong>&#24322;&#27493;&#22238;&#35843;</strong>
                            <p data-payment-callback="notify">{{ $selectedProviderMeta['notify'] ?? '-' }}</p>
                        </div>
                        <div class="admin-readonly-box">
                            <strong>&#21516;&#27493;&#36820;&#22238;</strong>
                            <p data-payment-callback="return">{{ $selectedProviderMeta['return'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="admin-button admin-button-block">&#20445;&#23384;&#25903;&#20184;&#37197;&#32622;</button>
            </form>
        </article>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.querySelector('[data-payment-editor-form]');
    if (!form) return;

    const providerSelect = form.querySelector('[data-payment-provider-select]');
    const routePathInput = form.querySelector('[data-payment-route-path]');
    const providerMeta = @json($providerMetadata);
    const paymentFormErrors = @json($errors->getMessages());

    const syncRadioGroup = (name) => {
        form.querySelectorAll(`input[type="radio"][name="${name}"]`).forEach((input) => {
            const pill = input.closest('.admin-radio-pill--choice');
            if (!pill) return;
            pill.classList.toggle('is-selected', input.checked);
        });
    };

    const syncToggle = () => {
        form.querySelectorAll('.admin-checkbox-row--toggle input[type="checkbox"]').forEach((input) => {
            const row = input.closest('.admin-checkbox-row--toggle');
            if (!row) return;
            row.classList.toggle('is-selected', input.checked);
        });
    };

    const syncProviderFields = (providerKey) => {
        form.querySelectorAll('[data-provider-config-group]').forEach((group) => {
            const isActive = group.getAttribute('data-provider-config-group') === providerKey;
            group.classList.toggle('is-active', isActive);

            group.querySelectorAll('input, textarea, select').forEach((input) => {
                if (!(input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement || input instanceof HTMLSelectElement)) {
                    return;
                }

                input.disabled = !isActive;

                if (input.dataset.required === '1') {
                    input.required = isActive;
                }
            });
        });
    };

    const syncChoiceGroup = (name, allowedValues, fallbackValue) => {
        const normalizedAllowedValues = Array.isArray(allowedValues) ? allowedValues.map((value) => String(value)) : [];
        const inputs = Array.from(form.querySelectorAll(`input[type="radio"][name="${name}"]`));
        let selectedInput = null;

        inputs.forEach((input) => {
            const isAllowed = normalizedAllowedValues.includes(input.value);
            const pill = input.closest('.admin-payment-choice');

            input.disabled = !isAllowed;

            if (pill) {
                pill.classList.toggle('is-hidden', !isAllowed);
            }

            if (!isAllowed && input.checked) {
                input.checked = false;
            }

            if (isAllowed && input.checked) {
                selectedInput = input;
            }
        });

        if (!selectedInput) {
            const nextInput = inputs.find((input) => !input.disabled && input.value === String(fallbackValue))
                || inputs.find((input) => !input.disabled);

            if (nextInput) {
                nextInput.checked = true;
            }
        }

        syncRadioGroup(name);
    };

    const syncProviderMeta = () => {
        if (!(providerSelect instanceof HTMLSelectElement)) {
            return;
        }

        const currentMeta = providerMeta[providerSelect.value] || {};

        form.querySelectorAll('[data-payment-callback]').forEach((node) => {
            const key = node.getAttribute('data-payment-callback');
            node.textContent = currentMeta[key] || '-';
        });

        if (routePathInput instanceof HTMLInputElement) {
            const knownRoutePaths = Object.values(providerMeta)
                .map((item) => item && item.route_path ? String(item.route_path) : '')
                .filter(Boolean);

            if (!routePathInput.value || knownRoutePaths.includes(routePathInput.value)) {
                routePathInput.value = currentMeta.route_path || routePathInput.value;
            }
        }

        syncProviderFields(providerSelect.value);
        syncChoiceGroup('payment_scene', currentMeta.payment_scenes || [], currentMeta.default_payment_scene || 'general');
        syncChoiceGroup('payment_method', currentMeta.payment_methods || [], currentMeta.default_payment_method || 'page');
    };

    form.addEventListener('change', (event) => {
        const target = event.target;

        if (target === providerSelect) {
            syncProviderMeta();
            return;
        }

        if (!(target instanceof HTMLInputElement)) return;

        if (target.type === 'radio') {
            syncRadioGroup(target.name);
            return;
        }

        if (target.type === 'checkbox') {
            syncToggle();
        }
    });

    syncRadioGroup('payment_scene');
    syncRadioGroup('payment_method');
    syncToggle();
    syncProviderMeta();
    window.applyAdminFormErrors?.({
        form,
        errors: paymentFormErrors,
    });
})();
</script>
@endpush
