# BaseController.php - AI Agent Documentation

## Overview
**File**: BaseController.php  
**Type**: PHP Base Controller Class  
**Purpose**: Core application controller providing authentication, database interaction, validation, file uploads, and audit logging functionality.  
**Framework**: Custom PHP MVC Framework  
**Inheritance**: Other controllers extend this class for standard features

---

## Class Definition

```php
class BaseController
```

**Access Control**: Controllers not requiring authentication/authorization can extend this class directly.

---

## Properties Reference

### Core System Properties

| Property | Type | Default | Description | Usage Context |
|----------|------|---------|-------------|---------------|
| `$status` | int | 200 | HTTP status code controlling page dispatch | Check before rendering |
| `$view` | BaseView | null | View renderer instance | All view operations |
| `$db` | PDODb | null | Database connection instance | Database queries |
| `$route` | object | null | Current page route properties | Routing information |
| `$rec_id` | string | null | Current record ID from URL | CRUD operations |
| `$return_value` | bool | false | Return value instead of rendering | Internal controller calls |
| `$flash_msg` | string | "" | Session message displayed after actions | User feedback |
| `$tablename` | string | null | Associated database table | Database operations |

### Request Handling Properties

| Property | Type | Default | Description | AI Agent Notes |
|----------|------|---------|-------------|----------------|
| `$request` | stdClass | null | Sanitized $_GET parameters | Always sanitized with FILTER_SANITIZE_SPECIAL_CHARS |
| `$post` | stdClass | null | Sanitized $_POST parameters | Only populated on POST requests |
| `$modeldata` | array | [] | Validated POST data ready for DB | Use after validate_form() |
| `$rules_array` | array | [] | Validation rules for forms | Define in child controllers |
| `$sanitize_array` | array | [] | Sanitization rules for inputs | Define in child controllers |

### Feature Flags

| Property | Type | Default | Description | When to Modify |
|----------|------|---------|-------------|----------------|
| `$validate_captcha` | bool | false | Enable CAPTCHA validation | Set true for public forms |
| `$filter_vals` | bool | false | Remove empty POST fields | Set true to clean data |
| `$filter_rules` | bool | false | Validate only posted fields | Set true for partial updates |
| `$soft_delete` | bool | false | Use soft delete instead of hard delete | Set true to preserve records |

### Soft Delete Configuration

| Property | Type | Default | Description | Customization |
|----------|------|---------|-------------|---------------|
| `$delete_field_name` | string | "is_deleted" | Database column for soft delete flag | Change if using different column |
| `$delete_field_value` | string | "-1" | Value marking deleted records | Adjust to match schema |

### Pagination Properties

| Property | Type | Description | Set By |
|----------|------|-------------|--------|
| `$ordertype` | string | Query order (DESC/ASC) | ORDER_TYPE constant or $_GET['ordertype'] |

