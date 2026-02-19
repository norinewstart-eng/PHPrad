# Pagination.php - AI Agent Documentation

## Overview
**File**: Pagination.php  
**Type**: PHP View Helper Class  
**Category**: View Helper  
**Purpose**: Generate responsive pagination controls with page navigation, record count display, and customizable page limits.  
**Framework**: Custom PHP MVC Framework  
**UI Framework**: Bootstrap 4/5 compatible

---

## Class Definition

```php
class Pagination
```

**Usage Context**: Instantiated in view templates to display pagination controls for database query results.

---

## Properties Reference

### Core Data Properties

| Property | Type | Default | Description | Required |
|----------|------|---------|-------------|----------|
| `$page_name` | string | null | Current page/table name for reference | No |
| `$total_records` | int | - | Total records in database table | Yes (via constructor) |
| `$current_record_count` | int | - | Number of records on current page | Yes (via constructor) |
| `$limit_count` | int | MAX_RECORD_COUNT | Records per page | No |

**AI Agent Note**: `$total_records` and `$current_record_count` are set via constructor and are mandatory.

---

### Display Configuration Properties

| Property | Type | Default | Description | Controls |
|----------|------|---------|-------------|----------|
| `$pager_link_range` | int | 4 | Number of page links to display | Page number buttons (e.g., 1 2 3 4) |
| `$show_page_count` | bool | true | Display page count dropdown | Page X of Y selector |
| `$show_record_count` | bool | true | Display record count info | "Records: X of Y" text |
| `$show_page_limit` | bool | true | Display limit control | Records per page input |
| `$show_page_number_list` | bool | true | Display numbered page links | Page number buttons |

**UI Control Pattern**:
```php
// Hide specific elements
$pagination->show_page_limit = false;      // Hide "records per page" control
$pagination->show_page_number_list = false; // Hide numbered page links
$pagination->show_record_count = false;     // Hide "X of Y records" text
```

---

## Constructor

### `__construct($total_records, $current_record_count)`

**Purpose**: Initialize pagination with record counts

**Parameters**:
- `$total_records` (int): **Required** - Total records in database query/table
- `$current_record_count` (int): **Required** - Records returned in current page

**AI Agent Usage**:
```php
// From controller
$db = $this->GetModel();
$total = $db->getValue('users', 'count(*)');
$users = $db->get('users', 20); // Get 20 records
$current_count = count($users);

// In view
$pagination = new Pagination($total, $current_count);
$pagination->render();
```

**Calculation Example**:
```php
// Database has 250 users
// Current query returns 20 users
$pagination = new Pagination(250, 20);
// Will generate: Page 1 of 13 (assuming 20 per page)
```

---

## Methods Reference

### `set_link($limit_start)` : string

**Purpose**: Generate pagination URL with query string parameters

**Parameters**:
- `$limit_start` (int): Page number to link to (default: null)

**Returns**: URL with pagination query parameters

**Behavior**:
1. Creates query string array
2. Adds `limit_start` parameter if provided
3. Calls `set_current_page_link()` to merge with existing parameters
4. Calls `print_link()` to output the URL

**AI Agent Usage**:
```php
// Generate link to page 3
$pagination->set_link(3);
// Output: /users/list?limit_start=3&other_param=value

// Generate link maintaining current page
$pagination->set_link();
// Output: /users/list?other_param=value
```

**URL Building Logic**:
- Preserves existing query parameters (search, filters, etc.)
- Only modifies `limit_start` parameter
- Maintains clean URL structure

---

### `render()` : void (outputs HTML)

**Purpose**: Generate and display complete pagination UI

**Returns**: None (outputs HTML directly)

**Component Breakdown**:

#### 1. **Form Container**
```html
<form id="form{random_id}" action="{current_page_link}" method="get">
```
- Uses GET method to maintain URL-based state
- Random form ID to avoid conflicts with multiple paginations

#### 2. **Record Count Display** (if `$show_record_count` is true)
```html
<small>Records: {current_position} of {total_records}</small>
```

