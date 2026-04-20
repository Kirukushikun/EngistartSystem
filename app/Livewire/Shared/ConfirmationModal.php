<?php

namespace App\Livewire\Shared;

use Livewire\Component;

class ConfirmationModal extends Component
{
    protected $listeners = [
        'openConfirmationModal' => 'open',
        'closeConfirmationModal' => 'close',
    ];

    public bool $isOpen = false;

    public string $title = 'Confirm action';

    public string $message = 'Are you sure you want to continue?';

    public string $tone = 'info';

    public string $confirmText = 'Confirm';

    public string $cancelText = 'Cancel';

    public string $confirmEvent = 'confirmationConfirmed';

    public ?string $confirmTarget = null;

    public array $payload = [];

    public array $fields = [];

    public array $summary = [];

    public array $values = [];

    public function open(array $config = []): void
    {
        $this->title = (string) ($config['title'] ?? 'Confirm action');
        $this->message = (string) ($config['message'] ?? 'Are you sure you want to continue?');
        $this->tone = (string) ($config['tone'] ?? 'info');
        $this->confirmText = (string) ($config['confirmText'] ?? 'Confirm');
        $this->cancelText = (string) ($config['cancelText'] ?? 'Cancel');
        $this->confirmEvent = (string) ($config['confirmEvent'] ?? 'confirmationConfirmed');
        $this->confirmTarget = isset($config['confirmTarget']) ? (string) $config['confirmTarget'] : null;
        $this->payload = is_array($config['payload'] ?? null) ? $config['payload'] : [];
        $this->fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];
        $this->summary = is_array($config['summary'] ?? null) ? $config['summary'] : [];
        $this->values = [];

        foreach ($this->fields as $field) {
            $name = (string) ($field['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $this->values[$name] = $field['value'] ?? '';
        }

        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->reset([
            'isOpen',
            'fields',
            'summary',
            'values',
            'payload',
        ]);

        $this->title = 'Confirm action';
        $this->message = 'Are you sure you want to continue?';
        $this->tone = 'info';
        $this->confirmText = 'Confirm';
        $this->cancelText = 'Cancel';
        $this->confirmEvent = 'confirmationConfirmed';
        $this->confirmTarget = null;
    }

    public function confirm(): void
    {
        foreach ($this->fields as $field) {
            $name = (string) ($field['name'] ?? '');
            $required = (bool) ($field['required'] ?? false);

            if ($required && trim((string) ($this->values[$name] ?? '')) === '') {
                $label = (string) ($field['label'] ?? 'This field');
                $this->addError('values.' . $name, $label . ' is required.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        if ($this->confirmTarget) {
            $this->dispatch($this->confirmEvent, payload: $this->payload, values: $this->values)
                ->to($this->confirmTarget);
        } else {
            $this->dispatch($this->confirmEvent, payload: $this->payload, values: $this->values);
        }

        $this->close();
    }

    public function render()
    {
        return view('livewire.shared.confirmation-modal');
    }
}