### File Upload Configuration

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$file_upload_settings` | array | [] | File upload configurations by field name |
| `$fields` | array | [] | Table fields for current page |

---

## File Upload Presets

The constructor initializes these upload configurations:

### `photo`
```php
"extensions" => ".jpg,.png,.gif,.jpeg"
"limit" => "1"
"filesize" => "3" // MB
"uploadDir" => "uploads/files/"
```

### `attachment`
```php
"extensions" => ".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx"
"limit" => "10"
"filesize" => "10" // MB
"uploadDir" => "uploads/files/"
```

### `excel_file`
```php
"extensions" => ".xlsx"
"limit" => "1"
"filesize" => "10" // MB
"uploadDir" => "uploads/files/"
```

### `restore_file`
```php
"extensions" => ".sql"
"limit" => "1"
"filesize" => "100" // MB
"uploadDir" => "uploads/files/"
```

### `logo`
```php
"extensions" => ".jpg,.png,.gif,.jpeg"
"limit" => "1"
"filesize" => "3" // MB
"uploadDir" => "uploads/files/"
```

**AI Agent Note**: All use random filenames (`"title" => "{{random}}"`). Create custom settings by adding to this array.

---

## Methods Reference

### Constructor: `__construct()`

**Purpose**: Initialize controller with view, sanitize requests, check CSRF on POST

**Execution Flow**:
1. Creates BaseView instance
2. If POST request: validates CSRF token, sanitizes $_POST
3. Sanitizes $_GET into $this->request
4. Initializes file upload settings
5. Sets status to AUTHORIZED

**Security Features**:
- CSRF protection on all POST requests
- Automatic input sanitization (FILTER_SANITIZE_SPECIAL_CHARS)

**AI Agent Usage**:
```php
// Child controller constructor example
function __construct() {
    parent::__construct();
    // Additional initialization
}
```

---

### `GetModel()` : PDODb

**Purpose**: Initialize and return database connection

**Parameters**: None

**Returns**: PDODb instance

**Behavior**:
- Creates new PDODb connection with configured credentials
- If `$soft_delete` is true: adds WHERE clause to exclude deleted records
- Automatically filters out records where `$delete_field_name` equals `$delete_field_value`

**AI Agent Usage**:
```php
$db = $this->GetModel();
$db->where('user_id', $userId);
$users = $db->get('users');
```

**Database Configuration Used**:
- DB_TYPE, DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT, DB_CHARSET

---

### `set_request($get)` : void

**Purpose**: Sanitize and set GET request parameters

**Parameters**:
- `$get` (array): GET parameters (default from $_GET)

**Process**:
1. Creates stdClass for $this->request
2. Filters all values with FILTER_SANITIZE_SPECIAL_CHARS
3. Assigns each parameter as object property

**AI Agent Usage**:
```php
// Manually set request data
$this->set_request(['page' => '1', 'search' => 'term']);

// Access sanitized data
$page = $this->request->page;
```

---

### `set_route($route)` : void

**Purpose**: Set route information for controller and view

**Parameters**:
- `$route` (object): Route object from router

**Effect**:
- Adds request object to route
- Sets route on both controller and view

**AI Agent Usage**:
```php
$this->set_route($routeObject);
// Both $this->route and $this->view->route are now set
```

---

### `get_pagination($page_count)` : array

**Purpose**: Calculate pagination parameters for database queries

**Parameters**:
- `$page_count` (int): Records per page (default: MAX_RECORD_COUNT)

**Returns**: `[$limit_start, $limit_count]`

**Calculation Logic**:
```
limit_count = $this->request->limit_count || $page_count
limit_start = $this->request->limit_start || 1
limit_start = (limit_start - 1) * limit_count
```

**Side Effects**: Sets `$this->view->limit_count` and `$this->view->limit_start`

**AI Agent Usage**:
```php
list($start, $count) = $this->get_pagination(20);
$db->pageLimit = $count;
$records = $db->paginate('users', $start);
```

**Expected URL Format**: `?limit_start=2&limit_count=50`

---

### `validate_form($modeldata)` : array

**Purpose**: Sanitize and validate form data using GUMP library

**Parameters**:
- `$modeldata` (array): Raw POST data to validate

**Returns**: Validated and sanitized array or empty array on failure

**Process**:
1. Applies sanitization rules from `$this->sanitize_array`
2. Applies validation rules from `$this->rules_array`
3. If `$filter_vals` is true: removes empty fields
4. If `$filter_rules` is true: only validates fields present in POST data
5. If `$validate_captcha` is true: validates CAPTCHA
6. Sets validation errors in `$this->view->page_error` on failure

**AI Agent Usage**:
```php
// In child controller
$this->rules_array = [
    'email' => 'required|valid_email',
    'age' => 'required|numeric|min_numeric,18'
];
$this->sanitize_array = [
    'email' => 'trim|sanitize_email',
    'name' => 'trim'
];