#### 3. **Page Count Dropdown** (if `$show_page_count` is true)
```html
<select name="limit_start">
    <option value="1">1</option>
    <option value="2" selected>2</option>
    ...
</select>
Page X of Y
```

#### 4. **Records Per Page Control** (if `$show_page_limit` is true)
```html
<input type="number" name="limit_count" value="{current_limit}" />
```

#### 5. **Pagination Links** (if multiple pages exist)
```html
<ul class="pagination pagination-sm">
    <li><a href="?limit_start=1">First</a></li>
    <li><a href="?limit_start=1">←</a></li>
    <li><a href="?limit_start=2">2</a></li>
    <li class="active"><a>3</a></li>
    <li><a href="?limit_start=4">4</a></li>
    <li><a href="?limit_start=4">→</a></li>
    <li><a href="?limit_start=10">Last</a></li>
</ul>
```

**AI Agent Usage**:
```php
<!-- In view template -->
<?php
$pagination = new Pagination($total_records, $record_count);
$pagination->limit_count = 25;
$pagination->pager_link_range = 5;
$pagination->render();
?>
```

---

## Rendering Logic Breakdown

### Page Calculation Algorithm

```php
// Input from URL
$limit_count = $_GET['limit_count'] ?? MAX_RECORD_COUNT;
$page_num = $_GET['limit_start'] ?? 1;

// Calculate total pages
$numofpages = ceil($total_records / $limit_count);

// Calculate current record position
$record_position = min(($page_num * $limit_count), $total_records);
```

**Example**:
```
Total Records: 250
Limit Count: 20
Page Number: 3

numofpages = ceil(250 / 20) = 13 pages
record_position = min((3 * 20), 250) = 60
Display: "Records: 60 of 250"
```

---

### Page Link Range Algorithm

**Purpose**: Display limited range of page numbers around current page

**Configuration**: `$pager_link_range = 4` (shows 4 page links)

**Algorithm**:
```php
// Calculate range min and max
$range_min = ($pager_link_range % 2 == 0) 
    ? ($pager_link_range / 2) - 1 
    : ($pager_link_range - 1) / 2;

$range_max = ($pager_link_range % 2 == 0) 
    ? $range_min + 1 
    : $range_min;

// Calculate page min and max around current page
$page_min = $page_num - $range_min;
$page_max = $page_num + $range_max;

// Adjust boundaries
$page_min = max($page_min, 1);
$page_max = min($page_max, $numofpages);

// Ensure minimum range
if($page_max < ($page_min + $pager_link_range - 1)) {
    $page_max = $page_min + $pager_link_range - 1;
}
```

**Visual Examples**:

**Range = 4, Current Page = 5, Total Pages = 20**
```
[First] [←] [3] [4] [5] [6] [→] [Last]
              ↑ current
```

**Range = 4, Current Page = 2, Total Pages = 20**
```
[First] [←] [1] [2] [3] [4] [→] [Last]
              ↑ current
```

**Range = 4, Current Page = 19, Total Pages = 20**
```
[First] [←] [17] [18] [19] [20] [→] [Last]
                       ↑ current
```

---

## HTML Output Structure

### Complete HTML Structure

