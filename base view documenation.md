# BaseView.php - AI Agent Documentation

## Overview
**File**: BaseView.php  
**Type**: PHP View Rendering Class  
**Purpose**: Manages view rendering, page exports (PDF, Excel, CSV, JSON), form field population, and UI component visibility.  
**Framework**: Custom PHP MVC Framework  
**PHP Attribute**: `#[AllowDynamicProperties]` - Allows dynamic property assignment

---

## Class Definition

```php
#[AllowDynamicProperties]
class BaseView
```

**Dynamic Properties**: This class automatically accepts GET parameters as object properties via the constructor.

---

## Properties Reference

### Core Data Properties

| Property | Type | Default | Description | Set By |
|----------|------|---------|-------------|--------|
| `$model` | object | null | Data passed from controller, accessible by all sub-views | Controller |
| `$view_data` | mixed | null | Main data passed from controller to view | `render()` method |
| `$route` | Router | null | Reference to current route with properties | Controller via `set_route()` |
| `$page_props` | array | null | Properties passed from other views | Other views |
| `$form_data` | array | null | $_GET data for setting form default values | Auto from $_GET |
| `$queryParams` | array | [] | Sanitized copy of all GET parameters | Constructor |

### Security & Request Properties

| Property | Type | Description | Usage |
|----------|------|-------------|-------|
| `$csrf_token` | string | CSRF token for forms | Include in all forms |
| `$view_args` | mixed | Additional view arguments | Custom data passing |
| `$request_uri` | string | Current request URI | URL information |

### Rendering Format Properties

| Property | Type | Default | Description | Values |
|----------|------|---------|-------------|--------|
| `$format` | string | "html" | Output format for page rendering | html, json, xml, csv, pdf, word, excel, image, print |

**Format Triggers**: Set via `?format=pdf` in URL

### Pagination Properties

| Property | Type | Default | Description | AI Agent Notes |
|----------|------|---------|-------------|----------------|
| `$limit_count` | int | MAX_RECORD_COUNT | Records per page | Set by controller's `get_pagination()` |
| `$limit_start` | int | 1 | Current page number | Set by controller's `get_pagination()` |

### Report/Export Properties

| Property | Type | Default | Description | Use Cases |
|----------|------|---------|-------------|-----------|
| `$report_filename` | string | "report" | Export filename (no extension) | PDF, Excel, CSV exports |
| `$report_title` | string | "" | Title displayed in report header | PDF, Word exports |
| `$report_layout` | string | "report_layout.php" | Layout template for reports | Report wrapping |
| `$report_orientation` | string | "portrait" | PDF page orientation | "portrait", "landscape" |
| `$report_paper_size` | string | "A4" | PDF paper size | "A4", "Letter", "Legal" |
| `$report_hidden_fields` | array | [] | Fields to exclude from export | `['id', 'password', 'created_at']` |
| `$report_links` | bool | true | Render link tags in PDF/Word | Set false to keep hrefs |
| `$report_list_sequence` | bool | true | Include row numbers in export | Set false to hide sequence |

### Page Display Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$page_title` | string | null | Browser/page title |
| `$view_title` | string | null | View heading title |
| `$view_name` | string | null | View template filename |
| `$ajax_view` | string | null | Alternative view for AJAX requests |

### UI Component Visibility Flags

| Property | Type | Default | Description | Control What? |
|----------|------|---------|-------------|---------------|
| `$show_header` | bool | true | Page header component | Header visibility |
| `$show_footer` | bool | true | Page footer component | Footer visibility |
| `$show_search` | bool | true | Search control | Search box |
| `$show_edit_btn` | bool | true | Edit record button | Edit actions |
| `$show_view_btn` | bool | true | View record button | View details |
| `$show_delete_btn` | bool | true | Delete button | Delete actions |
| `$show_multi_delete_btn` | bool | true | Bulk delete button | Multiple record deletion |
| `$show_import_btn` | bool | true | Import records button | Data import |
| `$show_export_btn` | bool | true | Export button | Data export |
| `$show_checkbox` | bool | true | Record selection checkbox | Bulk operations |
| `$show_list_sequence` | bool | true | Row number column | Sequence numbers |
| `$show_pagination` | bool | true | Pagination component | Page navigation |

**AI Agent Pattern**: Set these to `false` in controller to hide UI elements:
```php
$this->view->show_delete_btn = false;
$this->view->show_edit_btn = false;
```