$validated = $this->validate_form($_POST);
if(!empty($validated)) {
    // Data is valid
    $this->modeldata = $validated;
}
```

**GUMP Rules Reference**:
- `required`, `valid_email`, `numeric`, `min_numeric,N`, `max_numeric,N`
- `alpha`, `alpha_numeric`, `contains,str`, `regex,pattern`

---

### `render_view($viewname, $data, $layout, $remove_column)` : mixed

**Purpose**: Render view template or return value for internal calls

**Parameters**:
- `$viewname` (string): View template file path
- `$data` (mixed): Data to pass to view (default: null)
- `$layout` (string): Layout wrapper (default: "main_layout.php")
- `$remove_column` (mixed): Columns to exclude (default: null)

**Returns**: 
- If `$return_value` is true: returns `$this->rec_id`
- Otherwise: renders view (no return)

**AI Agent Usage**:
```php
// Standard view rendering
$this->render_view('users/list.php', $users);

// Return value for internal controller calls
$this->return_value = true;
$result = $this->render_view('users/list.php', $users);
```

---

### `set_page_error($page_error)` : string

**Purpose**: Set error messages from database or custom errors

**Parameters**:
- `$page_error` (string|array): Custom error message(s)

**Returns**: `$this->rec_id`

**Behavior**:
1. Checks for database errors via `$this->db->getLastError()`
2. Adds custom error if provided
3. Sets errors in `$this->view->page_error` array

**AI Agent Usage**:
```php
// Database errors automatically included
$this->set_page_error("Custom validation failed");

// Multiple errors
$this->set_page_error([
    "Email already exists",
    "Password too weak"
]);
```

---

### `set_flash_msg($msg, $type, $dismissable, $showduration)` : void

**Purpose**: Set session message for user feedback after actions

**Parameters**:
- `$msg` (string): Message content
- `$type` (string): Alert type - "success", "danger", "warning", "info", "custom" (default: "success")
- `$dismissable` (bool): Show close button (default: true)
- `$showduration` (int): Display duration in milliseconds (default: 5000)

**Behavior**:
- Skipped for AJAX requests
- Stores formatted HTML in session "MsgFlash"
- Uses Bootstrap alert classes
- Animated with "bounce" effect

**AI Agent Usage**:
```php
// Success message
$this->set_flash_msg("Record saved successfully");

// Error message
$this->set_flash_msg("Operation failed", "danger");

// Custom persistent message
$this->set_flash_msg("<div>Custom HTML</div>", "custom", false, 10000);
```

---

### `format_request_data($arr)` : array

**Purpose**: Convert array values to comma-separated strings

**Parameters**:
- `$arr` (array): POST data array

**Returns**: Array with arrays converted to strings

**Use Case**: Converting multi-select form inputs for database storage

**AI Agent Usage**:
```php
$postData = [
    'name' => 'John',
    'hobbies' => ['reading', 'gaming', 'coding']
];
$formatted = $this->format_request_data($postData);
// Result: ['name' => 'John', 'hobbies' => 'reading,gaming,coding']
```

---

### `format_multi_request_data($arr)` : array

**Purpose**: Format multiple POST data rows (batch operations)

**Parameters**:
- `$arr` (array): Array of POST data arrays

**Returns**: Array of formatted records (excludes empty records)

**Use Case**: Batch insert/update operations

**AI Agent Usage**:
```php
$batchData = [
    ['name' => 'User 1', 'roles' => ['admin', 'editor']],
    ['name' => '', 'roles' => []],  // Will be excluded
    ['name' => 'User 2', 'roles' => ['viewer']]
];
$formatted = $this->format_multi_request_data($batchData);
// Returns 2 records with arrays converted to strings
```

---

### `delete_record_files($files, $field)` : void

**Purpose**: Delete physical files associated with database records

**Parameters**:
- `$files` (array): Array of records containing file paths
- `$field` (string): Field name containing file path(s)

**Behavior**:
- Handles comma-separated file paths
- Converts site URLs to file system paths
- Silently handles deletion errors

**AI Agent Usage**:
```php
$records = $db->where('id', $id)->get('products');
$this->delete_record_files($records, 'product_image');
// Deletes all files in the 'product_image' field
```

**Path Conversion**: SITE_ADDR is replaced with empty string to get filesystem path

---

### `get_uploaded_file_paths($fieldname)` : string

**Purpose**: Process file upload and return file path(s)

**Parameters**:
- `$fieldname` (string): Name of file input field

**Returns**: Comma-separated file paths or empty string

**Configuration Source**: `$this->file_upload_settings[$fieldname]`

**Behavior**:
1. Uses Uploader class to process files
2. Returns comma-separated paths if multiple files
3. Optionally returns full URLs if `returnfullpath` is true
4. Sets errors in `$this->view->page_error` on failure

**AI Agent Usage**:
```php
// Using preset configuration
$photoPath = $this->get_uploaded_file_paths('photo');