```html
<form id="form{random_id}" action="{page_link}" method="get">
    <div class="row justify-content-center">
        
        <!-- Left Column: Record Info & Controls -->
        <div class="col">
            <!-- Record Count -->
            <small class="text-muted">
                Records: 60 of 250
            </small>
            
            <!-- Page Count Dropdown -->
            <small class="text-muted">
                Page: 
                <select name="limit_start" class="custom form-control form-control-sm">
                    <option>1</option>
                    <option selected>2</option>
                    <option>3</option>
                </select>
                of 13
            </small>
            
            <!-- Limit Count Input -->
            <small class="text-muted">
                Records per page:
                <input type="number" name="limit_count" value="20" 
                       class="form-control form-control-sm" />
            </small>
        </div>
        
        <!-- Right Column: Pagination Links -->
        <div class="col-md-5">
            <ul class="pagination pagination-sm">
                <!-- First Page Link -->
                <li class="page-item">
                    <a class="page-link" href="?limit_start=1">First</a>
                </li>
                
                <!-- Previous Page Link -->
                <li class="page-item">
                    <a class="page-link" href="?limit_start=2">
                        <i class="fa fa-arrow-left"></i>
                    </a>
                </li>
                
                <!-- Page Number Links -->
                <li class="page-item">
                    <a class="page-link" href="?limit_start=1">1</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?limit_start=2">2</a>
                </li>
                <li class="page-item active">
                    <a class="page-link">3</a> <!-- Current page, no link -->
                </li>
                <li class="page-item">
                    <a class="page-link" href="?limit_start=4">4</a>
                </li>
                
                <!-- Next Page Link -->
                <li class="page-item">
                    <a class="page-link" href="?limit_start=4">
                        <i class="fa fa-arrow-right"></i>
                    </a>
                </li>
                
                <!-- Last Page Link -->
                <li class="page-item">
                    <a class="page-link" href="?limit_start=13">Last</a>
                </li>
            </ul>
        </div>
        
    </div>
</form>
```

---

## CSS Classes Used

### Bootstrap Classes

| Class | Purpose | Element |
|-------|---------|---------|
| `row` | Bootstrap grid row | Main container |
| `col` | Auto-width column | Left column (info) |
| `col-md-5` | 5-column width on medium+ screens | Right column (links) |
| `justify-content-center` | Center grid columns | Row alignment |
| `pagination` | Bootstrap pagination wrapper | `<ul>` element |
| `pagination-sm` | Small-sized pagination | `<ul>` element |
| `page-item` | Pagination item wrapper | `<li>` elements |
| `page-link` | Pagination link style | `<a>` elements |
| `active` | Current page highlight | Active `<li>` |
| `text-muted` | Muted text color | Info text |
| `form-control` | Bootstrap form input style | Input/select |
| `form-control-sm` | Small form controls | Input/select |
| `custom` | Custom form control | Select element |

### Responsive Classes

| Class | Purpose | Breakpoint |
|-------|---------|------------|
| `d-none` | Hide on mobile | Default |
| `d-block-sm` | Show on small+ screens | 576px+ |
| `col-md-5` | Apply column width on medium+ | 768px+ |

---

## JavaScript Functionality

### Auto-Submit on Change

**Page Dropdown Change**:
```javascript
onchange="$('#form{random_id}').submit()"
```
- Triggers form submission when page is selected
- Navigates to selected page immediately

**Limit Count Change**:
```javascript
onchange="$('#formselect{random_id}').val(0);$('#form{random_id}').submit()"
```
- Resets page dropdown to 0 (first page)
- Submits form to reload with new limit
- Prevents staying on page 10 when changing from 20 to 100 records per page

**Dependencies**: jQuery

---

## Internationalization Support

### Language Keys

The class uses language placeholders that need translation:

| Placeholder | English Translation | Context |
|-------------|---------------------|---------|
| `[html-lang-0088]` | "Records" | Record count label |
| `[html-lang-0089]` | "Page" | Page selector label |
| `[html-lang-0090]` | "Records per page" | Limit control label |
| `[html-lang-0126]` | "of" | Separator (X of Y) |
| `first_page` | "First" | First page button |
| `last_page` | "Last" | Last page button |

**Function Used**: `print_lang()`

**AI Agent Note**: Replace placeholders with actual language strings or implement `print_lang()` function.

---

## Common Usage Patterns

### Pattern 1: Basic Pagination in List View

```php
<!-- In Controller -->
function list_users() {
    $db = $this->GetModel();
    
    // Get pagination parameters
    list($limit_start, $limit_count) = $this->get_pagination(20);
    
    // Get total count
    $total_records = $db->getValue('users', 'count(*)');
    
    // Get paginated records
    $db->pageLimit = $limit_count;
    $records = $db->paginate('users', $limit_start);
    
    // Pass to view
    $data = new stdClass();
    $data->records = $records;
    $data->total_records = $total_records;
    
    $this->render_view('users/list.php', $data);
}
```