### Error & Message Properties

| Property | Type | Default | Description | Format |
|----------|------|---------|-------------|--------|
| `$page_error` | string/array | null | Page errors from controller | Single string or array |
| `$form_error` | string/array | null | Form validation errors | Single string or array |

### Advanced View Properties

| Property | Type | Default | Description | Use Case |
|----------|------|---------|-------------|----------|
| `$is_partial_view` | bool | false | Render without layout | AJAX content |
| `$partial_view` | string | null | Partial view file path | Component rendering |
| `$search_template` | string | null | Template for AJAX dropdown search | Autocomplete |
| `$ajax_page` | string | null | Template for AJAX content | Dynamic loading |
| `$force_layout` | string | null | Override default layout | Custom layouts |
| `$force_print` | bool | false | Auto-open print dialog | Print view |
| `$redirect_to` | string | null | Redirect URL after render | Post-action redirect |

---

## Methods Reference

### Constructor: `__construct($arg)`

**Purpose**: Initialize view and map GET parameters to object properties

**Parameters**:
- `$arg` (mixed): Optional initialization argument

**Execution Flow**:
1. Sanitizes all GET parameters with FILTER_SANITIZE_SPECIAL_CHARS
2. Stores sanitized params in `$queryParams` array
3. Dynamically assigns each GET param as object property

**AI Agent Usage**:
```php
// GET: ?page=2&search=test
$view = new BaseView();
echo $view->page;    // "2"
echo $view->search;  // "test"
echo $view->format;  // "html" (default)
```

**Security Feature**: All GET parameters automatically sanitized

---

### `render($view_name, $view_data, $layout, $remove_column)` : void

**Purpose**: Main method to render views in multiple formats

**Parameters**:
- `$view_name` (string): Path to view template file
- `$view_data` (mixed): Data to pass to view (default: null)
- `$layout` (string): Layout wrapper file (default: "main_layout.php")
- `$remove_column` (mixed): Columns to remove (default: null)

**Format Handling**:

| Format | Output | Process |
|--------|--------|---------|
| `html` | HTML page | Renders view with layout |
| `json` | JSON response | Converts view_data to JSON |
| `xml` | XML response | Converts records to XML |
| `csv` | CSV download | Exports records as CSV |
| `excel` | Excel download | Creates .xlsx file |
| `word` | Word download | Creates .docx file |
| `pdf` | PDF download | Generates PDF using mPDF/DomPDF |
| `print` | Print dialog | Renders HTML and triggers print |

**Process Flow by Format**:

**HTML**:
```php
1. Sets view_name and view_data
2. Checks if AJAX request → renders ajax_view if set
3. Includes view template within layout
4. Outputs HTML to browser
```

**JSON**:
```php
1. Converts view_data to JSON
2. Sets Content-Type: application/json
3. Echoes JSON and exits
```

**CSV**:
```php
1. Parses report records
2. Creates CSV headers from first record
3. Writes all records as CSV rows
4. Triggers download
```

**PDF**:
```php
1. Parses report HTML (removes unwanted elements)
2. Creates PDF using mPDF or DomPDF
3. Sets orientation and paper size
4. Triggers download
```

**Excel**:
```php
1. Uses PhpSpreadsheet library
2. Creates worksheet with records
3. Auto-sizes columns
4. Generates .xlsx file
```

**Word**:
```php
1. Uses PhpWord library
2. Parses report HTML
3. Converts HTML to Word document
4. Triggers download
```

**AI Agent Usage**:

```php
// Standard HTML rendering
$this->view->render('users/list.php', $users);

// PDF export
$this->view->format = 'pdf';
$this->view->report_filename = 'users_report';
$this->view->report_orientation = 'landscape';
$this->view->render('users/list.php', $users);

// JSON API response
$this->view->format = 'json';
$this->view->render(null, ['status' => 'success', 'data' => $users]);

// Excel export with hidden fields
$this->view->format = 'excel';
$this->view->report_hidden_fields = ['password', 'token'];
$this->view->render('users/list.php', $users);
```

---

### `set_field_value($fieldname, $default_value, $index)` : mixed

**Purpose**: Get form field value from POST/GET data with fallback

**Parameters**:
- `$fieldname` (string): Form field name
- `$default_value` (mixed): Default value if not found (default: null)
- `$index` (int): Row index for multi-row forms (default: null)

