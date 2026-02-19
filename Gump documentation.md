# GUMP.php - AI Agent Documentation

## Overview
**File**: GUMP.php  
**Type**: PHP Input Validation and Sanitization Library  
**Version**: 1.5  
**Authors**: Sean Nieuwoudt, Filis Futsarov  
**Purpose**: Fast, extensible input validation and filtering for form data with extensive built-in rules and custom rule support.  
**Pattern**: Singleton + Fluent Interface

---

## Class Definition

```php
class GUMP
```

**Design Pattern**: Singleton (with `get_instance()` method)  
**Architecture**: Filter-then-Validate pipeline  
**Extensibility**: Custom validators and filters via callbacks

---

## Core Concepts

### Validation vs Filtering

| Concept | Purpose | Returns | Side Effects |
|---------|---------|---------|--------------|
| **Filtering** | Transform/sanitize data | Modified data array | Changes input values |
| **Validation** | Check data correctness | Boolean or error array | No data modification |

**Workflow**: Always filter first, then validate
```php
$filtered = $gump->filter($data, $filter_rules);
$valid = $gump->validate($filtered, $validation_rules);
```

---

## Properties Reference

### Instance Properties

| Property | Type | Visibility | Description |
|----------|------|------------|-------------|
| `$instance` | GUMP | protected static | Singleton instance |
| `$validation_rules` | array | protected | Rules for validation |
| `$filter_rules` | array | protected | Rules for filtering |
| `$errors` | array | protected | Validation errors from last run |
| `$fields` | array | protected static | Human-readable field names |
| `$validation_methods` | array | protected static | Custom validation callbacks |
| `$validation_methods_errors` | array | protected static | Custom error messages |
| `$filter_methods` | array | protected static | Custom filter callbacks |
| `$lang` | string | protected | Language code (default: 'en') |
| `$fieldCharsToRemove` | array | protected | Characters to remove from field names in errors |

### Public Static Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$basic_tags` | string | `<br><p><a>...` | Allowed HTML tags for basic_tags filter |
| `$en_noise_words` | string | "about,after,all..." | English noise words for removal |

---

## Constructor

### `__construct($lang = 'en')`

**Purpose**: Initialize GUMP with language support

**Parameters**:
- `$lang` (string): Language code for error messages (default: 'en')

**Behavior**:
- Looks for language file in `lang/{$lang}.php`
- Throws exception if language file doesn't exist

**AI Agent Usage**:
```php
$gump = new GUMP('en');  // English messages
$gump = new GUMP('es');  // Spanish messages (if lang/es.php exists)
```

---

## Static Factory Methods

### `get_instance()` : GUMP

**Purpose**: Get singleton instance