```php
<!-- In View: users/list.php -->
<table class="table">
    <!-- Display records -->
    <?php foreach($this->view_data->records as $user): ?>
    <tr>
        <td><?php echo $user['name']; ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<!-- Pagination -->
<?php
$pagination = new Pagination(
    $this->view_data->total_records,
    count($this->view_data->records)
);
$pagination->render();
?>
```

---

### Pattern 2: Custom Pagination Configuration

```php
<!-- Large dataset with custom settings -->
<?php
$pagination = new Pagination($total_records, $current_count);

// Show more page links
$pagination->pager_link_range = 7;

// Custom records per page
$pagination->limit_count = 50;

// Hide limit control (fixed page size)
$pagination->show_page_limit = false;

// Hide page number list (use only prev/next)
$pagination->show_page_number_list = false;

$pagination->render();
?>
```

**Result**: Simple prev/next navigation with 50 records per page

---

### Pattern 3: Minimal Pagination (Mobile-Friendly)

```php
<?php
$pagination = new Pagination($total_records, $current_count);

// Hide all extras, show only navigation
$pagination->show_record_count = false;
$pagination->show_page_count = false;
$pagination->show_page_limit = false;
$pagination->pager_link_range = 3; // Fewer page links

$pagination->render();
?>
```

**Result**: Compact pagination with only First, Prev, 1-3, Next, Last

---

### Pattern 4: Read-Only Pagination Info

```php
<?php
$pagination = new Pagination($total_records, $current_count);

// Show only information, no controls
$pagination->show_page_count = false;
$pagination->show_page_limit = false;
$pagination->show_page_number_list = false;

$pagination->render();
?>
```

**Result**: Displays "Records: X of Y" without interactive controls

---

### Pattern 5: AJAX Pagination

```php
<!-- In View -->
<div id="user-list">
    <!-- User table here -->
</div>

<div id="pagination-container">
    <?php
    $pagination = new Pagination($total_records, $current_count);
    $pagination->render();
    ?>
</div>

<script>
// Intercept pagination links
$('#pagination-container').on('click', '.page-link', function(e) {
    e.preventDefault();
    var url = $(this).attr('href');
    
    $.get(url, function(response) {
        $('#user-list').html($(response).find('#user-list').html());
        $('#pagination-container').html($(response).find('#pagination-container').html());
    });
});
</script>
```

---

## State Management

### URL Parameter Handling

**Query Parameters**:
- `limit_start`: Current page number (1-based)
- `limit_count`: Records per page
- Additional params preserved (search, filter, etc.)

**Example URLs**:
```
/users/list                              → Page 1, default limit
/users/list?limit_start=3                → Page 3, default limit
/users/list?limit_start=2&limit_count=50 → Page 2, 50 per page
/users/list?limit_start=2&search=john    → Page 2, search preserved
```

---

### Form Submission Behavior

**Page Change**:
1. User selects page from dropdown
2. Form submits via GET
3. `limit_start` parameter updated
4. `limit_count` preserved
5. Page reloads with new data

**Limit Change**:
1. User enters new limit
2. Page dropdown resets to 0 (first page)
3. Form submits via GET
4. Both parameters updated
5. Page reloads with new limit from page 1

---

## Calculation Reference

### Key Formulas

**Total Pages**:
```php
$numofpages = ceil($total_records / $limit_count);
```

**Record Position**:
```php
$record_position = min(($page_num * $limit_count), $total_records);
```
- Shows highest record number on current page
- Capped at total_records to avoid overflow

**Database Offset** (handled by controller):
```php
$limit_start = ($page_num - 1) * $limit_count;
```
- Converts page number to database offset
- Page 1 = offset 0, Page 2 = offset 20, etc.

**Examples**:

| Total | Limit | Page | Pages | Position | DB Offset |
|-------|-------|------|-------|----------|-----------|
| 100 | 20 | 1 | 5 | 20 | 0 |
| 100 | 20 | 3 | 5 | 60 | 40 |
| 100 | 20 | 5 | 5 | 100 | 80 |
| 97 | 20 | 5 | 5 | 97 | 80 |