**Value Priority**:
1. `$this->page_props[$fieldname]` - Passed from other views
2. `$_REQUEST[$fieldname]` - Current request (POST/GET)
3. `$default_value` - Provided default

**Multi-row Support**: For batch forms with `row0[field]`, `row1[field]` pattern

**AI Agent Usage**:

```php
<!-- Single field -->
<input name="email" value="<?php echo $this->set_field_value('email'); ?>" />

<!-- With default value -->
<input name="country" value="<?php echo $this->set_field_value('country', 'USA'); ?>" />

<!-- Multi-row form -->
<input name="row0[name]" value="<?php echo $this->set_field_value('name', '', 0); ?>" />
<input name="row1[name]" value="<?php echo $this->set_field_value('name', '', 1); ?>" />
```

**Use Case**: Populate form fields after validation failure or when editing records

---

### `set_field_checked($fieldname, $value, $default_value)` : string

**Purpose**: Return "checked" attribute for radio/checkbox inputs

**Parameters**:
- `$fieldname` (string): Form field name
- `$value` (string): Value to check against
- `$default_value` (mixed): Default value if field not in request (default: null)

**Returns**: `"checked"` or `null`

**Array Support**: If field value is array, checks if value exists in array

**AI Agent Usage**:

```php
<!-- Radio button -->
<input type="radio" name="gender" value="Male" 
    <?php echo $this->set_field_checked('gender', 'Male'); ?> />

<!-- Checkbox with default -->
<input type="checkbox" name="terms" value="1" 
    <?php echo $this->set_field_checked('terms', '1', '1'); ?> />

<!-- Multi-select checkboxes -->
<input type="checkbox" name="roles[]" value="admin" 
    <?php echo $this->set_field_checked('roles', 'admin'); ?> />
```

**Use Case**: Maintain form state after POST back or validation errors

---

### `set_field_selected($fieldname, $value, $default_value)` : string

**Purpose**: Return "selected" attribute for select dropdown options

**Parameters**:
- `$fieldname` (string): Select field name
- `$value` (string): Option value to check
- `$default_value` (mixed): Default selected value (default: 0)

**Returns**: `"selected"` or `null`

**Array Support**: For multi-select dropdowns

**AI Agent Usage**:

```php
<!-- Single select -->
<select name="country">
    <option value="US" <?php echo $this->set_field_selected('country', 'US'); ?>>USA</option>
    <option value="UK" <?php echo $this->set_field_selected('country', 'UK'); ?>>UK</option>
</select>

<!-- With default value -->
<select name="status">
    <option value="1" <?php echo $this->set_field_selected('status', '1', '1'); ?>>Active</option>
    <option value="0" <?php echo $this->set_field_selected('status', '0', '1'); ?>>Inactive</option>
</select>

<!-- Multi-select -->
<select name="tags[]" multiple>
    <option value="php" <?php echo $this->set_field_selected('tags', 'php'); ?>>PHP</option>
    <option value="js" <?php echo $this->set_field_selected('tags', 'js'); ?>>JavaScript</option>
</select>
```

---

### `check_form_field_checked($srcdata, $value)` : string

**Purpose**: Check if value exists in comma-separated string (for pre-populated checkboxes)

**Parameters**:
- `$srcdata` (string): Comma-separated values from database (e.g., "admin,editor,viewer")
- `$value` (string): Value to check for

**Returns**: `"checked"` or `null`

**AI Agent Usage**:

```php
<!-- Database has: roles = "admin,editor" -->
<?php $user_roles = $record['roles']; ?>

<input type="checkbox" name="roles[]" value="admin" 
    <?php echo $this->check_form_field_checked($user_roles, 'admin'); ?> /> Admin

<input type="checkbox" name="roles[]" value="editor" 
    <?php echo $this->check_form_field_checked($user_roles, 'editor'); ?> /> Editor

<input type="checkbox" name="roles[]" value="viewer" 
    <?php echo $this->check_form_field_checked($user_roles, 'viewer'); ?> /> Viewer
```

**Use Case**: Editing records with multi-select checkboxes stored as comma-separated strings

---

### `get_page_title($title)` : string

**Purpose**: Get page title for HTML `<title>` tag

**Parameters**:
- `$title` (string): Optional override title

**Returns**: Page title string