**Returns**: GUMP instance (creates if doesn't exist)

**AI Agent Usage**:
```php
$gump = GUMP::get_instance();
// Always returns same instance
```

---

### `is_valid(array $data, array $validators)` : mixed

**Purpose**: Quick inline validation without instantiation

**Parameters**:
- `$data` (array): Data to validate
- `$validators` (array): Validation rules

**Returns**: 
- `true` if valid
- Array of human-readable error messages if invalid

**AI Agent Usage**:
```php
$data = ['email' => 'test@example.com', 'age' => '25'];
$rules = [
    'email' => 'required|valid_email',
    'age' => 'required|numeric|min_numeric,18'
];

$result = GUMP::is_valid($data, $rules);
if($result === true) {
    // Valid
} else {
    // $result contains error messages
    foreach($result as $error) {
        echo $error;
    }
}
```

---

### `filter_input(array $data, array $filters)` : array

**Purpose**: Quick inline filtering without instantiation

**Parameters**:
- `$data` (array): Data to filter
- `$filters` (array): Filter rules

**Returns**: Filtered data array

**AI Agent Usage**:
```php
$data = ['email' => ' TEST@EXAMPLE.COM ', 'name' => '  John  '];
$filters = [
    'email' => 'trim|lower_case',
    'name' => 'trim'
];

$filtered = GUMP::filter_input($data, $filters);
// Result: ['email' => 'test@example.com', 'name' => 'John']
```

---

## Core Validation Methods

### `run(array $data, $check_fields = false)` : mixed

**Purpose**: Execute full filter + validation pipeline

**Parameters**:
- `$data` (array): Input data
- `$check_fields` (bool): Check for fields without validation rules

**Returns**:
- Filtered data array if valid
- `false` if validation fails

**Process**:
1. Filters data using `$filter_rules`
2. Validates filtered data using `$validation_rules`
3. Optionally checks for unvalidated fields
4. Returns filtered data or false

**AI Agent Usage**:
```php
$gump = new GUMP();

// Set rules
$gump->validation_rules([
    'email' => 'required|valid_email',
    'password' => 'required|min_len,8'
]);

$gump->filter_rules([
    'email' => 'trim|lower_case',
    'password' => 'trim'
]);

// Run pipeline
$validated = $gump->run($_POST);

if($validated === false) {
    $errors = $gump->get_readable_errors();
    // Display errors
} else {
    // $validated contains clean, validated data
    // Safe to use in database
}
```

---

### `validate(array $input, array $ruleset)` : mixed

**Purpose**: Validate data against ruleset

**Parameters**:
- `$input` (array): Data to validate
- `$ruleset` (array): Validation rules

**Returns**:
- `true` if valid
- Array of error details if invalid

**Rule Format**: `'field' => 'rule1|rule2|rule3,param'`

**AI Agent Usage**:
```php
$rules = [
    'username' => 'required|alpha_numeric|min_len,3|max_len,20',
    'email' => 'required|valid_email',
    'age' => 'required|numeric|min_numeric,18|max_numeric,100',
    'website' => 'valid_url',  // optional field
    'terms' => 'required|contains,yes true 1'
];

$result = $gump->validate($_POST, $rules);

if($result === true) {
    // All valid
} else {
    // $result is array of errors
    foreach($result as $error) {
        echo "{$error['field']}: {$error['rule']} failed";
    }
}
```

---

### `filter(array $input, array $filterset)` : array

**Purpose**: Apply filters to data

**Parameters**:
- `$input` (array): Data to filter
- `$filterset` (array): Filter rules

**Returns**: Filtered data array

**Filter Format**: `'field' => 'filter1|filter2|filter3,param'`

**AI Agent Usage**:
```php
$filters = [
    'email' => 'trim|lower_case|sanitize_email',
    'name' => 'trim|sanitize_string',
    'description' => 'trim|basic_tags',
    'price' => 'sanitize_floats',
    'slug' => 'slug'
];

$clean_data = $gump->filter($_POST, $filters);
```

---

## Error Handling Methods

### `errors()` : array

**Purpose**: Get raw error array from last validation

**Returns**: Array of error details

**Error Structure**:
```php
[
    [
        'field' => 'email',
        'value' => 'invalid-email',
        'rule' => 'validate_valid_email',
        'param' => null
    ],
    // ...more errors
]
```

---

### `get_readable_errors($convert_to_string = false, $field_class = 'gump-field', $error_class = 'gump-error-message')` : mixed

**Purpose**: Get human-readable error messages

**Parameters**:
- `$convert_to_string` (bool): Return HTML string instead of array
- `$field_class` (string): CSS class for field names
- `$error_class` (string): CSS class for error messages

**Returns**:
- Array of error messages (if `$convert_to_string` = false)
- HTML string of errors (if `$convert_to_string` = true)

**AI Agent Usage**:
```php
// As array
$errors = $gump->get_readable_errors();
foreach($errors as $error) {
    echo "<div class='alert alert-danger'>$error</div>";
}

// As HTML string
echo $gump->get_readable_errors(true);
// Output: <span class="gump-error-message">Email is required</span>...
```

---

### `get_errors_array($convert_to_string = null)` : array

**Purpose**: Get errors with field names as keys (useful for inline field errors)

**Returns**: Associative array `['field' => 'error message']`

**AI Agent Usage**:
```php
$errors = $gump->get_errors_array();

// Display inline errors
echo '<input name="email" />';
if(isset($errors['email'])) {
    echo "<span class='error'>{$errors['email']}</span>";
}
```

---

## Helper Methods

### `validation_rules(array $rules = array())` : array

**Purpose**: Getter/Setter for validation rules

**AI Agent Usage**:
```php
// Set rules
$gump->validation_rules([
    'email' => 'required|valid_email'
]);

// Get rules
$current_rules = $gump->validation_rules();
```

---

### `filter_rules(array $rules = array())` : array

**Purpose**: Getter/Setter for filter rules

**AI Agent Usage**:
```php
// Set rules
$gump->filter_rules([
    'email' => 'trim|lower_case'
]);

// Get rules
$current_filters = $gump->filter_rules();
```

---

## Customization Methods

### `add_validator($rule, $callback, $error_message = null)` : bool (static)

**Purpose**: Add custom validation rule

**Parameters**:
- `$rule` (string): Rule name
- `$callback` (callable): Validation function
- `$error_message` (string): Error message template

**Callback Signature**: `function($field, $input, $param) : bool`
- Return `false` if validation fails
- Return nothing/null if validation passes

**AI Agent Usage**:
```php
// Custom validator: check if username is available
GUMP::add_validator('username_available', function($field, $input, $param) {
    $username = $input[$field];
    $db = get_database();
    $exists = $db->where('username', $username)->getValue('users', 'count(*)');
    
    if($exists > 0) {
        return false;  // Username taken
    }
    // Valid (available)
}, 'The {field} is already taken');

// Use the custom validator
$rules = [
    'username' => 'required|alpha_numeric|username_available'
];
```

---

### `add_filter($rule, $callback)` : bool (static)

**Purpose**: Add custom filter

**Parameters**:
- `$rule` (string): Filter name
- `$callback` (callable): Filter function

**Callback Signature**: `function($value, $params) : mixed`
- Return transformed value

**AI Agent Usage**:
```php
// Custom filter: convert to title case
GUMP::add_filter('title_case', function($value, $params) {
    return ucwords(strtolower($value));
});

// Custom filter: mask credit card
GUMP::add_filter('mask_card', function($value, $params) {
    return str_repeat('*', 12) . substr($value, -4);
});

// Use custom filters
$filters = [
    'name' => 'title_case',
    'card_number' => 'mask_card'
];
```

---

### `set_field_name($field, $readable_name)` : void (static)

**Purpose**: Set human-readable name for field in error messages

**AI Agent Usage**:
```php
GUMP::set_field_name('email', 'Email Address');
GUMP::set_field_name('pwd', 'Password');

// Error will say "Email Address is required" instead of "Email is required"
```

---

### `set_field_names(array $array)` : void (static)

**Purpose**: Set multiple field names at once

**AI Agent Usage**:
```php
GUMP::set_field_names([
    'fname' => 'First Name',
    'lname' => 'Last Name',
    'dob' => 'Date of Birth',
    'ssn' => 'Social Security Number'
]);
```

---

### `set_error_message($rule, $message)` : void (static)

**Purpose**: Override error message for a rule

**AI Agent Usage**:
```php
GUMP::set_error_message('required', '{field} cannot be empty!');
GUMP::set_error_message('valid_email', 'Please enter a valid email for {field}');
```

---

### `set_error_messages(array $array)` : void (static)

**Purpose**: Set multiple error messages

**AI Agent Usage**:
```php
GUMP::set_error_messages([
    'required' => '{field} is mandatory',
    'valid_email' => '{field} must be a valid email address',
    'min_len' => '{field} must be at least {param} characters long'
]);
```

---

## Utility Methods

### `xss_clean(array $data)` : array (static)

**Purpose**: Sanitize array to prevent XSS attacks

**Returns**: Sanitized array

**AI Agent Usage**:
```php
$clean_data = GUMP::xss_clean($_POST);
// Applies FILTER_SANITIZE_SPECIAL_CHARS to all values
```

---

### `sanitize(array $input, array $fields = array(), $utf8_encode = true)` : array

**Purpose**: Comprehensive sanitization with UTF-8 handling

**Parameters**:
- `$input` (array): Data to sanitize
- `$fields` (array): Specific fields to sanitize (empty = all)
- `$utf8_encode` (bool): Convert to UTF-8

**Features**:
- Strips magic quotes (legacy PHP)
- Removes `\r` characters
- Converts encoding to UTF-8
- Applies FILTER_SANITIZE_SPECIAL_CHARS
- Recursive (handles nested arrays)

**AI Agent Usage**:
```php
$sanitized = $gump->sanitize($_POST);
```

---

### `field($key, array $array, $default = null)` : mixed (static)

**Purpose**: Safely extract value from array with default

**AI Agent Usage**:
```php
$email = GUMP::field('email', $_POST, '');
$age = GUMP::field('age', $_POST, 18);
```

---

## Built-in Validation Rules

### Required / Existence Rules

| Rule | Usage | Description |
|------|-------|-------------|
| `required` | `'field' => 'required'` | Field must be present and non-empty (allows 0, false) |

---

### String Length Rules

| Rule | Usage | Description | Example |
|------|-------|-------------|---------|
| `min_len` | `'field' => 'min_len,5'` | Minimum string length | At least 5 characters |
| `max_len` | `'field' => 'max_len,100'` | Maximum string length | No more than 100 chars |
| `exact_len` | `'field' => 'exact_len,10'` | Exact string length | Exactly 10 characters |

**Note**: Uses `mb_strlen()` if available for multibyte support

---

### Numeric Rules

| Rule | Usage | Description |
|------|-------|-------------|
| `numeric` | `'field' => 'numeric'` | Must be numeric |
| `integer` | `'field' => 'integer'` | Must be integer |
| `float` | `'field' => 'float'` | Must be float |
| `min_numeric` | `'field' => 'min_numeric,18'` | Minimum numeric value |
| `max_numeric` | `'field' => 'max_numeric,100'` | Maximum numeric value |

---

### Format Validation Rules

| Rule | Usage | Description |
|------|-------|-------------|
| `valid_email` | `'email' => 'valid_email'` | Valid email format |
| `valid_url` | `'website' => 'valid_url'` | Valid URL |
| `valid_ip` | `'ip' => 'valid_ip'` | Valid IP address (v4 or v6) |
| `valid_ipv4` | `'ip' => 'valid_ipv4'` | Valid IPv4 address |
| `valid_ipv6` | `'ip' => 'valid_ipv6'` | Valid IPv6 address |
| `valid_cc` | `'card' => 'valid_cc'` | Valid credit card (Luhn algorithm) |

---

### Character Type Rules

| Rule | Usage | Description |
|------|-------|-------------|
| `alpha` | `'name' => 'alpha'` | Only alphabetic characters |
| `alpha_numeric` | `'username' => 'alpha_numeric'` | Only letters and numbers |
| `alpha_dash` | `'slug' => 'alpha_dash'` | Letters, numbers, dashes, underscores |
| `alpha_space` | `'name' => 'alpha_space'` | Letters and spaces |

---

### Comparison Rules

| Rule | Usage | Description |
|------|-------|-------------|
| `equals` | `'field' => 'equals,value'` | Exact match |
| `equalsfield` | `'password_confirm' => 'equalsfield,_password'` | Match another field (use `_` prefix) |
| `contains` | `'status' => 'contains,active pending'` | Value in list (space-separated) |
| `contains_list` | `'role' => 'contains_list,admin;user;guest'` | Value in list (semicolon-separated) |
| `doesnt_contain_list` | `'username' => 'doesnt_contain_list,admin;root'` | Value NOT in list |

---

### Date/Time Rules

| Rule | Usage | Description |
|------|-------|-------------|
| `valid_date` | `'dob' => 'valid_date'` | Valid date (various formats) |
| `date_before` | `'start' => 'date_before,2025-12-31'` | Date before specified date |
| `date_after` | `'end' => 'date_after,2024-01-01'` | Date after specified date |

---

### File Validation Rules

| Rule | Usage | Description |
|------|-------|-------------|
| `required_file` | `'upload' => 'required_file'` | File upload required |
| `extension` | `'file' => 'extension,pdf;doc;docx'` | Allowed file extensions |
| `max_size` | `'file' => 'max_size,5242880'` | Max file size in bytes |

---

### Array Validation Rules

| Rule | Usage | Description |
|------|-------|-------------|
| `valid_array_size_greater` | `'items' => 'valid_array_size_greater,1'` | Array has at least N elements |
| `valid_array_size_lesser` | `'items' => 'valid_array_size_lesser,10'` | Array has at most N elements |
| `valid_array_size_equal` | `'items' => 'valid_array_size_equal,5'` | Array has exactly N elements |

---

### Boolean Rules

| Rule | Usage | Description |
|------|-------|-------------|
| `boolean` | `'active' => 'boolean'` | Valid boolean value |

---

### JSON Rules

| Rule | Usage | Description |
|------|-------|-------------|
| `valid_json_string` | `'data' => 'valid_json_string'` | Valid JSON string |

---

### Phone Number Rules

| Rule | Usage | Description |
|------|-------|-------------|
| `phone_number` | `'phone' => 'phone_number'` | Valid phone number format |

---

### Custom Pattern Rules

| Rule | Usage | Description |
|------|-------|-------------|
| `regex` | `'code' => 'regex,/^[A-Z]{3}\d{3}$/'` | Custom regex pattern |

---

### Internationalization Rules (Persian/Arabic/Pashtu)

| Rule | Usage | Description |
|------|-------|-------------|
| `valid_persian_name` | `'name' => 'valid_persian_name'` | Valid Persian/Dari/Arabic name |
| `valid_eng_per_pas_name` | `'name' => 'valid_eng_per_pas_name'` | English, Persian, or Pashtu name |
| `valid_persian_digit` | `'number' => 'valid_persian_digit'` | Persian/Arabic digits |
| `valid_persian_text` | `'text' => 'valid_persian_text'` | Persian/Dari/Arabic text |
| `valid_pashtu_text` | `'text' => 'valid_pashtu_text'` | Pashtu text |

---

## Built-in Filter Rules

### String Transformation Filters

| Filter | Usage | Description | Example |
|--------|-------|-------------|---------|
| `trim` | `'field' => 'trim'` | Remove whitespace | `" text "` → `"text"` |
| `lower_case` | `'field' => 'lower_case'` | Convert to lowercase | `"TEXT"` → `"text"` |
| `upper_case` | `'field' => 'upper_case'` | Convert to uppercase | `"text"` → `"TEXT"` |
| `slug` | `'field' => 'slug'` | Convert to URL slug | `"My Title"` → `"my-title"` |

---

### Sanitization Filters

| Filter | Usage | Description |
|--------|-------|-------------|
| `sanitize_string` | `'field' => 'sanitize_string'` | Remove script tags, sanitize HTML |
| `sanitize_email` | `'email' => 'sanitize_email'` | Remove illegal email characters |
| `sanitize_numbers` | `'field' => 'sanitize_numbers'` | Keep only numbers and `-` sign |
| `sanitize_floats` | `'price' => 'sanitize_floats'` | Keep only float-valid characters |
| `urlencode` | `'field' => 'urlencode'` | URL encode string |
| `htmlencode` | `'field' => 'htmlencode'` | HTML entity encode |

---

### Content Filters

| Filter | Usage | Description |
|--------|-------|-------------|
| `basic_tags` | `'content' => 'basic_tags'` | Keep only basic HTML tags (p, br, a, etc.) |
| `noise_words` | `'field' => 'noise_words'` | Remove English noise words |
| `rmpunctuation` | `'field' => 'rmpunctuation'` | Remove punctuation |
| `ms_word_characters` | `'field' => 'ms_word_characters'` | Convert MS Word special chars to web-safe |

---

### Numeric Filters

| Filter | Usage | Description | Example |
|--------|-------|-------------|---------|
| `whole_number` | `'field' => 'whole_number'` | Convert to integer | `"12.7"` → `12` |

---

## Common Usage Patterns

### Pattern 1: Basic Form Validation

```php
$gump = new GUMP();

$validation_rules = [
    'username' => 'required|alpha_numeric|min_len,3|max_len,20',
    'email' => 'required|valid_email',
    'password' => 'required|min_len,8',
    'password_confirm' => 'required|equalsfield,_password',
    'age' => 'required|numeric|min_numeric,18'
];

$filter_rules = [
    'username' => 'trim|lower_case',
    'email' => 'trim|lower_case|sanitize_email',
    'password' => 'trim',
    'password_confirm' => 'trim'
];

$gump->validation_rules($validation_rules);
$gump->filter_rules($filter_rules);

$validated_data = $gump->run($_POST);

if($validated_data === false) {
    $errors = $gump->get_readable_errors();
    // Display errors
} else {
    // $validated_data is clean and valid
    // Safe to insert into database
}
```

---

### Pattern 2: Quick Inline Validation

```php
$is_valid = GUMP::is_valid($_POST, [
    'email' => 'required|valid_email',
    'age' => 'numeric|min_numeric,18'
]);

if($is_valid === true) {
    // Process
} else {
    // $is_valid contains error messages
    print_r($is_valid);
}
```

---

### Pattern 3: API Input Validation

```php
function create_user($data) {
    // Custom error messages
    GUMP::set_error_messages([
        'required' => 'The {field} field is required',
        'valid_email' => '{field} must be a valid email address',
        'min_len' => '{field} must be at least {param} characters'
    ]);
    
    // Friendly field names
    GUMP::set_field_names([
        'fname' => 'First Name',
        'lname' => 'Last Name',
        'email' => 'Email Address'
    ]);
    
    $rules = [
        'fname' => 'required|alpha_space|min_len,2',
        'lname' => 'required|alpha_space|min_len,2',
        'email' => 'required|valid_email',
        'phone' => 'phone_number'
    ];
    
    $result = GUMP::is_valid($data, $rules);
    
    if($result !== true) {
        return [
            'success' => false,
            'errors' => $result
        ];
    }
    
    // Filter data
    $clean_data = GUMP::filter_input($data, [
        'fname' => 'trim|sanitize_string',
        'lname' => 'trim|sanitize_string',
        'email' => 'trim|lower_case|sanitize_email'
    ]);
    
    return [
        'success' => true,
        'data' => $clean_data
    ];
}
```

---

### Pattern 4: Custom Validators

```php
// Add custom validator for unique email
GUMP::add_validator('unique_email', function($field, $input, $param) {
    $email = $input[$field];
    $db = get_database();
    
    $count = $db->where('email', $email)->getValue('users', 'count(*)');
    
    if($count > 0) {
        return false;  // Email exists
    }
    // Valid (unique)
}, 'The {field} is already registered');

// Add validator for strong password
GUMP::add_validator('strong_password', function($field, $input, $param) {
    $password = $input[$field];
    
    // Must contain uppercase, lowercase, number, special char
    if(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        return false;
    }
}, '{field} must contain uppercase, lowercase, number, and special character');

// Use custom validators
$rules = [
    'email' => 'required|valid_email|unique_email',
    'password' => 'required|min_len,8|strong_password'
];
```

---

### Pattern 5: File Upload Validation

```php
if(isset($_FILES['avatar'])) {
    $file_data = [
        'avatar' => [
            'name' => $_FILES['avatar']['name'],
            'type' => $_FILES['avatar']['type'],
            'size' => $_FILES['avatar']['size'],
            'tmp_name' => $_FILES['avatar']['tmp_name']
        ]
    ];
    
    $rules = [
        'avatar' => 'required_file|extension,jpg;jpeg;png|max_size,2097152'  // 2MB
    ];
    
    $gump = new GUMP();
    $valid = $gump->validate($file_data, $rules);
    
    if($valid === true) {
        // Process upload
        move_uploaded_file($_FILES['avatar']['tmp_name'], 'uploads/' . $_FILES['avatar']['name']);
    } else {
        $errors = $gump->get_readable_errors();
    }
}
```

---

### Pattern 6: Multi-Step Form with Session

```php
// Step 1
if($_POST['step'] == 1) {
    $rules_step1 = [
        'email' => 'required|valid_email',
        'password' => 'required|min_len,8'
    ];
    
    $valid = GUMP::is_valid($_POST, $rules_step1);
    
    if($valid === true) {
        $_SESSION['step1_data'] = GUMP::filter_input($_POST, [
            'email' => 'trim|lower_case',
            'password' => 'trim'
        ]);
        // Redirect to step 2
    }
}

// Step 2
if($_POST['step'] == 2) {
    $rules_step2 = [
        'fname' => 'required|alpha_space',
        'lname' => 'required|alpha_space',
        'phone' => 'required|phone_number'
    ];
    
    $valid = GUMP::is_valid($_POST, $rules_step2);
    
    if($valid === true) {
        $step1_data = $_SESSION['step1_data'];
        $step2_data = GUMP::filter_input($_POST, [
            'fname' => 'trim',
            'lname' => 'trim'
        ]);
        
        $complete_data = array_merge($step1_data, $step2_data);
        // Save to database
    }
}
```

---

### Pattern 7: Conditional Validation

```php
// Validate differently based on user type
$user_type = $_POST['user_type'];

$common_rules = [
    'email' => 'required|valid_email',
    'name' => 'required|alpha_space'
];

if($user_type === 'business') {
    $rules = array_merge($common_rules, [
        'company_name' => 'required',
        'tax_id' => 'required|exact_len,9',
        'business_phone' => 'required|phone_number'
    ]);
} else {
    $rules = array_merge($common_rules, [
        'date_of_birth' => 'required|valid_date',
        'personal_phone' => 'phone_number'
    ]);
}

$gump = new GUMP();
$validated = $gump->validate($_POST, $rules);
```

---

## Rule Syntax Reference

### Basic Rule Syntax

```php
'field_name' => 'rule1|rule2|rule3'
```

### Rule with Parameter

```php
'field_name' => 'rule,parameter'
```

### Multiple Rules with Parameters

```php
'field_name' => 'required|min_len,5|max_len,20|alpha_numeric'
```

### Field Reference in Parameter

Use `_` prefix to reference another field:

```php
'password_confirm' => 'required|equalsfield,_password'
```

This checks if `password_confirm` equals the value in `password` field.

---

## Error Message Placeholders

Error messages support placeholders:

| Placeholder | Replaced With | Example |
|-------------|---------------|---------|
| `{field}` | Field name (formatted) | "Email" |
| `{param}` | Rule parameter | "8" in min_len,8 |

**Example**:
```php
GUMP::set_error_message('min_len', '{field} must be at least {param} characters long');
// Result: "Password must be at least 8 characters long"
```

---

## Language Support

### Language File Structure

Location: `lang/{language_code}.php`

**Example**: `lang/en.php`
```php
<?php
return [
    'validate_required' => 'The {field} field is required',
    'validate_valid_email' => 'The {field} field must contain a valid email address',
    'validate_min_len' => 'The {field} field needs to be at least {param} characters',
    // ... more messages
];
```

### Using Different Language

```php
$gump = new GUMP('es');  // Spanish
$gump = new GUMP('fr');  // French
$gump = new GUMP('de');  // German
```

---

## Integration with BaseController

**In BaseController.php**:

```php
function validate_form($modeldata) {
    if(!empty($this->sanitize_array)) {
        $gump = new GUMP();
        
        // Apply filters
        if(!empty($this->sanitize_array)) {
            $modeldata = $gump->filter($modeldata, $this->sanitize_array);
        }
        
        // Apply validation
        if(!empty($this->rules_array)) {
            if($this->filter_vals) {
                // Remove empty values
                $modeldata = array_filter($modeldata);
            }
            
            if($this->filter_rules) {
                // Only validate fields present in POST
                $rules = array_intersect_key($this->rules_array, $modeldata);
            } else {
                $rules = $this->rules_array;
            }
            
            $validated = $gump->validate($modeldata, $rules);
            
            if($validated !== true) {
                $this->view->page_error = $gump->get_readable_errors();
                return array();
            }
        }
        
        return $modeldata;
    }
    
    return $modeldata;
}
```

**Usage in Controller**:

```php
class UserController extends BaseController {
    function add() {
        // Set validation rules
        $this->rules_array = [
            'email' => 'required|valid_email',
            'password' => 'required|min_len,8',
            'age' => 'numeric|min_numeric,18'
        ];
        
        // Set filter rules
        $this->sanitize_array = [
            'email' => 'trim|lower_case|sanitize_email',
            'password' => 'trim',
            'name' => 'trim|sanitize_string'
        ];
        
        if(is_post_request()) {
            $validated = $this->validate_form($_POST);
            
            if(!empty($validated)) {
                // Data is valid and clean
                $db = $this->GetModel();
                $db->insert('users', $validated);
            }
            // Errors automatically set in $this->view->page_error
        }
        
        $this->render_view('users/add.php');
    }
}
```

---

## Performance Considerations

### Validation Performance

- **Rules are processed sequentially**: First failure stops processing for that field
- **Regex validators are slower**: Use built-in validators when possible
- **File validators can be slow**: Validate file size/type before moving large files

### Optimization Tips

```php
// Order rules from fast to slow
'email' => 'required|valid_email|unique_email'
// required (fast) → valid_email (medium) → unique_email (slow, database)

// Use specific validators
'age' => 'integer|min_numeric,18'  // Better than regex
```

---

## Security Best Practices

### 1. Always Filter Before Database

```php
$clean_data = GUMP::filter_input($_POST, $filters);
$db->insert('users', $clean_data);
```

### 2. Use Appropriate Sanitizers

```php
$filters = [
    'email' => 'trim|lower_case|sanitize_email',
    'html_content' => 'basic_tags',  // Allow only safe HTML
    'url' => 'urlencode'
];
```

### 3. Validate AND Filter

```php
// Bad: Only filtering
$data = GUMP::filter_input($_POST, $filters);

// Good: Filter then validate
$data = GUMP::filter_input($_POST, $filters);
$valid = GUMP::is_valid($data, $rules);
```

### 4. Use XSS Clean for Display

```php
$data = GUMP::xss_clean($_POST);
// Then escape on output
echo htmlspecialchars($data['field']);
```

---

## Troubleshooting Guide

### Problem: Validation Always Fails

**Check**:
1. Are field names in rules matching input data keys?
2. Is data structure correct (array expected)?
3. Are you checking return value correctly? (`=== false` vs `!= true`)

```php
// Wrong
if(!$validated) { }

// Correct
if($validated === false) { }
```

---

### Problem: Custom Validator Not Working

**Check**:
1. Is callback returning `false` on failure (not throwing exception)?
2. Is validator added before validation runs?
3. Is rule name correct (no `validate_` prefix in rule name)?

```php
// Wrong
GUMP::add_validator('validate_custom', $callback);
$rules = ['field' => 'validate_custom'];

// Correct
GUMP::add_validator('custom', $callback);
$rules = ['field' => 'custom'];
```

---

### Problem: Error Messages Not Showing

**Check**:
1. Did validation actually fail?
2. Are you calling `get_readable_errors()` or `get_errors_array()`?
3. Is language file present for chosen language?

```php
$result = $gump->run($_POST);
if($result === false) {
    $errors = $gump->get_readable_errors();  // Required
    print_r($errors);
}
```

---

### Problem: Field Reference Not Working

**Check**:
- Use `_` prefix for field references

```php
// Wrong
'password_confirm' => 'equalsfield,password'

// Correct
'password_confirm' => 'equalsfield,_password'
```

---

### Problem: Filters Not Applied

**Check**:
1. Are you using `filter()` method or `run()` method?
2. Are filter rules set?
3. Are you using the returned value (filters don't modify original)?

```php
// Wrong
$gump->filter($_POST, $filters);
// $_POST is unchanged

// Correct
$filtered = $gump->filter($_POST, $filters);
// Use $filtered
```

---

## Best Practices for AI Agents

### 1. Use run() for Complete Pipeline

```php
$gump->validation_rules($rules);
$gump->filter_rules($filters);
$validated = $gump->run($_POST);
```

### 2. Set Field Names for Better UX

```php
GUMP::set_field_names([
    'fname' => 'First Name',
    'pwd' => 'Password'
]);
```

### 3. Order Rules Efficiently

```php
// Fast to slow
'field' => 'required|alpha_numeric|min_len,3|database_unique'
```

### 4. Use Static Methods for Simple Cases

```php
// Quick validation
if(GUMP::is_valid($data, $rules) === true) { }

// Quick filtering
$clean = GUMP::filter_input($data, $filters);
```

### 5. Always Check Return Values Strictly

```php
// Use ===
if($validated === false) { }
if($result === true) { }
```

### 6. Filter User-Facing Data

```php
$filters = [
    'email' => 'trim|lower_case|sanitize_email',
    'name' => 'trim|sanitize_string',
    'bio' => 'trim|basic_tags'
];
```

---

## AI Agent Quick Reference

### Minimal Validation

```php
$valid = GUMP::is_valid($_POST, ['email' => 'required|valid_email']);
```

### Minimal Filtering

```php
$clean = GUMP::filter_input($_POST, ['email' => 'trim|lower_case']);
```

### Complete Pipeline

```php
$gump = new GUMP();
$gump->validation_rules(['email' => 'required|valid_email']);
$gump->filter_rules(['email' => 'trim|lower_case']);
$result = $gump->run($_POST);
```

### Custom Rule

```php
GUMP::add_validator('rule_name', function($field, $input, $param) {
    return ($input[$field] == 'valid') ? null : false;
}, 'Error message');
```

### Get Errors

```php
$errors = $gump->get_readable_errors();        // Array
$errors_html = $gump->get_readable_errors(true); // HTML
$field_errors = $gump->get_errors_array();     // ['field' => 'message']
```

---

## End of Documentation

**Last Updated**: Based on file analysis (Version 1.5)  
**Maintainer**: Auto-generated for AI agents  
**Official**: Based on GUMP by Sean Nieuwoudt & Filis Futsarov  
**Use For**: Form validation, input filtering, data sanitization, API input validation