---

## Edge Cases Handled

### 1. Single Page (numofpages = 1)
- Pagination links hidden
- Info and controls still shown (configurable)

### 2. Last Page with Partial Records
```php
// Total: 97, Limit: 20, Page: 5
// Shows: Records 97 of 97 (not 100 of 97)
$record_position = min((5 * 20), 97) = 97 ✓
```

### 3. First Page
- "First" and "Previous" links disabled (no href)
- Still rendered for UI consistency

### 4. Last Page
- "Next" and "Last" links disabled (no href)
- Still rendered for UI consistency

### 5. No Records
```php
$pagination = new Pagination(0, 0);
// Renders but shows "0 of 0"
```

---

## Dependencies

### Required Functions

| Function | Purpose | Implemented By |
|----------|---------|----------------|
| `set_current_page_link()` | Build URL with query params | Framework helper |
| `print_link()` | Output/sanitize URL | Framework helper |
| `print_lang()` | Translate language keys | Internationalization |
| `random_str()` | Generate random string | Framework helper |

### Required Constants

| Constant | Purpose | Example Value |
|----------|---------|---------------|
| `MAX_RECORD_COUNT` | Default records per page | 20 |

### External Libraries

| Library | Purpose | Elements |
|---------|---------|----------|
| jQuery | Form submission on change | `$('#form').submit()` |
| Bootstrap 4/5 | UI styling | `.pagination`, `.page-item` |
| Font Awesome | Arrow icons | `.fa-arrow-left`, `.fa-arrow-right` |

---

## Customization Guide

### Custom Styling

**Override Bootstrap Classes**:
```css
/* Make pagination larger */
.pagination-sm {
    font-size: 1rem;
}

/* Custom active page color */
.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}

/* Compact mobile view */
@media (max-width: 576px) {
    .pagination {
        font-size: 0.8rem;
    }
    .pagination .page-item {
        margin: 0 2px;
    }
}
```

---

### Custom Language Strings

**Replace Placeholders**:
```php
// Before render, modify class or template
// Or implement print_lang() function:

function print_lang($key) {
    $translations = [
        'first_page' => 'Primera',
        'last_page' => 'Última',
        // ... more translations
    ];
    echo $translations[$key] ?? $key;
}
```

---

### Extend Functionality

**Add Custom Buttons**:
```php
class ExtendedPagination extends Pagination {
    public function render() {
        // Call parent render
        parent::render();
        
        // Add export button
        ?>
        <div class="text-center mt-2">
            <a href="?export=csv" class="btn btn-sm btn-primary">
                Export to CSV
            </a>
        </div>
        <?php
    }
}
```

---

## Performance Considerations

### Database Queries

**Efficient Count Query**:
```php
// Good: Single count query
$total = $db->getValue('users', 'count(*)');

// Bad: Loading all records to count
$all_users = $db->get('users');
$total = count($all_users); // Inefficient for large tables
```

**Index Optimization**:
- Ensure pagination columns are indexed
- Use LIMIT with OFFSET efficiently
- Consider cursor-based pagination for very large datasets

---

### Memory Usage

**Large Datasets**:
```php
// Safe: Only load current page
$db->pageLimit = 20;
$records = $db->paginate('users', $start);

// Unsafe: Loading all records
$all = $db->get('users'); // Could use gigabytes
```

---

### Caching

**Cache Total Count**:
```php
// Cache count for 5 minutes if table is large
$cache_key = 'users_total_count';
$total = get_cache($cache_key);

if(!$total) {
    $total = $db->getValue('users', 'count(*)');
    set_cache($cache_key, $total, 300); // 5 min TTL
}
```

---

## Troubleshooting Guide

### Problem: Pagination Not Showing
**Check**:
1. Is `$total_records > $limit_count`?
2. Are both constructor parameters provided?
3. Is `render()` method called?

### Problem: Page Numbers Wrong
**Check**:
1. Is `$total_records` accurate?
2. Is `$current_record_count` = actual count of returned records?
3. Is `$limit_count` matching database query limit?