**Priority**:
1. `$this->page_title` (set by controller)
2. `Router::$page_name` (from route)
3. Provided `$title` parameter

**AI Agent Usage**:

```php
<!-- In layout file -->
<title><?php echo $this->get_page_title(); ?></title>

<!-- Controller sets title -->
$this->view->page_title = "User Management - Dashboard";
```

---

### `display_page_errors()` : void (outputs HTML)

**Purpose**: Display error messages from controller

**Output**: Bootstrap alert divs with error messages

**Handles**:
- Single string error: One alert div
- Array of errors: Multiple alert divs
- Animated with "shake" effect

**AI Agent Usage**:

```php
<!-- In view template -->
<?php $this->display_page_errors(); ?>

<!-- Controller sets errors -->
$this->view->page_error = "Invalid email address";
// OR
$this->view->page_error = [
    "Email is required",
    "Password must be at least 8 characters"
];
```

**HTML Output**:
```html
<div class="alert alert-danger animated shake">
    Error message here
</div>
```

---

### `parse_report_html()` : string (private)

**Purpose**: Process rendered HTML for export, removing unwanted elements

**Returns**: Cleaned HTML string for PDF/Word export

**Process**:
1. Renders page with report layout
2. Decodes HTML entities
3. Loads into DOMDocument
4. Extracts element with ID "page-report-body"
5. Uses XPath to remove unwanted elements:
   - Elements with class "td-btn" (action buttons)
   - Elements with class "td-checkbox" (selection checkboxes)
   - Elements with class "td-sno" (if `$report_list_sequence` is false)
   - Fields in `$report_hidden_fields` array
6. Removes href attributes from links (if `$report_links` is true)
7. Places cleaned content in element with ID "report-body"

**AI Agent Note**: This is called automatically by `render()` for PDF/Word formats

**Customization**:
```php
// Hide specific fields in export
$this->view->report_hidden_fields = ['password', 'ssn', 'credit_card'];

// Keep sequence numbers
$this->view->report_list_sequence = true;

// Keep clickable links
$this->view->report_links = false;
```

**HTML Requirements**:
- Main content must have `id="page-report-body"`
- Report layout must have `id="report-body"` placeholder

---

### `parse_report_records()` : array (private)

**Purpose**: Extract and filter records for CSV/Excel/JSON export

**Returns**: Array of records with hidden fields removed

**Behavior**:
- If `$view_data->records` exists: exports all records (list page)
- Otherwise: wraps `$view_data` as single-record array (view page)
- Removes fields listed in `$report_hidden_fields`

**AI Agent Note**: Called automatically for CSV, Excel, JSON formats

---

### `innerHTML(\DOMElement $element)` : string (private)

**Purpose**: Get inner HTML content of DOM element

**Parameters**:
- `$element` (DOMElement): DOM element

**Returns**: HTML string

**Use Case**: Extracting HTML from DOM manipulation during report processing

---

### `setInnerHTML($element, $html)` : void (private)

**Purpose**: Set inner HTML content of DOM element

**Parameters**:
- `$element` (DOMElement): Target element
- `$html` (string): HTML content to insert

**Process**:
1. Decodes HTML entities
2. Fixes UTF-8 encoding issues
3. Creates document fragment
4. Replaces element content

**Use Case**: Inserting cleaned report body into layout during export

---

## Export Format Details

### PDF Export Configuration

**Libraries Used**:
- **mPDF** (preferred): Full UTF-8 support, better rendering
- **DomPDF** (fallback): Lighter weight

**Configuration**:
```php
$this->view->format = 'pdf';
$this->view->report_filename = 'invoice_2024';
$this->view->report_orientation = 'landscape'; // or 'portrait'
$this->view->report_paper_size = 'A4'; // or 'Letter', 'Legal'
$this->view->report_title = 'Monthly Sales Report';
```

**mPDF Settings**:
- Mode: UTF-8
- Format: A4-P (portrait) or A4-L (landscape)
- Language support: Via cookies (`get_cookie('lang')`)

---

### Excel Export Configuration

**Library**: PhpSpreadsheet

**Features**:
- Auto-column sizing
- Header row from first record keys
- Data types preserved

**Configuration**:
```php
$this->view->format = 'excel';
$this->view->report_filename = 'users_export';
$this->view->report_hidden_fields = ['password', 'token'];
```

**Output**: `.xlsx` file compatible with Excel 2007+

---

### CSV Export Configuration

