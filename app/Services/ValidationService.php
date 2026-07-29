<?php

/**
 * ValidationService - Input Validation Service
 * 
 * Provides fluent validation for input data with reusable rules.
 * Supports common validation rules and custom validators.
 * 
 * @package App\Services
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\ValidationException;

class ValidationService
{
    /**
     * Data to validate
     * @var array
     */
    private array $data = [];

    /**
     * Validation rules
     * @var array
     */
    private array $rules = [];

    /**
     * Validation errors
     * @var array
     */
    private array $errors = [];

    /**
     * Validated data
     * @var array
     */
    private array $validated = [];

    /**
     * Custom error messages
     * @var array
     */
    private array $customMessages = [];

    /**
     * Create validator instance
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return self
     */
    public static function make(array $data, array $rules): self
    {
        $validator = new self();
        $validator->data = $data;
        $validator->rules = $rules;
        return $validator;
    }

    /**
     * Set custom error messages
     * 
     * @param array $messages Custom messages
     * @return self
     */
    public function messages(array $messages): self
    {
        $this->customMessages = $messages;
        return $this;
    }

    /**
     * Run validation
     * 
     * @return self
     */
    public function validate(): self
    {
        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $rules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }

        return $this;
    }

    /**
     * Get validated data
     * 
     * @return array
     * @throws ValidationException If validation fails
     */
    public function validated(): array
    {
        if (!empty($this->errors)) {
            throw new ValidationException('Validation failed', $this->errors);
        }

        return $this->validated;
    }

    /**
     * Check if validation passed
     * 
     * @return bool
     */
    public function passes(): bool
    {
        $this->validate();
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     * 
     * @return bool
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Get errors
     * 
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error for field
     * 
     * @param string $field Field name
     * @return string|null
     */
    public function first(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Apply validation rule
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Rule to apply
     * @return void
     */
    private function applyRule(string $field, mixed $value, string $rule): void
    {
        // Parse rule and parameters
        $params = [];
        if (strpos($rule, ':') !== false) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        $method = 'validate' . ucfirst($rule);

        if (method_exists($this, $method)) {
            $error = $this->$method($field, $value, $params);
            if ($error !== null && !isset($this->errors[$field])) {
                $this->errors[$field] = $error;
            }
        }
    }

    /**
     * Get error message
     * 
     * @param string $field Field name
     * @param string $rule Rule name
     * @param array $params Rule parameters
     * @return string
     */
    private function getMessage(string $field, string $rule, array $params = []): string
    {
        // Check for custom message
        $key = "{$field}.{$rule}";
        if (isset($this->customMessages[$key])) {
            return $this->customMessages[$key];
        }

        // Default messages
        $messages = [
            'required' => "The {$field} field is required.",
            'email' => "The {$field} must be a valid email address.",
            'min' => "The {$field} must be at least {$params[0]} characters.",
            'max' => "The {$field} may not be greater than {$params[0]} characters.",
            'numeric' => "The {$field} must be a number.",
            'integer' => "The {$field} must be an integer.",
            'in' => "The selected {$field} is invalid.",
            'date' => "The {$field} must be a valid date.",
            'url' => "The {$field} must be a valid URL.",
            'alpha' => "The {$field} may only contain letters.",
            'alpha_num' => "The {$field} may only contain letters and numbers.",
            'array' => "The {$field} must be an array.",
            'unique' => "The {$field} has already been taken.",
            'exists' => "The selected {$field} does not exist.",
            'same' => "The {$field} and {$params[0]} must match.",
            'regex' => "The {$field} format is invalid."
        ];

        return $messages[$rule] ?? "Validation failed for {$field}.";
    }
    
    // ==========================================
    // Validation Rules
    // ==========================================

    /**
     * Required validation
     */
    private function validateRequired(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return $this->getMessage($field, 'required');
        }
        return null;
    }

    /**
     * Email validation
     */
    private function validateEmail(string $field, mixed $value): ?string
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->getMessage($field, 'email');
        }
        return null;
    }

    /**
     * Minimum length/value validation
     */
    private function validateMin(string $field, mixed $value, array $params): ?string
    {
        if (!$value) return null;

        $min = (int)($params[0] ?? 0);

        if (is_string($value) && strlen($value) < $min) {
            return $this->getMessage($field, 'min', $params);
        }
        if (is_numeric($value) && $value < $min) {
            return $this->getMessage($field, 'min', $params);
        }
        if (is_array($value) && count($value) < $min) {
            return $this->getMessage($field, 'min', $params);
        }

        return null;
    }

    /**
     * Maximum length/value validation
     */
    private function validateMax(string $field, mixed $value, array $params): ?string
    {
        if (!$value) return null;

        $max = (int)($params[0] ?? 0);

        if (is_string($value) && strlen($value) > $max) {
            return $this->getMessage($field, 'max', $params);
        }
        if (is_numeric($value) && $value > $max) {
            return $this->getMessage($field, 'max', $params);
        }
        if (is_array($value) && count($value) > $max) {
            return $this->getMessage($field, 'max', $params);
        }

        return null;
    }

    /**
     * Numeric validation
     */
    private function validateNumeric(string $field, mixed $value): ?string
    {
        if ($value && !is_numeric($value)) {
            return $this->getMessage($field, 'numeric');
        }
        return null;
    }

    /**
     * Integer validation
     */
    private function validateInteger(string $field, mixed $value): ?string
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
            return $this->getMessage($field, 'integer');
        }
        return null;
    }

    /**
     * In list validation
     */
    private function validateIn(string $field, mixed $value, array $params): ?string
    {
        if ($value && !in_array($value, $params)) {
            return $this->getMessage($field, 'in', $params);
        }
        return null;
    }

    /**
     * Date validation
     */
    private function validateDate(string $field, mixed $value): ?string
    {
        if ($value && !strtotime($value)) {
            return $this->getMessage($field, 'date');
        }
        return null;
    }

    /**
     * URL validation
     */
    private function validateUrl(string $field, mixed $value): ?string
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
            return $this->getMessage($field, 'url');
        }
        return null;
    }

    /**
     * Alpha validation
     */
    private function validateAlpha(string $field, mixed $value): ?string
    {
        if ($value && !preg_match('/^[a-zA-Z]+$/', $value)) {
            return $this->getMessage($field, 'alpha');
        }
        return null;
    }

    /**
     * Alpha-numeric validation
     */
    private function validateAlphaNum(string $field, mixed $value): ?string
    {
        if ($value && !preg_match('/^[a-zA-Z0-9]+$/', $value)) {
            return $this->getMessage($field, 'alpha_num');
        }
        return null;
    }

    /**
     * Array validation
     */
    private function validateArray(string $field, mixed $value): ?string
    {
        if ($value && !is_array($value)) {
            return $this->getMessage($field, 'array');
        }
        return null;
    }

    /**
     * Unique validation (database or Google Sheets)
     */
    private function validateUnique(string $field, mixed $value, array $params): ?string
    {
        if (!$value) return null;

        [$table, $column] = $params;
        $column = $column ?? $field;

        // Auth tables use MySQL
        if ($this->isAuthTable($table)) {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?", [$value]);
            if ($stmt->fetchColumn() > 0) {
                return $this->getMessage($field, 'unique');
            }
            return null;
        }

        // Certificate/learner data uses Google Sheets validation
        return $this->validateUniqueViaSheets($table, $column, $value, $field);
    }

    /**
     * Exists validation (database or Google Sheets)
     */
    private function validateExists(string $field, mixed $value, array $params): ?string
    {
        if (!$value) return null;

        [$table, $column] = $params;
        $column = $column ?? $field;

        // Auth tables use MySQL
        if ($this->isAuthTable($table)) {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?", [$value]);
            if ($stmt->fetchColumn() === 0) {
                return $this->getMessage($field, 'exists');
            }
            return null;
        }

        // Certificate/learner data uses Google Sheets validation
        return $this->validateExistsViaSheets($table, $column, $value, $field);
    }

    private function isAuthTable(string $table): bool
    {
        return in_array($table, ['users', 'user_roles', 'roles', 'permissions', 'role_permissions', 'login_attempts']);
    }

    private function validateUniqueViaSheets(string $table, string $column, string $value, string $field): ?string
    {
        try {
            $sheetService = new GoogleSheetService();
            if ($table === 'itgk_certificate') {
                $data = $sheetService->fetchParsedSheet(
                    $sheetService->getCertificateSheetId(),
                    $sheetService->getCertificateRange()
                );
                $rows = $data['rows'] ?? [];
                foreach ($rows as $row) {
                    $val = trim((string)($row[$column] ?? ''));
                    if (strcasecmp($val, $value) === 0 && $val !== '') {
                        return $this->getMessage($field, 'unique');
                    }
                }
            } elseif ($table === 'itgk_learner_result') {
                $data = $sheetService->fetchParsedSheet(
                    $sheetService->getStudentResultSheetId(),
                    $sheetService->getStudentResultRange()
                );
                $rows = $data['rows'] ?? [];
                foreach ($rows as $row) {
                    $val = trim((string)($row[$column] ?? ''));
                    if (strcasecmp($val, $value) === 0 && $val !== '') {
                        return $this->getMessage($field, 'unique');
                    }
                }
            }
        } catch (\Throwable $e) {
            Logger::warn('Sheets validation failed', ['table' => $table, 'error' => $e->getMessage()]);
        }
        return null;
    }

    private function validateExistsViaSheets(string $table, string $column, string $value, string $field): ?string
    {
        try {
            $sheetService = new GoogleSheetService();
            if ($table === 'itgk_certificate') {
                $data = $sheetService->fetchParsedSheet(
                    $sheetService->getCertificateSheetId(),
                    $sheetService->getCertificateRange()
                );
                $rows = $data['rows'] ?? [];
                $found = false;
                foreach ($rows as $row) {
                    $val = trim((string)($row[$column] ?? ''));
                    if (strcasecmp($val, $value) === 0) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    return $this->getMessage($field, 'exists');
                }
            } elseif ($table === 'itgk_learner_result') {
                $data = $sheetService->fetchParsedSheet(
                    $sheetService->getStudentResultSheetId(),
                    $sheetService->getStudentResultRange()
                );
                $rows = $data['rows'] ?? [];
                $found = false;
                foreach ($rows as $row) {
                    $val = trim((string)($row[$column] ?? ''));
                    if (strcasecmp($val, $value) === 0) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    return $this->getMessage($field, 'exists');
                }
            }
        } catch (\Throwable $e) {
            Logger::warn('Sheets validation failed', ['table' => $table, 'error' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Same validation (field matching)
     */
    private function validateSame(string $field, mixed $value, array $params): ?string
    {
        $otherField = $params[0] ?? '';
        $otherValue = $this->data[$otherField] ?? null;

        if ($value !== $otherValue) {
            return $this->getMessage($field, 'same', $params);
        }

        return null;
    }

    /**
     * Regex validation
     */
    private function validateRegex(string $field, mixed $value, array $params): ?string
    {
        if (!$value) return null;

        $pattern = $params[0] ?? '';

        if (!preg_match($pattern, $value)) {
            return $this->getMessage($field, 'regex');
        }

        return null;
    }

    /**
     * Nullable validation (allows null/empty)
     */
    private function validateNullable(string $field, mixed $value): ?string
    {
        // This rule just marks the field as nullable
        // No error is generated
        return null;
    }

    /**
     * Sometimes validation (only validate if present)
     */
    private function validateSometimes(string $field, mixed $value): ?string
    {
        // This rule just marks the field as optional
        // No error is generated
        return null;
    }
}
