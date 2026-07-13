<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @return array<string, mixed>|null Validated data, or null on failure
     */
    public function validate(array $data, array $rules): ?array
    {
        $this->errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $ruleList = array_filter(array_map('trim', explode('|', $ruleString)));

            foreach ($ruleList as $rule) {
                if ($rule === 'nullable' && ($value === null || $value === '')) {
                    $validated[$field] = null;
                    continue 2;
                }

                if ($rule === 'required') {
                    if ($value === null || (is_string($value) && trim($value) === '')) {
                        $this->addError($field, 'This field is required.');
                    }
                    continue;
                }

                if (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if (is_string($value) && strlen($value) < $min) {
                        $this->addError($field, "Must be at least {$min} characters.");
                    }
                    continue;
                }

                if (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (is_string($value) && strlen($value) > $max) {
                        $this->addError($field, "Must be at most {$max} characters.");
                    }
                    continue;
                }

                if ($rule === 'email') {
                    if ($value !== null && $value !== '' && !filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                        $this->addError($field, 'Enter a valid email address.');
                    }
                    continue;
                }

                if ($rule === 'int') {
                    if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                        $this->addError($field, 'Must be an integer.');
                    } elseif ($value !== null && $value !== '') {
                        $value = (int) $value;
                    }
                    continue;
                }

                if ($rule === 'date') {
                    if ($value !== null && $value !== '') {
                        $dt = \DateTime::createFromFormat('Y-m-d', (string) $value);
                        if (!$dt || $dt->format('Y-m-d') !== (string) $value) {
                            $this->addError($field, 'Enter a valid date (YYYY-MM-DD).');
                        }
                    }
                    continue;
                }
            }

            if (!isset($this->errors[$field])) {
                $validated[$field] = is_string($value) ? trim($value) : $value;
            }
        }

        return $this->errors === [] ? $validated : null;
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string, string> */
    public function firstErrors(): array
    {
        $first = [];
        foreach ($this->errors as $field => $messages) {
            $first[$field] = $messages[0] ?? '';
        }

        return $first;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