**Features**:
- UTF-8 BOM for Excel compatibility
- Headers from first record
- Proper escaping

**Configuration**:
```php
$this->view->format = 'csv';
$this->view->report_filename = 'data_export';
```

**Output**: Standard CSV with UTF-8 encoding

---

### Word Export Configuration

**Library**: PHPWord

**Features**:
- HTML to Word conversion
- Maintains basic formatting
- Tables preserved

**Configuration**:
```php
$this->view->format = 'word';
$this->view->report_filename = 'document';
$this->view->report_title = 'Project Report';
```

**Output**: `.docx` file

---

### JSON Export Configuration

**Features**:
- Pretty print for debugging
- Proper content-type header
- UTF-8 encoding

**Configuration**:
```php
$this->view->format = 'json';
```

**Output Example**:
```json
{
    "records": [
        {"id": 1, "name": "John"},
        {"id": 2, "name": "Jane"}
    ],
    "total": 2
}
```

---

### XML Export Configuration

**Features**:
- Proper XML structure
- UTF-8 declaration
- Record iteration

**Configuration**:
```php
$this->view->format = 'xml';
```

**Output Example**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<records>
    <record>
        <id>1</id>
        <name>John</name>
    </record>
</records>
```

---

## Common Usage Patterns

### Pattern 1: Standard Page Rendering

```php
// In Controller
function list_users() {
    $db = $this->GetModel();
    $users = $db->get('users');
    
    // Set page title
    $this->view->page_title = "User Management";
    
    // Hide certain buttons
    $this->view->show_delete_btn = false;
    
    // Render view
    $this->render_view('users/list.php', $users);
}
```

---

### Pattern 2: Multi-Format Export

```php
// In Controller
function export_users() {
    $db = $this->GetModel();
    $users = $db->get('users');
    
    // Configure export
    $this->view->report_filename = 'users_' . date('Y-m-d');
    $this->view->report_hidden_fields = ['password', 'token'];
    $this->view->report_title = "User List Report";
    
    // Format determined by ?format=pdf|excel|csv
    $this->render_view('users/list.php', $users);
}
```

---

### Pattern 3: Form with Validation Errors

```php
// In Controller
function edit($id) {
    if(is_post_request()) {
        $validated = $this->validate_form($_POST);
        
        if(empty($validated)) {
            // Validation failed - errors in $this->view->page_error
            // Form will repopulate with POST values
            $this->render_view('users/edit.php');
            return;
        }
        
        // Update logic...
    }
    
    // Load user data for editing
    $user = $db->where('id', $id)->getOne('users');
    
    // Pass data to populate form
    $this->view->page_props = $user;
    $this->render_view('users/edit.php');
}
```

```php
<!-- In View: users/edit.php -->
<form method="POST">
    <input name="email" value="<?php echo $this->set_field_value('email'); ?>" />
    
    <select name="role">
        <option value="admin" <?php echo $this->set_field_selected('role', 'admin'); ?>>Admin</option>
        <option value="user" <?php echo $this->set_field_selected('role', 'user'); ?>>User</option>
    </select>
    
    <input type="checkbox" name="active" value="1" 
        <?php echo $this->set_field_checked('active', '1'); ?> /> Active
    
    <button type="submit">Save</button>
</form>

<?php $this->display_page_errors(); ?>
```

---

### Pattern 4: AJAX Partial View

```php
// In Controller
function search_users() {
    if(is_ajax()) {
        $db = $this->GetModel();
        $search = $this->request->q;
        
        $users = $db->where('name', "%$search%", 'LIKE')->get('users');
        
        $this->view->is_partial_view = true;
        $this->render_view('users/search_results.php', $users);
    }
}
```

---

### Pattern 5: Custom Report Layout

```php
// In Controller
function generate_invoice($id) {
    $invoice = $this->get_invoice_data($id);
    
    // Use custom invoice layout
    $this->view->report_layout = 'invoice_layout.php';
    $this->view->report_filename = "invoice_$id";
    $this->view->format = 'pdf';
    
    $this->render_view('invoices/view.php', $invoice);
}
```

---

### Pattern 6: Dynamic UI Control

```php
// In Controller - Read-only view
function view_user($id) {
    $user = $db->where('id', $id)->getOne('users');
    
    // Hide all action buttons
    $this->view->show_edit_btn = false;
    $this->view->show_delete_btn = false;
    $this->view->show_checkbox = false;
    
    $this->render_view('users/view.php', $user);
}