### Problem: Form Submit Not Working
**Check**:
1. Is jQuery loaded?
2. Are form IDs unique (multiple paginations on page)?
3. Check browser console for JavaScript errors

### Problem: Links Not Working
**Check**:
1. Are `set_current_page_link()` and `print_link()` functions defined?
2. Is URL routing configured correctly?
3. Check for URL encoding issues

### Problem: Language Placeholders Showing
**Solution**: Implement `print_lang()` function or replace placeholders with hardcoded text

### Problem: Styling Looks Broken
**Check**:
1. Is Bootstrap CSS loaded?
2. Correct Bootstrap version (4 or 5)?
3. Are Font Awesome icons loaded?

---

## Security Considerations

### Input Validation

**GET Parameters**:
```php
// Should be validated in controller before database query
$limit_start = filter_input(INPUT_GET, 'limit_start', FILTER_VALIDATE_INT);
$limit_count = filter_input(INPUT_GET, 'limit_count', FILTER_VALIDATE_INT);

// Enforce maximum limit
$limit_count = min($limit_count, 100); // Cap at 100
```

### SQL Injection Prevention
- Use parameterized queries in controller
- Validate numeric inputs
- Don't trust `$_GET` values directly in SQL

---

## Best Practices for AI Agents

### 1. Always Provide Accurate Counts
```php
// Get exact count from database
$total = $db->getValue('table', 'count(*)');
$current = count($records);
```

### 2. Match Pagination with Query
```php
// Ensure pagination limit matches database limit
list($start, $count) = $this->get_pagination(25);
$db->pageLimit = $count; // Same value
$records = $db->paginate('users', $start);

$pagination = new Pagination($total, count($records));
$pagination->limit_count = $count; // Same value
```

### 3. Configure Before Rendering
```php
// Set all properties before calling render()
$pagination = new Pagination($total, $current);
$pagination->pager_link_range = 5;
$pagination->show_page_limit = false;
$pagination->render(); // Call last
```

### 4. Consider Mobile Users
```php
// Use responsive configuration
$pagination->pager_link_range = 3; // Fewer links on mobile
```

### 5. Preserve Query Parameters
```php
// Pagination automatically preserves existing parameters
// No manual intervention needed for search, filters, etc.
```

---

## Integration with BaseController

**Controller Method**:
```php
function list_items() {
    $db = $this->GetModel();
    
    // BaseController's get_pagination() method
    list($start, $count) = $this->get_pagination(25);
    
    // Get total
    $total = $db->getValue('items', 'count(*)');
    
    // Get records
    $db->pageLimit = $count;
    $records = $db->paginate('items', $start);
    
    // Prepare view data
    $data = (object)[
        'records' => $records,
        'total' => $total
    ];
    
    $this->render_view('items/list.php', $data);
}
```

**View Template**:
```php
<!-- Display records -->
<table>
    <?php foreach($this->view_data->records as $item): ?>
    <tr><td><?php echo $item['name']; ?></td></tr>
    <?php endforeach; ?>
</table>

<!-- Pagination -->
<?php
$pagination = new Pagination(
    $this->view_data->total,
    count($this->view_data->records)
);
$pagination->render();
?>
```

---

## AI Agent Quick Reference

### Minimal Working Example
```php
$pagination = new Pagination(100, 20);
$pagination->render();
```

### Common Configuration
```php
$pagination = new Pagination($total, $current);
$pagination->limit_count = 25;
$pagination->pager_link_range = 5;
$pagination->show_page_limit = true;
$pagination->render();
```

### Method Calls
```
__construct($total, $current) → Initialize
set_link($page) → Generate URL
render() → Output HTML
```

### Properties to Control
```
pager_link_range → Number of page links
show_page_count → Page dropdown visibility
show_record_count → Record info visibility
show_page_limit → Limit control visibility
show_page_number_list → Page numbers visibility
```

---

## End of Documentation

**Last Updated**: Based on file analysis  
**Maintainer**: Auto-generated for AI agents  
**Feedback**: Use for pagination implementation and customization