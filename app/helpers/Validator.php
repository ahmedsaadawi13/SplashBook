<?php
// FILE: /app/helpers/Validator.php

/**
 * Validator - Input validation helper
 *
 * Provides common validation rules for form inputs
 * with beginner-friendly error messages.
 */
class Validator
{
    /**
     * Validate data against rules
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array Validation errors (empty if valid)
     */
    public static function validate($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = isset($data[$field]) ? $data[$field] : null;

            foreach ($fieldRules as $rule) {
                // Parse rule and parameters
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramString) = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                }

                // Apply validation rule
                $error = self::applyRule($field, $value, $rule, $params, $data);

                if ($error) {
                    if (!isset($errors[$field])) {
                        $errors[$field] = [];
                    }
                    $errors[$field][] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * Apply a single validation rule
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Rule name
     * @param array $params Rule parameters
     * @param array $allData All data (for confirmation matching)
     * @return string|null Error message or null if valid
     */
    private static function applyRule($field, $value, $rule, $params, $allData)
    {
        $fieldName = ucfirst(str_replace('_', ' ', $field));

        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    return "$fieldName is required";
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "$fieldName must be a valid email address";
                }
                break;

            case 'min':
                $min = $params[0];
                if (!empty($value) && strlen($value) < $min) {
                    return "$fieldName must be at least $min characters";
                }
                break;

            case 'max':
                $max = $params[0];
                if (!empty($value) && strlen($value) > $max) {
                    return "$fieldName must not exceed $max characters";
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    return "$fieldName must be a number";
                }
                break;

            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    return "$fieldName must be an integer";
                }
                break;

            case 'alpha':
                if (!empty($value) && !preg_match('/^[a-zA-Z]+$/', $value)) {
                    return "$fieldName must contain only letters";
                }
                break;

            case 'alphanumeric':
                if (!empty($value) && !preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                    return "$fieldName must contain only letters and numbers";
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if (!empty($value) && (!isset($allData[$confirmField]) || $value !== $allData[$confirmField])) {
                    return "$fieldName confirmation does not match";
                }
                break;

            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return "$fieldName must be a valid URL";
                }
                break;

            case 'date':
                if (!empty($value) && !strtotime($value)) {
                    return "$fieldName must be a valid date";
                }
                break;

            case 'in':
                if (!empty($value) && !in_array($value, $params)) {
                    return "$fieldName must be one of: " . implode(', ', $params);
                }
                break;

            case 'unique':
                // Format: unique:table,column,exceptId
                $table = $params[0];
                $column = isset($params[1]) ? $params[1] : $field;
                $exceptId = isset($params[2]) ? $params[2] : null;

                if (!empty($value)) {
                    $db = Database::getInstance();
                    $sql = "SELECT COUNT(*) as count FROM `$table` WHERE `$column` = ?";
                    $sqlParams = [$value];

                    if ($exceptId) {
                        $sql .= " AND id != ?";
                        $sqlParams[] = $exceptId;
                    }

                    $result = $db->queryOne($sql, $sqlParams);
                    if ($result['count'] > 0) {
                        return "$fieldName already exists";
                    }
                }
                break;

            case 'phone':
                if (!empty($value) && !preg_match('/^[\d\s\-\+\(\)]+$/', $value)) {
                    return "$fieldName must be a valid phone number";
                }
                break;

            case 'timezone':
                if (!empty($value) && !in_array($value, timezone_identifiers_list())) {
                    return "$fieldName must be a valid timezone";
                }
                break;
        }

        return null;
    }

    /**
     * Check if validation errors exist
     *
     * @param array $errors Validation errors
     * @return bool
     */
    public static function hasErrors($errors)
    {
        return !empty($errors);
    }

    /**
     * Get first error message for a field
     *
     * @param array $errors Validation errors
     * @param string $field Field name
     * @return string|null First error message
     */
    public static function first($errors, $field)
    {
        return isset($errors[$field][0]) ? $errors[$field][0] : null;
    }
}