// In Controller - Restricted list
function archived_users() {
    $users = $db->where('archived', 1)->get('users');
    
    // Show only view, hide modification buttons
    $this->view->show_edit_btn = false;
    $this->view->show_delete_btn = false;
    $this->view->show_import_btn = false;
    
    $this->render_view('users/list.php', $users);
}
```

---

## View Template Integration

### Accessing View Properties in Templates

```php
<!-- In any view file -->

<!-- Access data passed from controller -->
<?php foreach($this->view_data as $record): ?>
    <tr>
        <td><?php echo $record['name']; ?></td>
    </tr>
<?php endforeach; ?>

<!-- Access route information -->
<?php echo $this->route->page_name; ?>
<?php echo $this->route->action; ?>

<!-- Access pagination -->
Page <?php echo $this->limit_start; ?> of records, 
showing <?php echo $this->limit_count; ?> per page

<!-- Conditional UI rendering -->
<?php if($this->show_edit_btn): ?>
    <a href="/edit/<?php echo $id; ?>">Edit</a>
<?php endif; ?>

<!-- CSRF token -->
<input type="hidden" name="csrf_token" value="<?php echo $this->csrf_token; ?>" />
```

---

## Report Template Requirements

### HTML Structure for Exports

```html
<!-- In report template (e.g., users/list.php) -->
<div id="page-report-body">
    <table class="table">
        <thead>
            <tr>
                <!-- Sequence column (optional) -->
                <th class="td-sno">No.</th>
                
                <!-- Regular columns -->
                <th class="td-name">Name</th>
                <th class="td-email">Email</th>
                
                <!-- Checkbox column (auto-hidden in exports) -->
                <th class="td-checkbox">
                    <input type="checkbox" />
                </th>
                
                <!-- Action buttons column (auto-hidden in exports) -->
                <th class="td-btn">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($this->view_data as $index => $record): ?>
            <tr>
                <td class="td-sno"><?php echo $index + 1; ?></td>
                <td class="td-name"><?php echo $record['name']; ?></td>
                <td class="td-email"><?php echo $record['email']; ?></td>
                <td class="td-checkbox">
                    <input type="checkbox" value="<?php echo $record['id']; ?>" />
                </td>
                <td class="td-btn">
                    <a href="/edit/<?php echo $record['id']; ?>">Edit</a>
                    <a href="/delete/<?php echo $record['id']; ?>">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

**Class Naming Convention**:
- `td-{fieldname}`: For data columns (can be hidden via `$report_hidden_fields`)
- `td-btn`: Auto-removed from exports
- `td-checkbox`: Auto-removed from exports
- `td-sno`: Removed if `$report_list_sequence` is false

---

### Report Layout Template

```php
<!-- In layouts/report_layout.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $this->report_title; ?></title>
    <style>
        /* Report-specific styles */
    </style>
</head>
<body>
    <h1><?php echo $this->report_title; ?></h1>
    <div class="report-meta">
        Generated: <?php echo date('Y-m-d H:i:s'); ?>
    </div>
    
    <!-- This is where cleaned report body is inserted -->
    <div id="report-body"></div>
    
    <div class="report-footer">
        Page footer content
    </div>
</body>
</html>
```

---

## Security Considerations

### Input Sanitization
- **GET Parameters**: Auto-sanitized with FILTER_SANITIZE_SPECIAL_CHARS in constructor
- **Form Values**: Sanitized in `set_field_value()`, `set_field_checked()`, `set_field_selected()`

### CSRF Protection
- `$csrf_token` property populated by controller
- Must be included in all forms

### XSS Prevention
- Always escape output in views: `<?php echo htmlspecialchars($value); ?>`
- Sanitization happens on input, escaping on output

---

## Best Practices for AI Agents

### 1. Always Set Format via URL Parameter
```php
// Don't set format directly unless programmatically exporting
// Better: Let user choose via URL ?format=pdf
```

### 2. Configure Export Properties Before Rendering
```php
// In Controller
$this->view->report_filename = 'export_' . date('Ymd');
$this->view->report_hidden_fields = ['password', 'ssn'];
$this->render_view('data/list.php', $data);
```