// Custom configuration
$this->file_upload_settings['document'] = [
    "title" => "{{random}}",
    "extensions" => ".pdf,.docx",
    "limit" => "5",
    "filesize" => "20",
    "returnfullpath" => true,
    "uploadDir" => "uploads/documents/"
];
$docPaths = $this->get_uploaded_file_paths('document');

// Save to database
if($photoPath) {
    $this->modeldata['photo'] = $photoPath;
}
```

---

### `get_uploaded_file_data($fieldname)` : string|null

**Purpose**: Get file content as binary data for BLOB storage

**Parameters**:
- `$fieldname` (string): Name of file input field

**Returns**: Binary file content or null

**Use Case**: Storing files directly in database BLOB fields

**AI Agent Usage**:
```php
$fileData = $this->get_uploaded_file_data('certificate');
if($fileData) {
    $this->modeldata['certificate_blob'] = $fileData;
    $db->insert('certifications', $this->modeldata);
}
```

---

### `write_to_log($action, $req_status, $postdata, $table_name)` : void

**Purpose**: Record audit trail of database operations

**Parameters**:
- `$action` (string): Action performed (e.g., "insert", "update", "delete")
- `$req_status` (string): Request success status (default: "true")
- `$postdata` (array): Data to log (default: `$this->modeldata`)
- `$table_name` (string): Table name (default: `$this->tablename`)

**Log Storage**: Controlled by `$this->log_location`:
- `"table"`: Logs to `audit_logs` database table
- `"file"`: Logs to CSV file in AUDIT_LOGS_DIR
- `"email"`: Sends log via email

**Logged Information**:
- Timestamp
- Action performed
- Table and record ID
- SQL query executed
- User ID and IP address
- Request URL and data
- Request completion status
- Error messages

**AI Agent Usage**:
```php
// After successful insert
$this->rec_id = $db->getInsertId();
$this->write_to_log("insert", "true");

// After failed update
$this->write_to_log("update", "false");

// Custom logging
$this->log_location = "file";  // or "email"
$this->write_to_log("custom_action", "true", $customData, "custom_table");
```

**Audit Log Table Schema** (when log_location = "table"):
```
Timestamp, Action, TableName, RecordID, SqlQuery, 
UserID, ServerIP, RequestUrl, RequestData, 
RequestCompleted, RequestMsg
```

---

## Security Features

### CSRF Protection
- **Automatic**: All POST requests validated via `Csrf::cross_check()`
- **Timing**: Occurs in constructor before any processing
- **AI Agent Note**: Ensure CSRF tokens are present in forms

### Input Sanitization
- **GET Parameters**: Filtered with FILTER_SANITIZE_SPECIAL_CHARS
- **POST Parameters**: Filtered with FILTER_SANITIZE_SPECIAL_CHARS
- **Additional**: Custom sanitization via `$sanitize_array`

### SQL Injection Protection
- **Database**: Uses PDODb with prepared statements
- **Soft Delete**: Parameters properly bound in WHERE clause

---

## Common Usage Patterns

### Pattern 1: Basic CRUD Controller

```php
class UserController extends BaseController {
    function __construct() {
        parent::__construct();
        $this->tablename = "users";
        $this->soft_delete = true;
    }
    
