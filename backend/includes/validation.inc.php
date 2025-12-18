<?php
/**
 * Input Validation and Sanitization Functions
 */

/**
 * Sanitize string input
 */
function sanitizeString($input) {
    if (is_null($input)) {
        return null;
    }
    
    // Don't sanitize arrays as strings
    if (is_array($input)) {
        return $input;
    }
    
    // Convert to string if not already
    if (!is_string($input)) {
        $input = (string) $input;
    }
    
    // Remove whitespace
    $input = trim($input);
    
    // Remove null bytes
    $input = str_replace("\0", '', $input);
    
    // HTML entity encode to prevent XSS
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize email
 */
function sanitizeEmail($email) {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/**
 * Sanitize integer
 */
function sanitizeInt($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize float
 */
function sanitizeFloat($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
}

/**
 * Validate required fields
 */
function validateRequired($data, $fields) {
    $missing = [];
    
    foreach ($fields as $field) {
        if (!isset($data[$field])) {
            $missing[] = $field;
            continue;
        }
        
        $value = $data[$field];
        
        // Handle arrays
        if (is_array($value)) {
            if (empty($value)) {
                $missing[] = $field;
            }
            continue;
        }
        
        // Handle strings and other types
        if (trim((string) $value) === '') {
            $missing[] = $field;
        }
    }
    
    return empty($missing) ? true : $missing;
}

/**
 * Validate and sanitize input array
 */
function sanitizeInput($data, $rules) {
    $sanitized = [];
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        $fieldType = $rule['type'] ?? 'string';
        
        // Check required
        if (isset($rule['required']) && $rule['required']) {
            if ($value === null || $value === '') {
                $errors[] = "$field is required";
                continue;
            }
            // For arrays, check if empty
            if (is_array($value) && empty($value)) {
                $errors[] = "$field is required";
                continue;
            }
        }
        
        // Skip if not required and value is empty (but handle arrays separately)
        if ($fieldType === 'array') {
            // For arrays, skip validation if not required and null/empty
            if (($value === null || (is_array($value) && empty($value))) && !isset($rule['required'])) {
                $sanitized[$field] = $rule['default'] ?? [];
                continue;
            }
        } else {
            // For non-arrays, check if empty string
            if ($value === null || (!is_array($value) && trim($value) === '')) {
                $sanitized[$field] = $rule['default'] ?? null;
                continue;
            }
        }
        
        // Sanitize based on type
        switch ($fieldType) {
            case 'email':
                $sanitized[$field] = sanitizeEmail($value);
                if ($sanitized[$field] === false) {
                    $errors[] = "$field is not a valid email";
                }
                break;
                
            case 'int':
                $sanitized[$field] = (int) sanitizeInt($value);
                if (isset($rule['min']) && $sanitized[$field] < $rule['min']) {
                    $errors[] = "$field must be at least {$rule['min']}";
                }
                if (isset($rule['max']) && $sanitized[$field] > $rule['max']) {
                    $errors[] = "$field must be at most {$rule['max']}";
                }
                break;
                
            case 'float':
                $sanitized[$field] = (float) sanitizeFloat($value);
                if (isset($rule['min']) && $sanitized[$field] < $rule['min']) {
                    $errors[] = "$field must be at least {$rule['min']}";
                }
                if (isset($rule['max']) && $sanitized[$field] > $rule['max']) {
                    $errors[] = "$field must be at most {$rule['max']}";
                }
                break;
                
            case 'array':
                // For arrays, validate that it's actually an array
                if (!is_array($value)) {
                    $errors[] = "$field must be an array";
                    break;
                }
                // Store array as-is (can be sanitized later if needed)
                $sanitized[$field] = $value;
                
                // Validate array structure if validator is provided
                if (isset($rule['validate']) && is_callable($rule['validate'])) {
                    $result = $rule['validate']($sanitized[$field]);
                    if ($result !== true) {
                        $errors[] = is_string($result) ? $result : "$field validation failed";
                    }
                }
                break;
                
            case 'string':
            default:
                // Ensure value is not an array for string type
                if (is_array($value)) {
                    $errors[] = "$field must be a string, not an array";
                    break;
                }
                $sanitized[$field] = sanitizeString($value);
                if (isset($rule['min_length']) && strlen($sanitized[$field]) < $rule['min_length']) {
                    $errors[] = "$field must be at least {$rule['min_length']} characters";
                }
                if (isset($rule['max_length']) && strlen($sanitized[$field]) > $rule['max_length']) {
                    $errors[] = "$field must be at most {$rule['max_length']} characters";
                }
                break;
        }
        
        // Custom validation
        if (isset($rule['validate']) && is_callable($rule['validate'])) {
            $result = $rule['validate']($sanitized[$field]);
            if ($result !== true) {
                $errors[] = $result;
            }
        }
    }
    
    return [
        'data' => $sanitized,
        'errors' => $errors
    ];
}

/**
 * Escape output for HTML
 */
function escapeHtml($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedTypes = null, $maxSize = null) {
    $errors = [];
    
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = 'Invalid file upload';
        return ['valid' => false, 'errors' => $errors];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error: ' . $file['error'];
        return ['valid' => false, 'errors' => $errors];
    }
    
    if ($maxSize && $file['size'] > $maxSize) {
        $errors[] = 'File size exceeds maximum allowed size';
    }
    
    if ($allowedTypes) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'File type not allowed';
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