### 3. Use Proper Class Names in Templates
```php
<!-- Columns that can be hidden -->
<td class="td-fieldname">Value</td>

<!-- Always-hidden columns -->
<td class="td-btn">Actions</td>
<td class="td-checkbox">Select</td>
```

### 4. Set Page Title for Better UX
```php
$this->view->page_title = "Dashboard - " . get_active_user('name');
```

### 5. Control UI Elements Based on Permissions
```php
if(!user_has_permission('delete_users')) {
    $this->view->show_delete_btn = false;
    $this->view->show_multi_delete_btn = false;
}
```

### 6. Populate Forms Correctly
```php
<!-- Always use helper methods -->
<input value="<?php echo $this->set_field_value('email'); ?>" />

<!-- Not direct access -->
<input value="<?php echo $_POST['email']; ?>" /> ❌
```

### 7. Handle Different Data Structures
```php
// List page: $view_data->records
// View page: $view_data (single record)
// The class handles both in parse_report_records()
```

---

## Troubleshooting Guide

### Problem: Export Includes Action Buttons
**Solution**: Ensure buttons have `class="td-btn"` and are inside `#page-report-body`

### Problem: Export Missing Data
**Solution**: Check if fields are in `$report_hidden_fields` array

### Problem: PDF Not Generating
**Check**:
- Is mPDF or DomPDF installed?
- Does `#page-report-body` exist in template?
- Does `#report-body` exist in report layout?
- Is report layout file in LAYOUTS_DIR?

### Problem: Form Not Repopulating After Validation
**Solution**: Ensure using `set_field_value()`, `set_field_checked()`, `set_field_selected()` methods

### Problem: CSV Showing Garbled Characters in Excel
**Solution**: Class adds UTF-8 BOM automatically - check browser encoding

### Problem: Page Title Not Showing
**Check Priority**:
1. Is `$this->view->page_title` set in controller?
2. Is `get_page_title()` called in layout?
3. Is Router::$page_name set?

---

## Dependencies

### Required Libraries
- **mPDF** or **DomPDF**: PDF generation
- **PhpSpreadsheet**: Excel export
- **PHPWord**: Word document export
- **DOMDocument**: HTML parsing (built-in PHP)
- **libxml**: XML processing (built-in PHP)

### Required Constants
- `ROOT`: Application root path
- `MAX_RECORD_COUNT`: Default pagination size
- `LAYOUTS_DIR`: Path to layout files

### Required Functions
- `is_ajax()`: Check if request is AJAX
- `get_cookie()`: Get cookie value
- `fixWrongUTF8Encoding()`: Fix encoding issues

### Required Classes
- `Router`: Routing information
- `Csrf`: CSRF token management

---

## Performance Notes

### Memory Usage
- **PDF Generation**: High memory for large reports (consider pagination)
- **Excel Export**: Memory efficient with PhpSpreadsheet
- **DOM Manipulation**: Can be slow for very large HTML documents

### Optimization Tips
1. Limit records per export (max 1000-5000 recommended)
2. Use pagination for large datasets
3. Cache report layouts if possible
4. Disable `$report_list_sequence` for faster processing
5. Minimize hidden fields processing

---

## Version Compatibility

**PHP Version**: 7.0+ (uses null coalescing `??`)  
**PHP 8.0+**: Compatible (handles `libxml_disable_entity_loader()` deprecation)  
**Attribute Support**: PHP 8.0+ (`#[AllowDynamicProperties]`)

---

## AI Agent Quick Reference

### Minimal View Rendering
```php
// In Controller
$data = ['user' => 'John', 'email' => 'john@example.com'];
$this->view->render('users/view.php', $data);
```

### Export Configuration
```php
$this->view->format = 'pdf';  // or excel, csv, word, json, xml
$this->view->report_filename = 'filename';
$this->view->report_hidden_fields = ['password', 'token'];
$this->view->render('page.php', $data);
```

### Form Field Helpers
```php
set_field_value('fieldname', 'default')
set_field_checked('fieldname', 'value', 'default')
set_field_selected('fieldname', 'value', 'default')
check_form_field_checked('comma,separated,values', 'value')
```

### UI Control
```php
$this->view->show_edit_btn = false;
$this->view->show_delete_btn = false;
$this->view->show_pagination = false;
```

---

## End of Documentation

**Last Updated**: Based on file analysis  
**Maintainer**: Auto-generated for AI agents  
**Feedback**: Use for code generation, view rendering, and export functionality