    function add() {
        $this->rules_array = [
            'email' => 'required|valid_email',
            'password' => 'required|min_len,8'
        ];
        
        if(is_post_request()) {
            $validated = $this->validate_form($_POST);
            if(!empty($validated)) {
                $db = $this->GetModel();
                $validated['password'] = password_hash($validated['password'], PASSWORD_DEFAULT);
                
                if($db->insert($this->tablename, $validated)) {
                    $this->rec_id = $db->getInsertId();
                    $this->write_to_log("insert");
                    $this->set_flash_msg("User created successfully");
                    redirect("users/list");
                } else {
                    $this->set_page_error();
                }
            }
        }
        $this->render_view("users/add.php");
    }
}
```

### Pattern 2: File Upload with Validation

```php
function upload_avatar() {
    $this->rules_array = ['user_id' => 'required|numeric'];
    
    if(is_post_request()) {
        $validated = $this->validate_form($_POST);
        if(!empty($validated)) {
            $avatar_path = $this->get_uploaded_file_paths('photo');
            
            if($avatar_path) {
                $db = $this->GetModel();
                $db->where('id', $validated['user_id']);
                
                if($db->update('users', ['avatar' => $avatar_path])) {
                    $this->set_flash_msg("Avatar updated");
                } else {
                    $this->set_page_error();
                }
            }
        }
    }
}
```

### Pattern 3: List with Pagination

```php
function list_users() {
    $db = $this->GetModel();
    list($start, $count) = $this->get_pagination(25);
    
    $db->pageLimit = $count;
    $records = $db->paginate('users', $start);
    
    $this->render_view("users/list.php", $records);
}
```

### Pattern 4: Soft Delete

```php
function delete($rec_id) {
    $this->rec_id = $rec_id;
    $db = $this->GetModel();
    
    // Get files before deletion for cleanup
    $files = $db->where('id', $rec_id)->get('products');
    $this->delete_record_files($files, 'product_image');
    
    // Soft delete
    $db->where('id', $rec_id);
    if($db->update('products', ['is_deleted' => '-1'])) {
        $this->write_to_log("delete");
        $this->set_flash_msg("Product deleted");
    } else {
        $this->set_page_error();
    }
}
```

---

## Configuration Constants

The class relies on these global constants:

| Constant | Purpose | Example Value |
|----------|---------|---------------|
| ROOT | Application root path | "/var/www/app" |
| AUTHORIZED | Authorization status | 200 |
| ORDER_TYPE | Default query order | "DESC" |
| MAX_RECORD_COUNT | Default pagination size | 25 |
| DB_TYPE | Database type | "mysql" |
| DB_HOST | Database host | "localhost" |
| DB_USERNAME | Database username | "root" |
| DB_PASSWORD | Database password | "password" |
| DB_NAME | Database name | "myapp_db" |
| DB_PORT | Database port | 3306 |
| DB_CHARSET | Database charset | "utf8mb4" |
| SITE_ADDR | Site base URL | "https://example.com/" |
| AUDIT_LOGS_DIR | Audit log directory | "logs/audit/" |

---

## Dependencies

### Required Classes
- **BaseView**: View rendering engine
- **PDODb**: Database abstraction layer
- **Csrf**: CSRF token validation
- **Uploader**: File upload handler
- **Router**: URL routing
- **Mailer**: Email functionality (for email logging)

### Required Functions
- `is_post_request()`: Check if request is POST
- `is_ajax()`: Check if request is AJAX
- `set_session()`: Set session variable
- `get_session()`: Get session variable
- `datetime_now()`: Get current datetime
- `get_active_user()`: Get logged-in user info
- `get_user_ip()`: Get user's IP address
- `set_url()`: Convert path to full URL
- `redirect()`: Redirect to URL
- `recursive_implode()`: Recursive array to string

---

## Error Handling

### View Errors
- Stored in: `$this->view->page_error` (array)
- Sources: Database errors, validation errors, upload errors
- Display: Rendered in view templates

### Flash Messages
- Stored in: Session "MsgFlash"
- Types: success, danger, warning, info, custom
- Auto-display: Retrieved and shown on next page load

### Exception Handling
- File deletion: Silently caught
- Audit logging: Exceptions caught but not thrown (email/file modes)

---

## Best Practices for AI Agents

### 1. Always Call Parent Constructor
```php
function __construct() {
    parent::__construct();
    // Your initialization
}
```

### 2. Set Table Name Early
```php
function __construct() {
    parent::__construct();
    $this->tablename = "your_table";
}
```

### 3. Define Validation Rules Before Validation
```php
function add() {
    $this->rules_array = ['field' => 'required'];
    $this->sanitize_array = ['field' => 'trim'];
    
    if(is_post_request()) {
        $validated = $this->validate_form($_POST);
    }
}
```

### 4. Always Check Validation Results
```php
$validated = $this->validate_form($_POST);
if(!empty($validated)) {
    // Proceed with database operation
}
// Errors automatically set in $this->view->page_error
```

### 5. Use GetModel() for Each Request
```php
function my_action() {
    $db = $this->GetModel();  // Fresh connection
    // Use $db for queries
}
```

### 6. Log Important Actions
```php
if($db->insert($this->tablename, $data)) {
    $this->rec_id = $db->getInsertId();
    $this->write_to_log("insert");
}
```

### 7. Handle File Cleanup on Delete
```php
$files = $db->where('id', $id)->get($this->tablename);
$this->delete_record_files($files, 'file_field');
```

### 8. Use Flash Messages for User Feedback
```php
$this->set_flash_msg("Operation successful");
redirect("controller/action");
```

---

## Troubleshooting Guide

### Problem: Validation Always Fails
**Check**:
- Are `$rules_array` and `$sanitize_array` set before calling `validate_form()`?
- Are field names in rules matching POST field names?
- Is `$filter_rules` set to true when it shouldn't be?

### Problem: Files Not Uploading
**Check**:
- Is file input name matching key in `$file_upload_settings`?
- Are file extensions allowed in settings?
- Is filesize within limit?
- Does upload directory exist and is writable?

### Problem: Soft Delete Not Working
**Check**:
- Is `$soft_delete` set to true?
- Does table have `is_deleted` column (or custom `$delete_field_name`)?
- Is column type compatible with `$delete_field_value`?

### Problem: Audit Logs Not Created
**Check**:
- Is `$log_location` set correctly ("table", "file", or "email")?
- Does `audit_logs` table exist (for table mode)?
- Does AUDIT_LOGS_DIR exist and is writable (for file mode)?
- Is Mailer configured (for email mode)?

### Problem: CSRF Validation Failing
**Check**:
- Is CSRF token included in form?
- Is form using POST method?
- Are cookies enabled?
- Is session working?

---

## Version Notes

**Framework Version**: Custom PHP MVC  
**PHP Version Required**: 5.6+ (based on syntax)  
**Database Support**: MySQL, PostgreSQL, SQLite (via PDODb)

---

## AI Agent Quick Reference

### Minimal Working Controller
```php
class MyController extends BaseController {
    function __construct() {
        parent::__construct();
        $this->tablename = "my_table";
    }
    
    function index() {
        $db = $this->GetModel();
        $data = $db->get($this->tablename);
        $this->render_view("my/index.php", $data);
    }
}
```

### Common Method Call Sequences

**Create Record**:
1. `$this->validate_form($_POST)`
2. `$this->GetModel()`
3. `$db->insert()`
4. `$this->write_to_log("insert")`
5. `$this->set_flash_msg()`

**Update Record**:
1. `$this->validate_form($_POST)`
2. `$this->GetModel()`
3. `$db->where()->update()`
4. `$this->write_to_log("update")`

**Delete Record**:
1. `$this->GetModel()`
2. `$db->where()->get()` (for file cleanup)
3. `$this->delete_record_files()`
4. `$db->where()->delete()` or update for soft delete
5. `$this->write_to_log("delete")`

**List with Pagination**:
1. `$this->GetModel()`
2. `$this->get_pagination()`
3. `$db->paginate()`
4. `$this->render_view()`

---

## End of Documentation

**Last Updated**: Based on file analysis  
**Maintainer**: Auto-generated for AI agents  
**Feedback**: Use for code generation, analysis, and automation tasks