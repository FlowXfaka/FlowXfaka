<?php

namespace App\Payments;

use App\Models\Order;
use App\Models\PaymentChannel;
use App\Payments\Contracts\PaymentProvider;
use InvalidArgumentException;

class PaymentProviderRegistry
{
    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $providerDefinitions = null;

    /**
     * @var array<string, PaymentProvider>|null
     */
    private ?array $resolvedProviders = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        if ($this->providerDefinitions !== null) {
            return $this->providerDefinitions;
        }

        $definitions = [];

        foreach ((array) config('payments.providers', []) as $key => $definition) {
            if (! is_string($key) || trim($key) === '') {
                continue;
            }

            $normalized = $this->normalizeDefinition($definition);
            if ($normalized === null) {
                continue;
            }

            $definitions[$key] = $normalized;
        }

        $this->providerDefinitions = $definitions;

        return $this->providerDefinitions;
    }

    /**
     * @return array<string, PaymentProvider>
     */
    public function all(): array
    {
        if ($this->resolvedProviders !== null) {
            return $this->resolvedProviders;
        }

        $resolved = [];
        foreach ($this->definitions() as $definition) {
            $providerClass = (string) ($definition['class'] ?? '');
            $provider = app($providerClass);

            if (! $provider instanceof PaymentProvider) {
                throw new InvalidArgumentException(sprintf(
                    'Payment provider [%s] must implement [%s].',
                    $providerClass,
                    PaymentProvider::class,
                ));
            }

            $resolved[$provider->key()] = $provider;
        }

        $this->resolvedProviders = $resolved;

        return $this->resolvedProviders;
    }

    public function find(?string $key): ?PaymentProvider
    {
        $providerKey = trim((string) $key);

        if ($providerKey === '') {
            return null;
        }

        return $this->all()[$providerKey] ?? null;
    }

    public function forChannel(PaymentChannel $channel): ?PaymentProvider
    {
        $provider = $this->find($channel->provider);

        return $provider && $provider->supportsChannel($channel) ? $provider : null;
    }

    public function forOrder(Order $order): ?PaymentProvider
    {
        $paymentPayload = is_array($order->payment_payload) ? $order->payment_payload : [];
        $providerKey = trim((string) ($paymentPayload['selected_provider'] ?? $order->payment_channel ?? ''));

        return $this->find($providerKey);
    }

    /**
     * @return array<string, string>
     */
    public function optionMap(): array
    {
        $options = [];

        foreach ($this->all() as $provider) {
            $options[$provider->key()] = $provider->label();
        }

        return $options;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function metadata(): array
    {
        $metadata = [];

        foreach ($this->all() as $provider) {
            $definition = $this->definitions()[$provider->key()] ?? [];
            $paymentMethods = $this->normalizeChoiceList($definition['payment_methods'] ?? [], ['page']);
            $paymentScenes = $this->normalizeChoiceList($definition['payment_scenes'] ?? [], ['general']);

            $metadata[$provider->key()] = [
                ...$provider->callbackUrls(),
                'route_path' => $provider->defaultRoutePath(),
                'fields' => $definition['fields'] ?? [],
                'payment_methods' => $paymentMethods,
                'payment_scenes' => $paymentScenes,
                'default_payment_method' => in_array($definition['default_payment_method'] ?? null, $paymentMethods, true)
                    ? (string) $definition['default_payment_method']
                    : $paymentMethods[0],
                'default_payment_scene' => in_array($definition['default_payment_scene'] ?? null, $paymentScenes, true)
                    ? (string) $definition['default_payment_scene']
                    : $paymentScenes[0],
            ];
        }

        return $metadata;
    }

    /**
     * @param  mixed  $definition
     * @return array<string, mixed>|null
     */
    private function normalizeDefinition($definition): ?array
    {
        if (is_string($definition)) {
            $definition = ['class' => $definition];
        }

        if (! is_array($definition)) {
            return null;
        }

        $providerClass = trim((string) ($definition['class'] ?? ''));

        if ($providerClass === '') {
            return null;
        }

        $fields = [];

        foreach ((array) ($definition['fields'] ?? []) as $field) {
            if (! is_array($field)) {
                continue;
            }

            $fieldKey = trim((string) ($field['key'] ?? ''));
            if ($fieldKey === '') {
                continue;
            }

            $fields[] = [
                'key' => $fieldKey,
                'label' => trim((string) ($field['label'] ?? $fieldKey)),
                'input' => ($field['input'] ?? 'text') === 'textarea' ? 'textarea' : 'text',
                'rows' => max(3, (int) ($field['rows'] ?? 5)),
                'maxlength' => max(0, (int) ($field['maxlength'] ?? 0)),
                'required' => array_key_exists('required', $field) ? (bool) $field['required'] : true,
                'secret' => (bool) ($field['secret'] ?? false),
                'hint' => trim((string) ($field['hint'] ?? '')),
            ];
        }

        return [
            'class' => $providerClass,
            'fields' => $fields,
            'payment_methods' => $this->normalizeChoiceList($definition['payment_methods'] ?? [], ['page']),
            'payment_scenes' => $this->normalizeChoiceList($definition['payment_scenes'] ?? [], ['general']),
            'default_payment_method' => trim((string) ($definition['default_payment_method'] ?? '')),
            'default_payment_scene' => trim((string) ($definition['default_payment_scene'] ?? '')),
        ];
    }

    /**
     * @param  mixed  $choices
     * @param  array<int, string>  $fallback
     * @return array<int, string>
     */
    private function normalizeChoiceList($choices, array $fallback): array
    {
        $normalized = [];

        foreach ((array) $choices as $choice) {
            $value = trim((string) $choice);

            if ($value === '' || in_array($value, $normalized, true)) {
                continue;
            }

            $normalized[] = $value;
        }

        return $normalized !== [] ? $normalized : $fallback;
    }
}
