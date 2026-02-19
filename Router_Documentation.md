# Router Class Documentation

**Type:** URL Parser & Page Dispatcher  
**Pattern:** Convention-Based Routing  
**AI Agent Compatibility:** High - Designed for programmatic understanding and code generation

---

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Routing Conventions](#routing-conventions)
4. [URL Structure & Patterns](#url-structure--patterns)
5. [Static Properties Reference](#static-properties-reference)
6. [Instance Properties Reference](#instance-properties-reference)
7. [Method Reference](#method-reference)
8. [Authorization Flow](#authorization-flow)
9. [Partial Views & Layouts](#partial-views--layouts)
10. [Controller Requirements](#controller-requirements)
11. [Usage Examples](#usage-examples)
12. [Best Practices for AI Agents](#best-practices-for-ai-agents)
13. [Common Patterns](#common-patterns)
14. [Troubleshooting Guide](#troubleshooting-guide)

---

## Overview

### Purpose
The Router class is a **convention-based URL dispatcher** that:
- **Parses URLs** into controller/action/parameters
- **Dispatches requests** to appropriate controller methods
- **Handles authorization** (logged in, forbidden, no role)
- **Manages views** (full page, partial views, custom layouts)
- **Provides route information** via static properties

### Key Characteristics
- **Convention over Configuration**: No route definitions needed
- **URL-to-Controller Mapping**: Automatic based on naming conventions
- **Static Route Access**: Current route info accessible anywhere via `Router::$property`
- **Built-in Authorization**: Handles user access control states
- **Flexible Rendering**: Supports full pages, partial views, and custom layouts

### Core Concept
```
URL: users/edit/123
  ↓
Page: users
  ↓
Controller: UsersController
  ↓
Action: edit
  ↓
Arguments: [123]
  ↓
Calls: UsersController->edit(123)
```

---

## Quick Start

### Basic Setup

```php
// In your index.php or bootstrap file
require_once 'Router.php';

$router = new Router();
$router->init();  // Automatically parses current URL and dispatches
```

### Manual Dispatch

```php
$router = new Router();
$router->run('users/edit/123');  // Manually dispatch a specific URL
```

### Accessing Route Information

```php
// From anywhere in your application
echo Router::$page_name;      // "users"
echo Router::$page_action;    // "edit"
echo Router::$page_id;        // "123"
echo Router::$controller_name; // "UsersController"
```

---

## Routing Conventions

### Controller Naming Convention

**Pattern:** `{PageName}Controller`

**Rules:**
1. Controller name MUST start with **capital letter**
2. Controller name MUST end with **"Controller"**
3. Controller class file should be in the `CONTROLLERS_DIR`

**Examples:**
```php
Page: "users"     → Controller: "UsersController"
Page: "products"  → Controller: "ProductsController"
Page: "blog-post" → Controller: "Blog-postController"
Page: "admin"     → Controller: "AdminController"
```

### Action Naming Convention

**Pattern:** `{actionname}` (lowercase)

**Rules:**
1. Action names MUST be **lowercase**
2. Action name "list" is automatically converted to "index"
3. Action must be a public method in the controller

**Examples:**
```php
Action: "index"  → Method: UsersController->index()
Action: "edit"   → Method: UsersController->edit()
Action: "view"   → Method: UsersController->view()
Action: "delete" → Method: UsersController->delete()
Action: "list"   → Method: UsersController->index() [auto-converted]
```

### Default Values

```php
DEFAULT_PAGE = "index"          // Default controller when none specified
DEFAULT_PAGE_ACTION = "index"   // Default action when none specified
```

**Examples:**
```php
URL: "/"              → IndexController->index()
URL: "/users"         → UsersController->index()
URL: "/users/"        → UsersController->index()
```

---

## URL Structure & Patterns

### URL Segment Mapping

```
URL: {page}/{action}/{param1}/{param2}/{param3}/...
      ↓      ↓        ↓        ↓        ↓
     [0]    [1]      [2]      [3]      [4]
```

### Pattern Examples

#### Pattern 1: Controller Only
```
URL: users
URL: users/

Parses to:
  page:   "users"
  action: "index" (default)
  
Dispatches to: UsersController->index()
```

#### Pattern 2: Controller + Action
```
URL: users/index
URL: users/list  (converted to index)

Parses to:
  page:   "users"
  action: "index"
  
Dispatches to: UsersController->index()
```

#### Pattern 3: Controller + Action + ID
```
URL: users/view/123
URL: users/edit/456
URL: users/delete/789

Parses to:
  page:       "users"
  action:     "view" | "edit" | "delete"
  page_id:    "123" | "456" | "789"
  args[0]:    "123" | "456" | "789"
  
Dispatches to: 
  UsersController->view(123)
  UsersController->edit(456)
  UsersController->delete(789)
```

#### Pattern 4: Controller + ID (Shorthand)
```
URL: users/123

Parses to:
  page:       "users"
  action:     "123" (treated as action)
  page_id:    null
  
Dispatches to: UsersController->123()

NOTE: This only works if the controller has a method named "123"
Better practice: users/view/123
```

#### Pattern 5: Controller + Action + Field/Value
```
URL: users/index/status/active
URL: users/search/email/john@example.com

Parses to:
  page:        "users"
  action:      "index" | "search"
  field_name:  "status" | "email"
  field_value: "active" | "john@example.com"
  args[0]:     "status" | "email"
  args[1]:     "active" | "john@example.com"
  
Dispatches to: 
  UsersController->index("status", "active")
  UsersController->search("email", "john@example.com")
```

#### Pattern 6: Controller + Action + Multiple Parameters
```
URL: users/filter/role/admin/status/active/age/25

Parses to:
  page:   "users"
  action: "filter"
  args:   ["role", "admin", "status", "active", "age", "25"]
  
Dispatches to: UsersController->filter("role", "admin", "status", "active", "age", "25")
```

#### Pattern 7: POST Request
```
URL: users/create
Method: POST
POST Data: { name: "John", email: "john@example.com" }

Parses to:
  page:   "users"
  action: "create"
  args:   [ $_POST array ]
  
Dispatches to: UsersController->create($_POST)

NOTE: $_POST is automatically appended as the last argument on POST requests
```

### Special URL Cases

#### Empty URL
```
URL: /
URL: "" (empty)

Dispatches to: IndexController->index()
```

#### Trailing Slashes
```
URL: users/
URL: users/edit/
URL: users/edit/123/

Trailing slashes are automatically removed via rtrim()
```

#### URL Encoding
```
URL: users/view/hello%20world

Automatically decoded to: "hello world"
All segments are urldecoded before processing
```

---

## Static Properties Reference

These properties are accessible from anywhere via `Router::$property_name`

### `Router::$page_name`

**Type:** `string|null`  
**Description:** The current page/controller name (lowercase).

**Example:**
```php
// URL: users/edit/123
echo Router::$page_name;  // "users"

// URL: products/view/456
echo Router::$page_name;  // "products"
```

---

### `Router::$page_action`

**Type:** `string|null`  
**Description:** The current action/method name (lowercase).

**Example:**
```php
// URL: users/edit/123
echo Router::$page_action;  // "edit"

// URL: products
echo Router::$page_action;  // "index" (default)
```

---

### `Router::$page_id`

**Type:** `string|null`  
**Description:** The first parameter after action (typically an ID).

**Example:**
```php
// URL: users/edit/123
echo Router::$page_id;  // "123"

// URL: users/index/status/active
echo Router::$page_id;  // "status"

// URL: users
echo Router::$page_id;  // null
```

---

### `Router::$field_name`

**Type:** `string|null`  
**Description:** The first parameter (often used as field name or category).

**Example:**
```php
// URL: users/index/status/active
echo Router::$field_name;  // "status"

// URL: products/filter/category/electronics
echo Router::$field_name;  // "category"

// URL: users/edit/123
echo Router::$field_name;  // "123"
```

**Note:** This is the same as the first argument (args[0]) and page_id.

---

### `Router::$field_value`

**Type:** `string|null`  
**Description:** The second parameter (often used as field value).

**Example:**
```php
// URL: users/index/status/active
echo Router::$field_value;  // "active"

// URL: products/filter/category/electronics
echo Router::$field_value;  // "electronics"

// URL: users/edit/123
echo Router::$field_value;  // null
```

**Note:** This is the same as the second argument (args[1]).

---

### `Router::$page_url`

**Type:** `string|null`  
**Description:** The full relative URL path.

**Example:**
```php
// URL: users/edit/123/status/active
echo Router::$page_url;  // "users/edit/123/status/active"

// URL: products
echo Router::$page_url;  // "products"
```

---

### `Router::$controller_name`

**Type:** `string|null`  
**Description:** The full controller class name.

**Example:**
```php
// URL: users/edit/123
echo Router::$controller_name;  // "UsersController"

// URL: blog-posts/view/456
echo Router::$controller_name;  // "Blog-postsController"
```

---

## Instance Properties Reference

These properties are set on the Router instance.

### `$is_partial_view`

**Type:** `bool`  
**Default:** `false`  
**Description:** When true, renders the view without the layout wrapper.

**Usage:**
```php
$router = new Router();
$router->is_partial_view = true;
$router->run('users/edit/123');
// Renders only the view content, no layout
```

**Use Cases:**
- AJAX requests returning HTML fragments
- Modal content loading
- Component rendering
- Embedded views

---

### `$page_props`

**Type:** `array`  
**Default:** `[]`  
**Description:** Custom properties to pass to the controller's view.

**Usage:**
```php
$router = new Router();
$router->page_props = [
    'title' => 'Custom Title',
    'show_header' => false,
    'custom_data' => ['key' => 'value']
];
$router->run('users/index');

// In the view:
// $this->title → "Custom Title"
// $this->custom_data → ['key' => 'value']
```

---

### `$force_layout`

**Type:** `string|null`  
**Default:** `null`  
**Description:** Override the default page layout with a specific layout file.

**Usage:**
```php
$router = new Router();
$router->force_layout = 'admin_layout.php';
$router->run('users/index');
// Uses admin_layout.php instead of default layout
```

**Common Layouts:**
- `main_layout.php` - Standard page layout
- `info_layout.php` - Information/error pages
- `admin_layout.php` - Admin panel layout
- `public_layout.php` - Public-facing pages

---

### `$partial_view`

**Type:** `mixed|null`  
**Default:** `null`  
**Description:** Custom partial view identifier or data.

**Usage:**
```php
$router = new Router();
$router->is_partial_view = true;
$router->partial_view = 'user_card';
$router->run('users/view/123');
```

---

### `$request`

**Type:** `array`  
**Default:** `[]`  
**Description:** Custom request data to pass to the controller.

**Usage:**
```php
$router = new Router();
$router->request = [
    'user_id' => 123,
    'action' => 'update',
    'data' => ['name' => 'John']
];
$router->run('users/process');

// In the controller:
// $this->request → ['user_id' => 123, ...]
```

---

## Method Reference

### `init()`

**Purpose:** Initialize router from the current HTTP request URL.

**Signature:**
```php
public function init(): void
```

**Description:** 
- Extracts the URL path from `$_SERVER['REQUEST_URI']`
- Removes the script base path
- Calls `run()` with the parsed path

**Usage:**
```php
// In index.php
$router = new Router();
$router->init();  // Automatically dispatches based on current URL
```

**Process Flow:**
```
$_SERVER['REQUEST_URI']: /myapp/users/edit/123
$_SERVER['SCRIPT_NAME']: /myapp/index.php
                            ↓
Base path: /myapp/
                            ↓
Page URL: users/edit/123
                            ↓
run('users/edit/123')
```

---

### `run(string $url)`

**Purpose:** Parse URL and dispatch to appropriate controller/action.

**Signature:**
```php
public function run(string $url): void
```

**Parameters:**
- `$url` - URL path to parse (e.g., "users/edit/123")

**Description:**
1. Splits URL into segments
2. Extracts page, action, and parameters
3. Sanitizes all inputs
4. Loads controller class
5. Checks method exists
6. Handles authorization
7. Calls controller method with arguments

**Usage:**
```php
$router = new Router();

// Dispatch specific URL
$router->run('users/edit/123');

// Dispatch default
$router->run('');  // Uses DEFAULT_PAGE and DEFAULT_PAGE_ACTION

// Dispatch with parameters
$router->run('products/filter/category/electronics/price/100');
```

**Security Features:**
- All URL segments are `urldecode()`d
- All segments are sanitized with `FILTER_SANITIZE_SPECIAL_CHARS`
- Removes potential XSS and injection attempts

**Error Handling:**
- Controller class not found → `page_not_found()`
- Action method not found → `page_not_found()`
- Authorization failed → Renders appropriate error page

---

### `page_not_found(string $msg)`

**Purpose:** Display 404 error page.

**Signature:**
```php
public function page_not_found(string $msg): void
```

**Parameters:**
- `$msg` - Error message to display

**Description:**
- Creates a new `BaseView` instance
- Renders `errors/error_404.php` with the message
- Uses `info_layout.php` layout
- Calls `exit` to stop execution

**Usage:**
```php
// Automatically called by Router when page not found
// Can also be called manually:
$router->page_not_found("Custom error message");
```

**Default Error Messages:**
```php
// Action not found
"{action} Action Was Not Found In {ControllerName}"

// Controller not found
"{ControllerName} Was Not Found In Controller Directory. Please Check {CONTROLLERS_DIR}"
```

---

## Authorization Flow

The Router integrates with controller authorization states. After dispatching to a controller, it checks the controller's `$status` property.

### Authorization States

#### `AUTHORIZED` - User Has Access

**Behavior:** Execute the action normally.

```php
// In controller
class UsersController extends BaseController {
    public function __construct() {
        parent::__construct();
        // User is authorized
        $this->status = AUTHORIZED;
    }
    
    public function index() {
        // This method executes
    }
}
```

---

#### `UNAUTHORIZED` - User Not Logged In

**Behavior:** 
- Render login page
- Save current URL for redirect after login
- Use `main_layout.php`

```php
// In controller
class AdminController extends BaseController {
    public function __construct() {
        parent::__construct();
        if (!is_logged_in()) {
            $this->status = UNAUTHORIZED;
        }
    }
}

// Router handles this by:
// 1. Saving current URL: set_session("login_redirect_url", get_current_url())
// 2. Rendering: render_view("index/login.php", null, "main_layout.php")
```

**Redirect After Login:**
```php
// After successful login
if (has_session("login_redirect_url")) {
    $redirect = get_session("login_redirect_url");
    clear_session("login_redirect_url");
    redirect($redirect);
}
```

---

#### `FORBIDDEN` - User Forbidden (Banned/Suspended)

**Behavior:** 
- Render forbidden error page
- Use `info_layout.php`

```php
// In controller
class UsersController extends BaseController {
    public function __construct() {
        parent::__construct();
        $user = get_current_user();
        if ($user['status'] == 'banned') {
            $this->status = FORBIDDEN;
        }
    }
}

// Router renders: errors/forbidden.php
```

---

#### `NOROLE` - User Has No Permission

**Behavior:**
- Render "no permission" error page
- Use `info_layout.php`

```php
// In controller
class AdminController extends BaseController {
    public function __construct() {
        parent::__construct();
        $user = get_current_user();
        if ($user['role'] != 'admin') {
            $this->status = NOROLE;
        }
    }
}

// Router renders: errors/error_no_permission.php
```

---

### Authorization Flow Diagram

```
Request → Router → Load Controller
                         ↓
                  Check $controller->status
                         ↓
          ┌──────────────┼──────────────┐
          ↓              ↓              ↓
      AUTHORIZED    UNAUTHORIZED    FORBIDDEN/NOROLE
          ↓              ↓              ↓
   Execute Action   Login Page    Error Page
```

---

## Partial Views & Layouts

### Full Page Rendering (Default)

```php
$router = new Router();
$router->run('users/index');

// Renders:
// - Layout: main_layout.php (or controller's default)
// - View: users/index.php
// - Full HTML page with header, footer, etc.
```

---

### Partial View Rendering

**Purpose:** Render only the view content without layout wrapper.

```php
$router = new Router();
$router->is_partial_view = true;
$router->run('users/index');

// Renders:
// - View: users/index.php
// - NO layout
// - Just the content HTML
```

**Use Cases:**
```php
// AJAX request for user list
$router = new Router();
$router->is_partial_view = true;
$router->run('users/list');
// Returns: <div class="user-list">...</div>

// Modal content
$router = new Router();
$router->is_partial_view = true;
$router->page_props = ['modal' => true];
$router->run('users/edit/123');
// Returns: <form>...</form>

// Component rendering
$router = new Router();
$router->is_partial_view = true;
$router->partial_view = 'user_card';
$router->run('users/view/123');
```

---

### Custom Layout

**Purpose:** Use a specific layout instead of the default.

```php
$router = new Router();
$router->force_layout = 'admin_layout.php';
$router->run('dashboard/index');

// Renders with admin_layout.php instead of default
```

**Common Scenarios:**
```php
// Admin pages
$router->force_layout = 'admin_layout.php';

// Public pages
$router->force_layout = 'public_layout.php';

// Error/info pages
$router->force_layout = 'info_layout.php';

// Print view
$router->force_layout = 'print_layout.php';

// Email template
$router->force_layout = 'email_layout.php';
```

---

### Passing Properties to View

```php
$router = new Router();
$router->is_partial_view = true;
$router->page_props = [
    'title' => 'User Profile',
    'show_back_button' => true,
    'user_id' => 123,
    'mode' => 'edit'
];
$router->run('users/view/123');

// In the view (users/view.php):
echo $this->title;  // "User Profile"
if ($this->show_back_button) {
    echo '<a href="...">Back</a>';
}
```

---

## Controller Requirements

For the Router to work correctly, controllers must follow these requirements:

### 1. Naming Convention
```php
// ✅ CORRECT
class UsersController { }
class ProductsController { }
class BlogPostsController { }

// ❌ WRONG
class users { }  // Must start with capital
class User { }   // Must end with "Controller"
class userscontroller { }  // Wrong case
```

---

### 2. File Location
Controllers must be in the `CONTROLLERS_DIR` directory.

```php
define('CONTROLLERS_DIR', './controllers/');

// Expected file structure:
// ./controllers/UsersController.php
// ./controllers/ProductsController.php
// ./controllers/AdminController.php
```

---

### 3. Required Properties

```php
class UsersController extends BaseController {
    
    // Authorization status
    public $status = AUTHORIZED;  // or UNAUTHORIZED, FORBIDDEN, NOROLE
    
    // View instance
    public $view;
    
    public function __construct() {
        parent::__construct();
        // Initialize
    }
}
```

---

### 4. Required Methods

```php
class UsersController extends BaseController {
    
    // Set route information
    public function set_route($route) {
        $this->route = $route;
    }
    
    // Set request data (for partial views)
    public function set_request($request) {
        $this->request = $request;
    }
    
    // Render view
    public function render_view($view_path, $data = null, $layout = null) {
        $this->view->render($view_path, $data, $layout);
    }
}
```

---

### 5. Action Method Signature

```php
class UsersController extends BaseController {
    
    // No parameters
    public function index() { }
    
    // With ID parameter
    public function view($id) { }
    
    // With multiple parameters
    public function filter($field, $value) { }
    
    // POST request (POST data is last argument)
    public function create($postData) { }
    
    // Mixed parameters + POST
    public function update($id, $postData) { }
}
```

---

### 6. Complete Controller Example

```php
class UsersController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        
        // Check authorization
        if (!is_logged_in()) {
            $this->status = UNAUTHORIZED;
            return;
        }
        
        // Check role
        $user = get_current_user();
        if (!in_array($user['role'], ['admin', 'moderator'])) {
            $this->status = NOROLE;
            return;
        }
        
        // Authorized
        $this->status = AUTHORIZED;
    }
    
    // GET /users OR /users/index
    public function index() {
        $db = get_db();
        $users = $db->get('users');
        $this->render_view('users/index.php', $users);
    }
    
    // GET /users/view/123
    public function view($id) {
        $db = get_db();
        $user = $db->where('id', $id)->getOne('users');
        
        if (!$user) {
            $this->render_view('errors/error_404.php', 'User not found', 'info_layout.php');
            return;
        }
        
        $this->render_view('users/view.php', $user);
    }
    
    // GET /users/edit/123
    public function edit($id) {
        $db = get_db();
        $user = $db->where('id', $id)->getOne('users');
        $this->render_view('users/edit.php', $user);
    }
    
    // POST /users/update/123
    public function update($id, $postData) {
        $db = get_db();
        
        // Sanitize and validate
        $data = [
            'name' => filter_var($postData['name'], FILTER_SANITIZE_STRING),
            'email' => filter_var($postData['email'], FILTER_VALIDATE_EMAIL)
        ];
        
        $result = $db->where('id', $id)->update('users', $data);
        
        if ($result) {
            redirect('users/view/' . $id);
        } else {
            $this->render_view('users/edit.php', ['error' => 'Update failed']);
        }
    }
    
    // GET /users/delete/123
    public function delete($id) {
        $db = get_db();
        $db->where('id', $id)->delete('users');
        redirect('users/index');
    }
    
    // GET /users/filter/status/active
    public function filter($field, $value) {
        $db = get_db();
        $users = $db->where($field, $value)->get('users');
        $this->render_view('users/index.php', $users);
    }
}
```

---

## Usage Examples

### Example 1: Basic Page Navigation

```php
// User clicks: <a href="users">View Users</a>
// URL: /users

// Router processes:
$router = new Router();
$router->init();

// Result:
Router::$page_name = "users"
Router::$page_action = "index"
Router::$controller_name = "UsersController"

// Dispatches to:
UsersController->index()
```

---

### Example 2: View Single Record

```php
// User clicks: <a href="users/view/123">View User</a>
// URL: /users/view/123

// Router processes:
Router::$page_name = "users"
Router::$page_action = "view"
Router::$page_id = "123"

// Dispatches to:
UsersController->view("123")

// In controller:
public function view($id) {
    $db = get_db();
    $user = $db->where('id', $id)->getOne('users');
    $this->render_view('users/view.php', $user);
}
```

---

### Example 3: Edit Record

```php
// User clicks: <a href="users/edit/123">Edit</a>
// URL: /users/edit/123

// Dispatches to:
UsersController->edit("123")

// User submits form (POST request)
// URL: /users/edit/123
// POST: {name: "John Updated", email: "john@new.com"}

// Dispatches to:
UsersController->edit("123", $_POST)

// In controller:
public function edit($id, $postData = null) {
    $db = get_db();
    
    if (is_post_request()) {
        // Handle update
        $db->where('id', $id)->update('users', $postData);
        redirect('users/view/' . $id);
    } else {
        // Show form
        $user = $db->where('id', $id)->getOne('users');
        $this->render_view('users/edit.php', $user);
    }
}
```

---

### Example 4: Filtered List

```php
// User clicks: <a href="users/index/status/active">Active Users</a>
// URL: /users/index/status/active

// Router processes:
Router::$field_name = "status"
Router::$field_value = "active"

// Dispatches to:
UsersController->index("status", "active")

// In controller:
public function index($field = null, $value = null) {
    $db = get_db();
    
    if ($field && $value) {
        $users = $db->where($field, $value)->get('users');
    } else {
        $users = $db->get('users');
    }
    
    $this->render_view('users/index.php', $users);
}
```

---

### Example 5: AJAX Partial View

```php
// JavaScript AJAX request
$.get('users/list', function(html) {
    $('#user-container').html(html);
});

// In PHP endpoint:
$router = new Router();
$router->is_partial_view = true;
$router->run('users/list');

// Returns HTML fragment without layout:
// <div class="user-list">
//   <div class="user">...</div>
//   <div class="user">...</div>
// </div>
```

---

### Example 6: Modal Content

```php
// Load edit form in modal
$.get('users/edit/123?partial=1', function(html) {
    $('#modal-body').html(html);
    $('#modal').modal('show');
});

// In PHP:
if (isset($_GET['partial'])) {
    $router = new Router();
    $router->is_partial_view = true;
    $router->page_props = ['modal' => true];
    $router->run('users/edit/123');
}

// Returns form HTML only
```

---

### Example 7: Custom Layout for Admin

```php
// Admin dashboard request
$router = new Router();

// Check if admin area
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($url, 'admin/') === 0) {
    $router->force_layout = 'admin_layout.php';
}

$router->init();
```

---

### Example 8: Authorization Check

```php
// Attempting to access protected page
// URL: /admin/users

// AdminController checks authorization:
class AdminController extends BaseController {
    public function __construct() {
        parent::__construct();
        
        if (!is_logged_in()) {
            $this->status = UNAUTHORIZED;
            return;
        }
        
        $user = get_current_user();
        if ($user['role'] != 'admin') {
            $this->status = NOROLE;
            return;
        }
        
        $this->status = AUTHORIZED;
    }
}

// Router checks status after construction:
// - UNAUTHORIZED → renders login page, saves redirect URL
// - NOROLE → renders error_no_permission.php
// - AUTHORIZED → calls users() method
```

---

## Best Practices for AI Agents

### 1. Always Follow Naming Conventions

```php
// ✅ CORRECT
class UsersController { }
public function index() { }
public function view($id) { }

// ❌ WRONG
class users { }  // Missing capital and "Controller"
public function Index() { }  // Action must be lowercase
```

---

### 2. Handle Both GET and POST in Same Action

```php
// ✅ CORRECT
public function edit($id, $postData = null) {
    if (is_post_request()) {
        // Handle POST (update)
        $this->updateUser($id, $postData);
    } else {
        // Handle GET (show form)
        $this->showEditForm($id);
    }
}

// ❌ WRONG (separate methods for GET/POST)
public function edit_get($id) { }  // Router won't route to this
public function edit_post($id) { }
```

---

### 3. Check Authorization in Constructor

```php
// ✅ CORRECT
class UsersController extends BaseController {
    public function __construct() {
        parent::__construct();
        
        if (!is_logged_in()) {
            $this->status = UNAUTHORIZED;
            return;  // Stop further execution
        }
        
        $this->status = AUTHORIZED;
    }
}

// ❌ WRONG (checking in each method)
public function index() {
    if (!is_logged_in()) {
        redirect('login');
    }
    // ... duplicated in every method
}
```

---

### 4. Use Optional Parameters with Defaults

```php
// ✅ CORRECT
public function index($field = null, $value = null, $page = 1) {
    // Handle with or without parameters
    if ($field && $value) {
        // Filtered list
    } else {
        // All records
    }
}

// ❌ WRONG (required parameters)
public function index($field, $value) {
    // Breaks on /users/index
}
```

---

### 5. Sanitize Input in Controller, Not Router

```php
// ✅ CORRECT (Router sanitizes, controller validates)
public function create($postData) {
    // Validate
    $name = filter_var($postData['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($postData['email'], FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $this->render_view('users/create.php', ['error' => 'Invalid email']);
        return;
    }
    
    // Insert
    $db->insert('users', ['name' => $name, 'email' => $email]);
}

// Note: Router already does FILTER_SANITIZE_SPECIAL_CHARS on URL segments
```

---

### 6. Use Partial Views for AJAX

```php
// ✅ CORRECT
if (is_ajax_request()) {
    $router = new Router();
    $router->is_partial_view = true;
    $router->run('users/list');
} else {
    $router = new Router();
    $router->init();
}

// Or check in controller:
public function list() {
    $users = $this->getUsers();
    
    if (is_ajax_request()) {
        $this->view->is_partial_view = true;
    }
    
    $this->render_view('users/list.php', $users);
}
```

---

### 7. Handle 404s Gracefully

```php
// ✅ CORRECT
public function view($id) {
    $db = get_db();
    $user = $db->where('id', $id)->getOne('users');
    
    if (!$user) {
        $this->render_view('errors/error_404.php', 'User not found', 'info_layout.php');
        return;
    }
    
    $this->render_view('users/view.php', $user);
}

// ❌ WRONG (no check)
public function view($id) {
    $user = $db->where('id', $id)->getOne('users');
    $this->render_view('users/view.php', $user);  // Errors if user is null
}
```

---

### 8. Use Redirects After POST

```php
// ✅ CORRECT (PRG pattern: Post-Redirect-Get)
public function create($postData) {
    $db = get_db();
    $id = $db->insert('users', $postData);
    
    // Redirect to view page
    redirect('users/view/' . $id);
}

// ❌ WRONG (rendering after POST)
public function create($postData) {
    $db = get_db();
    $db->insert('users', $postData);
    
    $this->render_view('users/index.php');  // Browser refresh re-submits form
}
```

---

### 9. Document URL Patterns

```php
/**
 * UsersController
 * 
 * URL Patterns:
 * GET  /users              → index()     List all users
 * GET  /users/view/123     → view($id)   View single user
 * GET  /users/edit/123     → edit($id)   Show edit form
 * POST /users/edit/123     → edit($id, $post)  Update user
 * GET  /users/delete/123   → delete($id) Delete user
 * GET  /users/create       → create()    Show create form
 * POST /users/create       → create($post)  Create new user
 * GET  /users/filter/x/y   → filter($field, $value)  Filtered list
 */
class UsersController extends BaseController { }
```

---

### 10. Use Static Properties for Context

```php
// ✅ CORRECT (access route info anywhere)
function generate_breadcrumb() {
    $breadcrumb = [
        '<a href="/">Home</a>',
        '<a href="' . Router::$page_name . '">' . ucfirst(Router::$page_name) . '</a>'
    ];
    
    if (Router::$page_action != 'index') {
        $breadcrumb[] = ucfirst(Router::$page_action);
    }
    
    return implode(' > ', $breadcrumb);
}

// Usage in any view:
echo generate_breadcrumb();
// Output: Home > Users > Edit
```

---

## Common Patterns

### Pattern 1: CRUD Controller Template

```php
class {Name}Controller extends BaseController {
    
    // List all
    public function index($field = null, $value = null) {
        $db = get_db();
        
        if ($field && $value) {
            $items = $db->where($field, $value)->get('{table}');
        } else {
            $items = $db->get('{table}');
        }
        
        $this->render_view('{name}/index.php', $items);
    }
    
    // View single
    public function view($id) {
        $db = get_db();
        $item = $db->where('id', $id)->getOne('{table}');
        
        if (!$item) {
            $this->render_view('errors/error_404.php', 'Not found', 'info_layout.php');
            return;
        }
        
        $this->render_view('{name}/view.php', $item);
    }
    
    // Create/Edit form + handle POST
    public function edit($id = null, $postData = null) {
        $db = get_db();
        
        if (is_post_request()) {
            // Handle update/create
            if ($id) {
                $db->where('id', $id)->update('{table}', $postData);
            } else {
                $id = $db->insert('{table}', $postData);
            }
            redirect('{name}/view/' . $id);
        } else {
            // Show form
            $item = $id ? $db->where('id', $id)->getOne('{table}') : null;
            $this->render_view('{name}/edit.php', $item);
        }
    }
    
    // Delete
    public function delete($id) {
        $db = get_db();
        $db->where('id', $id)->delete('{table}');
        redirect('{name}/index');
    }
}
```

---

### Pattern 2: Authorization Wrapper

```php
class AdminController extends BaseController {
    
    protected function checkAuthorization() {
        if (!is_logged_in()) {
            $this->status = UNAUTHORIZED;
            return false;
        }
        
        $user = get_current_user();
        if ($user['role'] != 'admin') {
            $this->status = NOROLE;
            return false;
        }
        
        $this->status = AUTHORIZED;
        return true;
    }
    
    public function __construct() {
        parent::__construct();
        $this->checkAuthorization();
    }
    
    // All methods only execute if authorized
    public function users() {
        // Only accessible to admins
    }
}
```

---

### Pattern 3: AJAX Response Handler

```php
class ApiController extends BaseController {
    
    protected function jsonResponse($data, $success = true) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'data' => $data
        ]);
        exit;
    }
    
    public function getUsers() {
        $db = get_db();
        $users = $db->get('users');
        $this->jsonResponse($users);
    }
    
    public function createUser($postData) {
        $db = get_db();
        $id = $db->insert('users', $postData);
        $this->jsonResponse(['id' => $id]);
    }
}

// Called via AJAX:
// $.post('api/createUser', userData, function(response) {
//     console.log(response.data.id);
// });
```

---

### Pattern 4: Pagination Handler

```php
public function index($page = 1) {
    $db = get_db();
    $db->setPageLimit(20);
    
    $users = $db->paginate('users', $page);
    
    $data = [
        'users' => $users,
        'current_page' => $page,
        'total_pages' => $db->totalPages,
        'total_count' => $db->totalCount
    ];
    
    $this->render_view('users/index.php', $data);
}

// URLs:
// /users/index/1
// /users/index/2
// /users/index/3
```

---

### Pattern 5: Search Handler

```php
public function search($query = null) {
    if (!$query && isset($_GET['q'])) {
        $query = $_GET['q'];
    }
    
    $db = get_db();
    
    if ($query) {
        $results = $db
            ->where('name', "%{$query}%", 'LIKE')
            ->orWhere('email', "%{$query}%", 'LIKE')
            ->get('users');
    } else {
        $results = [];
    }
    
    if (is_ajax_request()) {
        $this->view->is_partial_view = true;
    }
    
    $this->render_view('users/search.php', $results);
}

// URLs:
// /users/search/john
// /users/search?q=john
```

---

## Troubleshooting Guide

### Problem: "Controller Not Found"

**Symptoms:** Error message "{Controller}Controller Was Not Found"

**Possible Causes:**
1. Controller file not in `CONTROLLERS_DIR`
2. Controller class name doesn't match file name
3. Incorrect naming convention

**Solutions:**
```php
// ✅ Check file exists
ls ./controllers/UsersController.php

// ✅ Check class name matches
class UsersController { }  // Not 'users' or 'User'

// ✅ Check file is included/autoloaded
require_once CONTROLLERS_DIR . 'UsersController.php';
```

---

### Problem: "Action Not Found"

**Symptoms:** Error message "{action} Action Was Not Found In {Controller}"

**Possible Causes:**
1. Method doesn't exist in controller
2. Method is private or protected
3. Method name has wrong case

**Solutions:**
```php
// ✅ Method must be public
public function edit($id) { }  // Not private or protected

// ✅ Method name must be lowercase (in URL)
// URL: users/edit/123
public function edit($id) { }  // Not Edit() or EDIT()

// ✅ Check method exists
if (method_exists('UsersController', 'edit')) {
    echo "Method exists";
}
```

---

### Problem: "Blank Page" or "No Output"

**Possible Causes:**
1. Missing `render_view()` call
2. Authorization blocking execution
3. Early `return` statement

**Solutions:**
```php
// ✅ Always call render_view
public function index() {
    $users = $this->getUsers();
    $this->render_view('users/index.php', $users);  // Don't forget this
}

// ✅ Check authorization status
public function __construct() {
    parent::__construct();
    echo "Status: " . $this->status;  // Debug
}

// ✅ Check for early returns
public function view($id) {
    $user = $this->getUser($id);
    if (!$user) {
        $this->render_view('errors/404.php');
        return;  // This stops execution
    }
    $this->render_view('users/view.php', $user);
}
```

---

### Problem: "POST Data Not Received"

**Possible Causes:**
1. Forgot to add `$postData` parameter
2. Not checking `is_post_request()`
3. Form method is GET not POST

**Solutions:**
```php
// ✅ Add POST parameter
public function create($postData) {  // Router automatically passes $_POST
    var_dump($postData);
}

// ✅ Check request method
if (is_post_request()) {
    // Handle POST
}

// ✅ Check form method
<form method="POST" action="users/create">
```

---

### Problem: "Parameters Not Received"

**Possible Causes:**
1. Parameter order doesn't match URL
2. Missing parameters
3. POST data shifts parameters

**Solutions:**
```php
// URL: users/filter/status/active

// ✅ CORRECT parameter order
public function filter($field, $value) {
    // $field = "status"
    // $value = "active"
}

// ❌ WRONG parameter order
public function filter($value, $field) {
    // $value = "status" (WRONG!)
    // $field = "active" (WRONG!)
}

// ✅ Handle POST with URL params
// POST /users/update/123
public function update($id, $postData) {
    // $id = "123" from URL
    // $postData = $_POST array
}
```

---

### Problem: "Authorization Not Working"

**Possible Causes:**
1. Status set after method execution
2. Status not checked in constructor
3. Wrong status constant

**Solutions:**
```php
// ✅ Set status in constructor
public function __construct() {
    parent::__construct();
    
    if (!is_logged_in()) {
        $this->status = UNAUTHORIZED;
        return;  // Stop execution
    }
}

// ❌ WRONG: Setting status in method
public function index() {
    if (!is_logged_in()) {
        $this->status = UNAUTHORIZED;  // Too late!
    }
}

// ✅ Use correct constants
AUTHORIZED
UNAUTHORIZED
FORBIDDEN
NOROLE
```

---

### Problem: "Partial View Shows Full Layout"

**Possible Causes:**
1. `is_partial_view` not set before `run()`
2. View overriding setting
3. Wrong view file

**Solutions:**
```php
// ✅ Set before run()
$router = new Router();
$router->is_partial_view = true;  // Set first
$router->run('users/list');

// ✅ Check in controller
public function list() {
    echo "Partial: " . ($this->view->is_partial_view ? 'yes' : 'no');
}

// ✅ Ensure view respects setting
// In BaseView or view class, check is_partial_view before rendering layout
```

---

## URL Routing Quick Reference

| URL Pattern | Controller | Method | Arguments |
|-------------|------------|--------|-----------|
| `/` | IndexController | index() | [] |
| `/users` | UsersController | index() | [] |
| `/users/` | UsersController | index() | [] |
| `/users/index` | UsersController | index() | [] |
| `/users/list` | UsersController | index() | [] (list→index) |
| `/users/123` | UsersController | 123() | [] (❌ invalid) |
| `/users/view/123` | UsersController | view() | [123] |
| `/users/edit/123` | UsersController | edit() | [123] |
| `/users/delete/123` | UsersController | delete() | [123] |
| `/users/index/status/active` | UsersController | index() | [status, active] |
| `/users/filter/role/admin` | UsersController | filter() | [role, admin] |
| `/users/search/john/doe` | UsersController | search() | [john, doe] |
| `POST /users/create` | UsersController | create() | [$_POST] |
| `POST /users/edit/123` | UsersController | edit() | [123, $_POST] |

---

## Security Considerations

### 1. Automatic Input Sanitization

Router automatically sanitizes URL segments:

```php
// All segments are sanitized with:
FILTER_SANITIZE_SPECIAL_CHARS

// Example:
URL: /users/view/<script>alert('xss')</script>
     ↓
$id = "&lt;script&gt;alert('xss')&lt;/script&gt;"
```

### 2. URL Decoding

```php
// Automatically decoded:
URL: /users/view/hello%20world
     ↓
$id = "hello world"
```

### 3. POST Data Handling

```php
// POST data is NOT sanitized by Router
// Must sanitize in controller:

public function create($postData) {
    $name = filter_var($postData['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($postData['email'], FILTER_VALIDATE_EMAIL);
    
    // Use sanitized values
}
```

### 4. SQL Injection Protection

```php
// Router doesn't interact with database
// Use PDODb with parameter binding in controller:

public function view($id) {
    $db = get_db();
    // ✅ Safe: uses prepared statements
    $user = $db->where('id', $id)->getOne('users');
}
```

---

## AI Agent Decision Tree

```
Start Request
    │
    ├─ Parse URL
    │   ├─ Extract page name → ucfirst() + "Controller"
    │   ├─ Extract action → lowercase
    │   └─ Extract parameters → urldecode() + sanitize
    │
    ├─ Check controller exists?
    │   ├─ No → page_not_found()
    │   └─ Yes → Continue
    │
    ├─ Check method exists?
    │   ├─ No → page_not_found()
    │   └─ Yes → Continue
    │
    ├─ Instantiate controller
    │   └─ Set authorization status
    │
    ├─ Check authorization
    │   ├─ UNAUTHORIZED → login page
    │   ├─ FORBIDDEN → forbidden page
    │   ├─ NOROLE → no permission page
    │   └─ AUTHORIZED → Continue
    │
    ├─ Prepare arguments
    │   ├─ URL parameters
    │   └─ + $_POST if POST request
    │
    └─ Execute controller method
        └─ Render view
```

---

## End of Documentation

This documentation is optimized for AI agent comprehension with:
- ✅ Complete URL routing patterns
- ✅ Controller/action naming conventions
- ✅ Authorization flow documentation
- ✅ Practical examples for every pattern
- ✅ Troubleshooting guide
- ✅ Security considerations
- ✅ Decision trees and quick references

**For AI Agents:** This Router uses convention-based routing. Always follow naming conventions (ControllerName + "Controller", lowercase actions), check authorization in constructor, and handle both GET and POST in the same method when appropriate.
