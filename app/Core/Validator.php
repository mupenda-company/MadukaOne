<?php

declare(strict_types=1);

final class Validator
{
    private array $data;

    /**
     * @var array<string, array<int, string>>
     */
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function required(string $field, ?string $label = null): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || trim((string) $value) === '') {
            $this->addError($field, ($label ?? $field) . ' est obligatoire.');
        }

        return $this;
    }

    public function numeric(string $field, ?string $label = null): self
    {
        $value = $this->data[$field] ?? null;

        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, ($label ?? $field) . ' doit etre un nombre.');
        }

        return $this;
    }

    public function positiveOrZero(string $field, ?string $label = null): self
    {
        $value = $this->data[$field] ?? null;

        if ($value !== null && $value !== '' && (!is_numeric($value) || (float) $value < 0)) {
            $this->addError($field, ($label ?? $field) . ' doit etre positif ou egal a zero.');
        }

        return $this;
    }

    public function integerPositiveOrZero(string $field, ?string $label = null): self
    {
        $value = $this->data[$field] ?? null;

        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, ($label ?? $field) . ' doit etre un entier.');
            return $this;
        }

        if ($value !== null && $value !== '' && (int) $value < 0) {
            $this->addError($field, ($label ?? $field) . ' doit etre positif ou egal a zero.');
        }

        return $this;
    }

    public function maxLength(string $field, int $max, ?string $label = null): self
    {
        $value = (string) ($this->data[$field] ?? '');

        if ($value !== '' && strlen($value) > $max) {
            $this->addError($field, ($label ?? $field) . " ne peut pas depasser {$max} caracteres.");
        }

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
