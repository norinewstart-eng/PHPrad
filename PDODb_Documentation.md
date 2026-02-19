# PDODb Class Documentation

**Version:** 1.1.0  
**Type:** Database Access Layer / PDO Wrapper  
**License:** LGPL 3.0  
**AI Agent Compatibility:** High - Designed for programmatic understanding and usage

---

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Installation & Connection](#installation--connection)
4. [Core Concepts](#core-concepts)
5. [Query Building Pattern](#query-building-pattern)
6. [Complete Method Reference](#complete-method-reference)
7. [Usage Examples by Category](#usage-examples-by-category)
8. [Transaction Management](#transaction-management)
9. [Error Handling](#error-handling)
10. [Advanced Features](#advanced-features)
11. [Design Patterns](#design-patterns)
12. [Best Practices for AI Agents](#best-practices-for-ai-agents)

---

## Overview

### Purpose
PDODb is a **fluent query builder** and **PDO wrapper** that provides:
- **Method chaining** for intuitive query construction
- **Protection against SQL injection** via prepared statements
- **Multi-database support** (MySQL, PostgreSQL, SQLite, SQL Server)
- **Advanced features**: pagination, subqueries, joins, transactions, generators

### Key Characteristics
- **Stateful Query Builder**: Methods modify internal state and return `$this` for chaining
- **Auto-reset**: Internal state automatically clears after query execution
- **Type-safe**: Uses PDO parameter binding for all values
- **Database-agnostic**: Adapts field quoting and syntax based on database type

---

## Quick Start

### Minimal Example
```php
// 1. Create instance
$db = new PDODb('mysql', 'localhost', 'user', 'pass', 'database');

// 2. Connect
$db->connect();

// 3. Query
$users = $db->where('status', 'active')->get('users');

// 4. Insert
$db->insert('users', ['name' => 'John', 'email' => 'john@example.com']);

// 5. Update
$db->where('id', 5)->update('users', ['status' => 'inactive']);

// 6. Delete
$db->where('age', 18, '<')->delete('users');
```

---

## Installation & Connection

### Constructor Signatures

#### Signature 1: Individual Parameters
```php
public function __construct(
    string $type,      // 'mysql', 'pgsql', 'sqlite', 'sqlsrv'
    string $host,      // Database host
    string $username,  // Database username
    string $password,  // Database password
    string $dbname,    // Database name
    int $port = null,  // Optional port (default: 3306 for MySQL)
    string $charset = null  // Optional charset (default: utf8mb4 for MySQL)
)
```

**Example:**
```php
$db = new PDODb('mysql', 'localhost', 'root', 'password', 'myapp');
```

#### Signature 2: Array Configuration
```php
public function __construct(array $config)
```

**Example:**
```php
$db = new PDODb([
    'type' => 'mysql',
    'host' => 'localhost',
    'username' => 'root',
    'password' => 'secret',
    'dbname' => 'myapp',
    'port' => 3306,
    'charset' => 'utf8mb4',
    'prefix' => 'app_'  // Optional table prefix
]);
```

#### Signature 3: Existing PDO Object
```php
public function __construct(PDO $pdo)
```

**Example:**
```php
$pdo = new PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
$db = new PDODb($pdo);
```

### Connection Method

```php
public function connect(): void
```

**Description:** Establishes database connection using stored credentials.

**Supported Database Types:**
- `mysql` - MySQL/MariaDB
- `pgsql` - PostgreSQL
- `sqlite` - SQLite (requires only `dbname` parameter as file path)
- `sqlsrv` - Microsoft SQL Server

**Example:**
```php
$db = new PDODb('mysql', 'localhost', 'user', 'pass', 'database');
$db->connect();
```

**Important:** Connection is lazy - it only happens when `connect()` is called or when the first query executes.

---

## Core Concepts

### 1. Method Chaining Pattern
Most methods return `$this`, enabling fluent chaining:

```php
$result = $db
    ->where('age', 18, '>')
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get('users');
```

### 2. Auto-Reset Mechanism
After each query execution, internal state automatically resets:

```php
$db->where('id', 5)->get('users');  // Executes and resets
$db->get('products');  // Fresh query, no WHERE clause
```

### 3. Parameter Binding
All user values are automatically bound as prepared statement parameters:

```php
// Internally converts to: WHERE name = ? with bound param 'John'
$db->where('name', 'John')->get('users');
```

### 4. Table Prefix Support
Optional prefix automatically prepends to table names:

```php
$db->setPrefix('wp_');
$db->get('users');  // Queries wp_users
```

---

## Query Building Pattern

### Standard Flow

```
┌─────────────────┐
│   Create DB     │
│   Instance      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Add WHERE     │  ← where(), orWhere()
│   Conditions    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Add JOINS     │  ← join(), leftJoin(), etc.
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Add ORDER BY  │  ← orderBy()
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Add GROUP BY  │  ← groupBy()
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Add LIMIT     │  ← limit()
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Execute       │  ← get(), insert(), update(), delete()
│   Query         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Auto Reset    │
└─────────────────┘
```

---

## Complete Method Reference

### SELECT Methods

#### `get(string $tableName, int|array $numRows = null, string $columns = '*')`

**Purpose:** Retrieve multiple rows from a table.

**Parameters:**
- `$tableName` - Table name (prefix auto-applied)
- `$numRows` - Limit (int) or [offset, limit] (array)
- `$columns` - Columns to select (default: '*')

**Returns:** `array` - Array of associative arrays (or Generator if enabled)

**Examples:**
```php
// Get all users
$users = $db->get('users');

// Get 10 users
$users = $db->get('users', 10);

// Get users 20-30 (offset 20, limit 10)
$users = $db->get('users', [20, 10]);

// Get specific columns
$users = $db->get('users', null, 'id, name, email');
```

**With conditions:**
```php
$activeUsers = $db
    ->where('status', 'active')
    ->where('age', 18, '>=')
    ->get('users');
```

---

#### `getOne(string $tableName, string $columns = '*')`

**Purpose:** Retrieve a single row.

**Parameters:**
- `$tableName` - Table name
- `$columns` - Columns to select

**Returns:** `array|false` - Single row as associative array, or `false` if not found

**Examples:**
```php
// Get user by ID
$user = $db->where('id', 5)->getOne('users');

// Get specific columns
$user = $db->where('email', 'john@example.com')->getOne('users', 'id, name');
```

---

#### `getValue(string $tableName, string $column, int|array $numRows = null)`

**Purpose:** Retrieve a single column value or array of values.

**Parameters:**
- `$tableName` - Table name
- `$column` - Column name
- `$numRows` - Limit results (optional)

**Returns:** `mixed|array` - Single value if one row, array of values if multiple rows, `null` if not found

**Examples:**
```php
// Get single email
$email = $db->where('id', 5)->getValue('users', 'email');
// Returns: "john@example.com"

// Get multiple emails
$emails = $db->where('status', 'active')->getValue('users', 'email');
// Returns: ["john@example.com", "jane@example.com", ...]

// Get limited emails
$emails = $db->getValue('users', 'email', 10);
// Returns: Array of 10 emails
```

---

#### `paginate(string $tableName, int $page, array|string $fields = null)`

**Purpose:** Retrieve paginated results with automatic total count.

**Parameters:**
- `$tableName` - Table name
- `$page` - Page number (1-based)
- `$fields` - Columns to select

**Returns:** `array` - Page of results

**Side Effects:**
- Sets `$db->totalPages` - Total number of pages
- Sets `$db->totalCount` - Total number of records

**Examples:**
```php
$db->setPageLimit(20);  // Set rows per page (default: 10)

$page1 = $db->paginate('users', 1);
echo "Total pages: " . $db->totalPages;
echo "Total records: " . $db->totalCount;

// With conditions
$results = $db
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->paginate('users', 1);
```

---

### INSERT Methods

#### `insert(string $tableName, array $insertData)`

**Purpose:** Insert a single row or multiple rows.

**Parameters:**
- `$tableName` - Table name
- `$insertData` - Associative array for single row, or array of arrays for multiple rows

**Returns:** `int|array` - Last insert ID for single insert, array of IDs for batch insert

**Examples:**
```php
// Single insert
$id = $db->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'created_at' => $db->now()
]);
echo "Inserted ID: $id";

// Batch insert
$ids = $db->insert('users', [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com']
]);
// Returns: [15, 16]
```

---

#### `insertMulti(string $tableName, array $multiInsertData, array $dataKeys = null)`

**Purpose:** Optimized batch insert with a single query.

**Parameters:**
- `$tableName` - Table name
- `$multiInsertData` - Array of rows
- `$dataKeys` - Optional column names (auto-detected from first row if null)

**Returns:** `bool` - Success status

**Examples:**
```php
$success = $db->insertMulti('users', [
    ['name' => 'User1', 'email' => 'user1@example.com'],
    ['name' => 'User2', 'email' => 'user2@example.com'],
    ['name' => 'User3', 'email' => 'user3@example.com']
]);

// With explicit keys (useful if first row has fewer columns)
$success = $db->insertMulti('products', $data, ['name', 'price', 'stock']);
```

---

#### `replace(string $tableName, array $insertData)`

**Purpose:** MySQL REPLACE - insert or update if key exists.

**Parameters:** Same as `insert()`

**Returns:** `int` - Last insert ID

**Example:**
```php
// If id=5 exists, update; otherwise insert
$id = $db->replace('users', [
    'id' => 5,
    'name' => 'John Updated',
    'email' => 'john.new@example.com'
]);
```

---

#### `onDuplicate(array $updateColumns, string $lastInsertId = null)`

**Purpose:** Handle ON DUPLICATE KEY UPDATE (MySQL).

**Parameters:**
- `$updateColumns` - Columns to update on duplicate
- `$lastInsertId` - Column name for last insert ID retrieval

**Returns:** `PDODb` - For chaining before `insert()`

**Examples:**
```php
// Update email if username already exists
$id = $db->onDuplicate(['email'])
    ->insert('users', [
        'username' => 'john',
        'email' => 'new@example.com'
    ]);

// Update multiple columns
$id = $db->onDuplicate(['email', 'updated_at'], 'id')
    ->insert('users', $data);
```

---

### UPDATE Methods

#### `update(string $tableName, array $tableData, int $numRows = null)`

**Purpose:** Update existing rows. **ALWAYS use with `where()` to avoid updating all rows.**

**Parameters:**
- `$tableName` - Table name
- `$tableData` - Associative array of column => value
- `$numRows` - Limit number of rows to update

**Returns:** `bool` - Success status

**Side Effects:** Sets `$db->rowCount` with number of affected rows

**Examples:**
```php
// Update single user
$db->where('id', 5)
    ->update('users', ['status' => 'inactive']);
echo "Updated {$db->rowCount} rows";

// Update multiple columns
$db->where('status', 'pending')
    ->where('created_at', '2024-01-01', '<')
    ->update('users', [
        'status' => 'expired',
        'updated_at' => $db->now()
    ]);

// Update with limit
$db->where('status', 'active')
    ->orderBy('last_login', 'ASC')
    ->update('users', ['notification_sent' => 1], 100);
```

---

### DELETE Methods

#### `delete(string $tableName, int|array $numRows = null)`

**Purpose:** Delete rows. **ALWAYS use with `where()` to avoid deleting all rows.**

**Parameters:**
- `$tableName` - Table name
- `$numRows` - Limit (int) or [offset, limit] (array)

**Returns:** `bool` - Success status

**Side Effects:** Sets `$db->rowCount` with number of deleted rows

**Examples:**
```php
// Delete single row
$db->where('id', 5)->delete('users');

// Delete with multiple conditions
$db->where('status', 'inactive')
    ->where('last_login', '2020-01-01', '<')
    ->delete('users');
echo "Deleted {$db->rowCount} users";

// Delete with limit
$db->where('is_spam', 1)
    ->orderBy('created_at', 'ASC')
    ->delete('comments', 1000);
```

---

### WHERE Conditions

#### `where(string $whereProp, mixed $whereValue = 'DBNULL', string $operator = '=', string $cond = 'AND')`

**Purpose:** Add WHERE condition with AND logic.

**Parameters:**
- `$whereProp` - Column name or raw SQL
- `$whereValue` - Value to compare (or 'DBNULL' for special cases)
- `$operator` - Comparison operator
- `$cond` - 'AND' or 'OR' (default: 'AND')

**Supported Operators:**
- `=`, `!=`, `<>`, `>`, `>=`, `<`, `<=`
- `IN`, `NOT IN` (requires array value)
- `BETWEEN`, `NOT BETWEEN` (requires array value [min, max])
- `LIKE`, `NOT LIKE`
- `IS NULL`, `IS NOT NULL` (use 'DBNULL' as value)
- `REGEXP` (MySQL)

**Returns:** `PDODb` - For chaining

**Examples:**
```php
// Basic comparison
$db->where('age', 18, '>=')->get('users');

// Multiple conditions (AND)
$db->where('status', 'active')
    ->where('role', 'admin')
    ->get('users');

// IN operator
$db->where('id', [1, 2, 3, 4, 5], 'IN')->get('users');

// NOT IN
$db->where('status', ['banned', 'deleted'], 'NOT IN')->get('users');

// BETWEEN
$db->where('age', [18, 65], 'BETWEEN')->get('users');

// LIKE
$db->where('email', '%@gmail.com', 'LIKE')->get('users');

// IS NULL
$db->where('deleted_at', 'DBNULL', 'IS NULL')->get('users');

// IS NOT NULL
$db->where('email_verified_at', 'DBNULL', 'IS NOT NULL')->get('users');

// REGEXP (MySQL)
$db->where('phone', '^[0-9]{10}$', 'REGEXP')->get('users');

// Raw SQL (use carefully)
$db->where('DATE(created_at) = CURDATE()')->get('users');
```

---

#### `orWhere(string $whereProp, mixed $whereValue = 'DBNULL', string $operator = '=')`

**Purpose:** Add WHERE condition with OR logic.

**Parameters:** Same as `where()` but always uses OR

**Returns:** `PDODb` - For chaining

**Examples:**
```php
// Get admins OR moderators
$db->where('role', 'admin')
    ->orWhere('role', 'moderator')
    ->get('users');

// Complex OR conditions
$db->where('status', 'active')
    ->where(function($q) {
        $q->where('role', 'admin')
          ->orWhere('role', 'moderator');
    })
    ->get('users');
// SQL: WHERE status = 'active' AND (role = 'admin' OR role = 'moderator')
```

---

#### `having(string $havingProp, mixed $havingValue = 'DBNULL', string $operator = '=', string $cond = 'AND')`

**Purpose:** Add HAVING clause for aggregate filtering.

**Parameters:** Same as `where()`

**Returns:** `PDODb` - For chaining

**Examples:**
```php
// Get categories with more than 10 products
$db->groupBy('category_id')
    ->having('COUNT(*)', 10, '>')
    ->get('products', null, 'category_id, COUNT(*) as product_count');

// Multiple HAVING conditions
$db->select('department, AVG(salary) as avg_salary, COUNT(*) as emp_count')
    ->groupBy('department')
    ->having('AVG(salary)', 50000, '>')
    ->having('COUNT(*)', 5, '>=')
    ->get('employees');
```

---

### JOIN Operations

#### `join(string $joinTable, string $joinCondition, string $joinType = '')`

**Purpose:** Add SQL JOIN to query.

**Parameters:**
- `$joinTable` - Table to join
- `$joinCondition` - ON condition
- `$joinType` - 'LEFT', 'RIGHT', 'INNER', 'OUTER' (default: INNER)

**Returns:** `PDODb` - For chaining

**Examples:**
```php
// INNER JOIN
$results = $db
    ->join('posts p', 'u.id = p.user_id')
    ->where('u.status', 'active')
    ->get('users u', null, 'u.*, p.title');

// LEFT JOIN
$results = $db
    ->join('posts', 'users.id = posts.user_id', 'LEFT')
    ->get('users', null, 'users.*, COUNT(posts.id) as post_count');

// Multiple JOINs
$results = $db
    ->join('profiles', 'users.id = profiles.user_id', 'LEFT')
    ->join('roles', 'users.role_id = roles.id', 'INNER')
    ->where('users.status', 'active')
    ->get('users', null, 'users.*, profiles.bio, roles.name as role_name');
```

---

#### `leftJoin(string $joinTable, string $joinCondition)`

**Purpose:** Shortcut for LEFT JOIN.

**Example:**
```php
$results = $db
    ->leftJoin('posts', 'users.id = posts.user_id')
    ->get('users');
```

---

#### `rightJoin(string $joinTable, string $joinCondition)`

**Purpose:** Shortcut for RIGHT JOIN.

---

#### `innerJoin(string $joinTable, string $joinCondition)`

**Purpose:** Shortcut for INNER JOIN.

---

#### `naturalJoin(string $joinTable, string $joinType = '')`

**Purpose:** NATURAL JOIN (joins on matching column names).

**Example:**
```php
$results = $db->naturalJoin('profiles', 'LEFT')->get('users');
```

---

### Ordering & Grouping

#### `orderBy(string $orderByField, string $orderByDirection = 'DESC', array $customFields = null)`

**Purpose:** Add ORDER BY clause.

**Parameters:**
- `$orderByField` - Column name or comma-separated list
- `$orderByDirection` - 'ASC' or 'DESC'
- `$customFields` - Custom ordering values (for FIELD() in MySQL)

**Returns:** `PDODb` - For chaining

**Examples:**
```php
// Single column
$db->orderBy('created_at', 'DESC')->get('posts');

// Multiple columns
$db->orderBy('status, created_at', 'ASC')->get('users');

// Multiple orderBy calls
$db->orderBy('status', 'ASC')
    ->orderBy('created_at', 'DESC')
    ->get('users');

// Custom field order (MySQL)
$db->orderBy('id', 'ASC', [5, 3, 1, 2, 4])
    ->get('items');
// SQL: ORDER BY FIELD(id, 5, 3, 1, 2, 4)

// Raw SQL ordering
$db->orderBy('RAND()')->get('users', 10);
```

---

#### `groupBy(string $groupByField)`

**Purpose:** Add GROUP BY clause.

**Parameters:**
- `$groupByField` - Column name or comma-separated list

**Returns:** `PDODb` - For chaining

**Examples:**
```php
// Single column
$db->groupBy('category_id')
    ->get('products', null, 'category_id, COUNT(*) as count');

// Multiple columns
$db->groupBy('year, month')
    ->get('sales', null, 'year, month, SUM(amount) as total');

// With HAVING
$db->groupBy('user_id')
    ->having('COUNT(*)', 5, '>')
    ->get('orders', null, 'user_id, COUNT(*) as order_count');
```

---

### Limiting Results

#### `limit(int $limit, int $offset = null)`

**Purpose:** Add LIMIT clause.

**Parameters:**
- `$limit` - Maximum rows to return
- `$offset` - Optional starting position (default: 0)

**Returns:** `PDODb` - For chaining

**Examples:**
```php
// First 10 rows
$db->limit(10)->get('users');

// Rows 20-30 (offset 20, limit 10)
$db->limit(10, 20)->get('users');

// With ordering
$db->orderBy('created_at', 'DESC')
    ->limit(5)
    ->get('posts');
```

**Note:** Can also use array notation in `get()`:
```php
$db->get('users', [20, 10]);  // Same as limit(10, 20)
```

---

### Raw SQL Queries

#### `rawQuery(string $query, array $params = null)`

**Purpose:** Execute raw SQL query with parameter binding.

**Parameters:**
- `$query` - SQL query with `?` placeholders
- `$params` - Array of values to bind

**Returns:** `array` - Query results (or Generator if enabled)

**Examples:**
```php
// Simple query
$users = $db->rawQuery("SELECT * FROM users WHERE status = ?", ['active']);

// Multiple parameters
$results = $db->rawQuery(
    "SELECT * FROM orders WHERE user_id = ? AND amount > ? AND created_at > ?",
    [5, 100, '2024-01-01']
);

// Complex query
$stats = $db->rawQuery("
    SELECT 
        u.name,
        COUNT(o.id) as order_count,
        SUM(o.amount) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.created_at > ?
    GROUP BY u.id
    HAVING order_count > ?
", ['2024-01-01', 5]);
```

---

#### `rawQueryOne(string $query, array $params = null)`

**Purpose:** Execute raw query and return only first row.

**Returns:** `array|false` - Single row or false

**Example:**
```php
$user = $db->rawQueryOne("SELECT * FROM users WHERE email = ?", ['john@example.com']);
```

---

#### `rawQueryValue(string $query, array $params = null)`

**Purpose:** Execute raw query and return single column value(s).

**Returns:** `mixed|array` - Single value if LIMIT 1, array of values otherwise

**Examples:**
```php
// Single value
$count = $db->rawQueryValue("SELECT COUNT(*) FROM users LIMIT 1");
// Returns: 150

// Multiple values
$emails = $db->rawQueryValue("SELECT email FROM users WHERE status = ?", ['active']);
// Returns: ["john@example.com", "jane@example.com", ...]
```

---

### Transaction Methods

#### `startTransaction()`

**Purpose:** Begin database transaction.

**Example:**
```php
$db->startTransaction();
```

---

#### `commit()`

**Purpose:** Commit transaction.

**Returns:** `bool` - Success status

**Example:**
```php
$db->commit();
```

---

#### `rollback()`

**Purpose:** Rollback transaction.

**Returns:** `bool` - Success status

**Example:**
```php
$db->rollback();
```

See [Transaction Management](#transaction-management) section for complete examples.

---

### Utility Methods

#### `getLastQuery()`

**Purpose:** Get the last executed SQL query.

**Returns:** `string` - Last SQL query

**Example:**
```php
$users = $db->where('status', 'active')->get('users');
echo $db->getLastQuery();
// Output: SELECT * FROM users WHERE status = ?
```

---

#### `getLastError()`

**Purpose:** Get last error information.

**Returns:** `array` - PDO error info [SQLSTATE, driver code, message]

**Example:**
```php
$result = $db->insert('users', $data);
if (!$result) {
    $error = $db->getLastError();
    echo "Error: " . $error[2];  // Error message
}
```

---

#### `getLastErrorCode()`

**Purpose:** Get last error code.

**Returns:** `string` - PDO error code

---

#### `getInsertId()`

**Purpose:** Get last insert ID.

**Returns:** `int` - Last auto-increment ID

**Example:**
```php
$id = $db->insert('users', ['name' => 'John']);
echo "Inserted user with ID: " . $db->getInsertId();
```

---

#### `count`

**Purpose:** Get number of rows in last result.

**Returns:** `int` - Row count

**Example:**
```php
$db->where('status', 'active')->get('users');
echo "Found {$db->count} active users";
```

---

#### `now(string $diff = null, string $func = 'NOW()')`

**Purpose:** Generate current timestamp expression.

**Parameters:**
- `$diff` - Time difference (e.g., '+1 DAY', '-2 HOURS')
- `$func` - SQL function (default: 'NOW()')

**Returns:** `array` - Special format for SQL function

**Examples:**
```php
// Current timestamp
$db->insert('posts', [
    'title' => 'Hello',
    'created_at' => $db->now()
]);

// Future date
$db->insert('subscriptions', [
    'expires_at' => $db->now('+1 YEAR')
]);

// Past date
$db->where('created_at', $db->now('-30 DAYS'), '>')
    ->get('posts');
```

---

#### `inc(int $num = 1)`

**Purpose:** Increment column value.

**Returns:** `array` - Special format for increment

**Example:**
```php
$db->where('id', 5)
    ->update('posts', ['view_count' => $db->inc()]);
// SQL: UPDATE posts SET view_count = view_count + 1

$db->where('id', 10)
    ->update('users', ['credits' => $db->inc(50)]);
// SQL: UPDATE users SET credits = credits + 50
```

---

#### `dec(int $num = 1)`

**Purpose:** Decrement column value.

**Example:**
```php
$db->where('id', 5)
    ->update('products', ['stock' => $db->dec(1)]);
// SQL: UPDATE products SET stock = stock - 1
```

---

#### `func(string $expr, array $bindParams = null)`

**Purpose:** Use SQL functions in queries.

**Parameters:**
- `$expr` - SQL expression with ? placeholders
- `$bindParams` - Parameters to bind

**Returns:** `array` - Special format for SQL function

**Examples:**
```php
// Use function in WHERE
$db->where('created_at', $db->func('DATE(?)'), [$someDate])
    ->get('posts');

// Use in INSERT
$db->insert('logs', [
    'hash' => $db->func('SHA1(?)', ['secret_value']),
    'upper_name' => $db->func('UPPER(?)', ['john'])
]);

// Use in SELECT
$results = $db->get('users', null, 
    'id, name, ' . $db->func('CONCAT(?, email)', ['prefix_'])
);
```

---

#### `setPrefix(string $prefix)`

**Purpose:** Set table name prefix.

**Parameters:**
- `$prefix` - Prefix to add to all table names

**Returns:** `PDODb` - For chaining

**Example:**
```php
$db->setPrefix('wp_');
$db->get('users');  // Queries wp_users
$db->get('posts');  // Queries wp_posts
```

---

#### `setPageLimit(int $limit)`

**Purpose:** Set rows per page for pagination.

**Parameters:**
- `$limit` - Rows per page (default: 10)

**Returns:** `PDODb` - For chaining

**Example:**
```php
$db->setPageLimit(25);
$results = $db->paginate('products', 1);
```

---

#### `setQueryOption(string|array $options)`

**Purpose:** Set SQL query options.

**Allowed Options:**
- `ALL`, `DISTINCT`, `DISTINCTROW`
- `HIGH_PRIORITY`, `LOW_PRIORITY`
- `SQL_CACHE`, `SQL_NO_CACHE`, `SQL_CALC_FOUND_ROWS`
- `FOR UPDATE`, `LOCK IN SHARE MODE`
- `MYSQLI_NESTJOIN` (enables nested join results)

**Returns:** `PDODb` - For chaining

**Examples:**
```php
// DISTINCT results
$db->setQueryOption('DISTINCT')
    ->get('users', null, 'country');

// Multiple options
$db->setQueryOption(['DISTINCT', 'SQL_CACHE'])
    ->get('products');

// For locking
$db->setQueryOption('FOR UPDATE')
    ->where('id', 5)
    ->getOne('inventory');
```

---

#### `setReturnType(int $returnType)`

**Purpose:** Set PDO fetch mode.

**Parameters:**
- `$returnType` - PDO::FETCH_* constant

**Common Types:**
- `PDO::FETCH_ASSOC` - Associative array (default)
- `PDO::FETCH_OBJ` - Object
- `PDO::FETCH_NUM` - Numeric array
- `PDO::FETCH_BOTH` - Both associative and numeric

**Returns:** `PDODb` - For chaining

**Example:**
```php
$db->setReturnType(PDO::FETCH_OBJ)
    ->get('users');
// Returns: [stdClass{id: 1, name: 'John'}, ...]
```

---

#### `withTotalCount()`

**Purpose:** Enable SQL_CALC_FOUND_ROWS for total count retrieval.

**Returns:** `PDODb` - For chaining

**Side Effects:** Sets `$db->totalCount` with total rows (ignoring LIMIT)

**Example:**
```php
$results = $db->withTotalCount()
    ->limit(10)
    ->get('users');
    
echo "Showing 10 of {$db->totalCount} total users";
```

---

#### `useGenerator(bool $option)`

**Purpose:** Enable generator mode (yield) for memory efficiency with large result sets.

**Parameters:**
- `$option` - true to enable, false to disable

**Example:**
```php
$db->useGenerator(true);
$users = $db->get('users');  // Returns Generator

foreach ($users as $user) {
    // Process one row at a time, memory efficient
    echo $user['name'];
}
```

---

#### `tableExists(string|array $tables)`

**Purpose:** Check if table(s) exist.

**Parameters:**
- `$tables` - Single table name or array of names

**Returns:** `bool` - True if all tables exist

**Examples:**
```php
if ($db->tableExists('users')) {
    echo "Users table exists";
}

if ($db->tableExists(['users', 'posts', 'comments'])) {
    echo "All tables exist";
}
```

---

#### `subQuery(string $subQueryAlias = '')`

**Purpose:** Create subquery builder instance.

**Parameters:**
- `$subQueryAlias` - Alias for subquery

**Returns:** `PDODb` - New instance for subquery

**Example:**
```php
$subQuery = $db->subQuery('sq');
$subQuery->where('status', 'active')->get('users', null, 'id');

$results = $db->where('user_id', $subQuery, 'IN')
    ->get('orders');
// SQL: SELECT * FROM orders WHERE user_id IN (SELECT id FROM users WHERE status = 'active')
```

---

## Usage Examples by Category

### Basic CRUD Operations

#### Create (Insert)
```php
// Single record
$userId = $db->insert('users', [
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT),
    'created_at' => $db->now()
]);

// Multiple records
$db->insertMulti('tags', [
    ['name' => 'PHP'],
    ['name' => 'MySQL'],
    ['name' => 'JavaScript']
]);

// Insert or update
$db->onDuplicate(['email', 'updated_at'])
    ->insert('users', [
        'username' => 'john_doe',
        'email' => 'newemail@example.com'
    ]);
```

#### Read (Select)
```php
// All records
$users = $db->get('users');

// Single record
$user = $db->where('id', 5)->getOne('users');

// Specific columns
$emails = $db->get('users', null, 'id, email');

// Single value
$username = $db->where('id', 5)->getValue('users', 'username');

// Count
$db->withTotalCount()->get('users', 1);
$total = $db->totalCount;
```

#### Update
```php
// Update single record
$db->where('id', 5)
    ->update('users', [
        'email' => 'newemail@example.com',
        'updated_at' => $db->now()
    ]);

// Update multiple records
$db->where('status', 'pending')
    ->where('created_at', '2024-01-01', '<')
    ->update('orders', ['status' => 'cancelled']);

// Increment/decrement
$db->where('id', 10)
    ->update('posts', [
        'view_count' => $db->inc(),
        'likes' => $db->inc(5)
    ]);
```

#### Delete
```php
// Delete specific record
$db->where('id', 5)->delete('users');

// Delete multiple records
$db->where('status', 'spam')
    ->where('created_at', '2023-01-01', '<')
    ->delete('comments');

// Delete with limit
$db->where('processed', 1)
    ->orderBy('created_at', 'ASC')
    ->delete('logs', 1000);
```

---

### Complex Queries

#### Joins
```php
// Get posts with author information
$posts = $db
    ->join('users', 'posts.user_id = users.id', 'LEFT')
    ->where('posts.status', 'published')
    ->orderBy('posts.created_at', 'DESC')
    ->get('posts', null, 'posts.*, users.name as author_name');

// Multiple joins
$orders = $db
    ->join('users', 'orders.user_id = users.id')
    ->join('products', 'orders.product_id = products.id')
    ->join('addresses', 'orders.shipping_address_id = addresses.id', 'LEFT')
    ->where('orders.status', 'completed')
    ->get('orders', null, 'orders.*, users.name, products.title, addresses.city');
```

#### Aggregation
```php
// Group by with aggregates
$stats = $db
    ->groupBy('category_id')
    ->get('products', null, 'category_id, COUNT(*) as count, AVG(price) as avg_price');

// With HAVING clause
$activeCategories = $db
    ->groupBy('category_id')
    ->having('COUNT(*)', 10, '>')
    ->get('products', null, 'category_id, COUNT(*) as product_count');

// Complex aggregation
$monthlySales = $db
    ->groupBy('YEAR(created_at), MONTH(created_at)')
    ->orderBy('created_at', 'DESC')
    ->get('orders', null, '
        YEAR(created_at) as year,
        MONTH(created_at) as month,
        COUNT(*) as order_count,
        SUM(total) as revenue
    ');
```

#### Subqueries
```php
// Subquery in WHERE IN
$activeUserIds = $db->subQuery();
$activeUserIds->where('status', 'active')->get('users', null, 'id');

$orders = $db->where('user_id', $activeUserIds, 'IN')->get('orders');

// Subquery in SELECT
$subQuery = $db->subQuery('sq');
$subQuery->where('posts.user_id = users.id')->get('posts', null, 'COUNT(*)');

$users = $db->get('users', null, "users.*, ({$subQuery}) as post_count");
```

#### Pagination
```php
$db->setPageLimit(20);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$products = $db
    ->where('status', 'active')
    ->orderBy('created_at', 'DESC')
    ->paginate('products', $page);

echo "Page $page of {$db->totalPages}";
echo "Total products: {$db->totalCount}";

// Generate pagination links
for ($i = 1; $i <= $db->totalPages; $i++) {
    echo "<a href='?page=$i'>$i</a> ";
}
```

---

### Advanced WHERE Conditions

```php
// IN operator
$db->where('id', [1, 2, 3, 4, 5], 'IN')->get('users');

// NOT IN
$db->where('status', ['banned', 'deleted'], 'NOT IN')->get('users');

// BETWEEN
$db->where('age', [18, 65], 'BETWEEN')->get('users');
$db->where('created_at', ['2024-01-01', '2024-12-31'], 'BETWEEN')->get('orders');

// LIKE patterns
$db->where('email', '%@gmail.com', 'LIKE')->get('users');
$db->where('name', 'John%', 'LIKE')->get('users');

// NULL checks
$db->where('deleted_at', 'DBNULL', 'IS NULL')->get('users');
$db->where('email_verified_at', 'DBNULL', 'IS NOT NULL')->get('users');

// Complex OR conditions
$db->where('status', 'active')
    ->orWhere('status', 'pending')
    ->orWhere('status', 'processing')
    ->get('orders');

// Nested conditions
$db->where('created_at', '2024-01-01', '>')
    ->where(function($q) {
        $q->where('status', 'active')
          ->orWhere('priority', 'high');
    })
    ->get('tasks');
```

---

## Transaction Management

### Basic Transaction Pattern

```php
try {
    $db->startTransaction();
    
    // Multiple operations
    $userId = $db->insert('users', ['name' => 'John', 'email' => 'john@example.com']);
    $db->insert('profiles', ['user_id' => $userId, 'bio' => 'Hello']);
    $db->where('id', 10)->update('accounts', ['balance' => $db->dec(100)]);
    
    $db->commit();
    echo "Transaction successful";
} catch (Exception $e) {
    $db->rollback();
    echo "Transaction failed: " . $e->getMessage();
}
```

### Complex Transaction Example

```php
function transferMoney($fromUserId, $toUserId, $amount, $db) {
    try {
        $db->startTransaction();
        
        // Check sender balance
        $sender = $db->where('id', $fromUserId)->getOne('accounts', 'balance');
        if (!$sender || $sender['balance'] < $amount) {
            throw new Exception("Insufficient funds");
        }
        
        // Deduct from sender
        $db->where('id', $fromUserId)
            ->update('accounts', ['balance' => $db->dec($amount)]);
        
        // Add to receiver
        $db->where('id', $toUserId)
            ->update('accounts', ['balance' => $db->inc($amount)]);
        
        // Log transaction
        $db->insert('transactions', [
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'amount' => $amount,
            'created_at' => $db->now()
        ]);
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        error_log("Transfer failed: " . $e->getMessage());
        return false;
    }
}
```

---

## Error Handling

### Checking for Errors

```php
$result = $db->where('id', 999)->update('users', ['status' => 'active']);

if (!$result) {
    $error = $db->getLastError();
    $errorCode = $db->getLastErrorCode();
    
    echo "Error Code: $errorCode\n";
    echo "Error Message: {$error[2]}\n";
    echo "Query: " . $db->getLastQuery() . "\n";
}
```

### Comprehensive Error Handling

```php
function safeInsert($table, $data, $db) {
    try {
        $id = $db->insert($table, $data);
        
        if ($id === false) {
            $error = $db->getLastError();
            throw new Exception("Insert failed: " . $error[2]);
        }
        
        return $id;
    } catch (PDOException $e) {
        // Log error
        error_log("Database error: " . $e->getMessage());
        error_log("Query: " . $db->getLastQuery());
        
        // Handle specific errors
        if ($e->getCode() == 23000) {
            throw new Exception("Duplicate entry detected");
        }
        
        throw $e;
    }
}
```

---

## Advanced Features

### Generator Mode (Memory Efficient)

```php
// Enable generator for large result sets
$db->useGenerator(true);

$users = $db->get('users');  // Returns Generator, not array

foreach ($users as $user) {
    // Process one row at a time
    // Memory efficient for millions of rows
    processUser($user);
}
```

### Nested Join Results

```php
$db->setQueryOption('MYSQLI_NESTJOIN');

$users = $db
    ->join('posts', 'users.id = posts.user_id', 'LEFT')
    ->get('users');

// Results are nested by table:
// [
//   'users' => ['id' => 1, 'name' => 'John'],
//   'posts' => ['id' => 5, 'title' => 'Hello']
// ]
```

### Row Locking

```php
// Lock rows for update
$db->setQueryOption('FOR UPDATE');
$product = $db->where('id', 10)->getOne('products');

// Make changes
$db->where('id', 10)->update('products', ['stock' => $product['stock'] - 1]);

// Shared lock (read lock)
$db->setQueryOption('LOCK IN SHARE MODE');
$balance = $db->where('id', 5)->getValue('accounts', 'balance');
```

---

## Design Patterns

### 1. Fluent Interface
Methods return `$this` for chaining:
```php
$result = $db->where('x', 1)->where('y', 2)->orderBy('z')->get('table');
```

### 2. Builder Pattern
Query is built incrementally before execution:
```php
$query = $db->where('status', 'active');

if ($minAge) {
    $query->where('age', $minAge, '>=');
}

if ($searchTerm) {
    $query->where('name', "%$searchTerm%", 'LIKE');
}

$results = $query->get('users');
```

### 3. Singleton Pattern
Static `$instance` property stores single instance:
```php
$db = PDODb::getInstance();  // If implemented in your version
```

### 4. Prepared Statements (Security Pattern)
All user values are automatically bound:
```php
// Internally uses prepared statements
$db->where('email', $_POST['email'])->getOne('users');
// Safe from SQL injection
```

---

## Best Practices for AI Agents

### 1. Always Chain WHERE Before Execution
```php
// ✅ CORRECT
$db->where('id', 5)->delete('users');

// ❌ DANGEROUS (deletes ALL rows)
$db->delete('users');
```

### 2. Use Transactions for Multi-Step Operations
```php
// ✅ CORRECT
$db->startTransaction();
try {
    $db->insert('table1', $data1);
    $db->insert('table2', $data2);
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
}
```

### 3. Check Return Values
```php
// ✅ CORRECT
$result = $db->update('users', $data);
if (!$result) {
    handleError($db->getLastError());
}

// ❌ INCORRECT
$db->update('users', $data);  // Ignoring result
```

### 4. Use Parameter Binding
```php
// ✅ CORRECT (automatic binding)
$db->where('email', $userInput)->get('users');

// ✅ CORRECT (manual binding with rawQuery)
$db->rawQuery("SELECT * FROM users WHERE email = ?", [$userInput]);

// ❌ DANGEROUS (SQL injection risk)
$db->rawQuery("SELECT * FROM users WHERE email = '$userInput'");
```

### 5. Leverage Helper Methods
```php
// ✅ CORRECT
$db->insert('posts', [
    'created_at' => $db->now(),
    'view_count' => 0
]);

$db->where('id', 5)->update('posts', [
    'view_count' => $db->inc()
]);

// ❌ VERBOSE
$db->insert('posts', [
    'created_at' => date('Y-m-d H:i:s')
]);
```

### 6. Handle Pagination Properly
```php
// ✅ CORRECT
$db->setPageLimit(20);
$page = max(1, (int)($_GET['page'] ?? 1));
$results = $db->paginate('products', $page);

// Access pagination info
echo "Page $page of {$db->totalPages}";
```

### 7. Use Subqueries for Complex Conditions
```php
// ✅ CORRECT (subquery)
$activeUserIds = $db->subQuery();
$activeUserIds->where('status', 'active')->get('users', null, 'id');
$db->where('user_id', $activeUserIds, 'IN')->get('orders');

// ❌ INEFFICIENT (two queries + manual filtering)
$users = $db->where('status', 'active')->get('users');
$userIds = array_column($users, 'id');
$db->where('user_id', $userIds, 'IN')->get('orders');
```

### 8. Specify Columns to Select
```php
// ✅ CORRECT (only needed columns)
$db->get('users', null, 'id, name, email');

// ❌ WASTEFUL (all columns when not needed)
$db->get('users');
```

### 9. Use Appropriate Fetch Methods
```php
// Single row
$user = $db->where('id', 5)->getOne('users');

// Single value
$email = $db->where('id', 5)->getValue('users', 'email');

// Multiple rows
$users = $db->get('users');
```

### 10. Clean Up Resources
```php
// Auto-reset happens after each query
$db->where('status', 'active')->get('users');  // Executes and resets

// For generators, consume or close
$db->useGenerator(true);
$users = $db->get('users');
// Use in foreach or call $users->next() until complete
```

---

## Common Patterns for AI Code Generation

### Pattern 1: Search with Filters
```php
function searchUsers($filters) {
    global $db;
    
    $query = $db;
    
    if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }
    
    if (!empty($filters['min_age'])) {
        $query->where('age', $filters['min_age'], '>=');
    }
    
    if (!empty($filters['search'])) {
        $query->where('name', "%{$filters['search']}%", 'LIKE');
    }
    
    return $query->orderBy('created_at', 'DESC')
                 ->get('users');
}
```

### Pattern 2: Paginated List
```php
function getPaginatedProducts($page = 1, $perPage = 20, $categoryId = null) {
    global $db;
    
    $db->setPageLimit($perPage);
    
    $query = $db->where('status', 'active');
    
    if ($categoryId) {
        $query->where('category_id', $categoryId);
    }
    
    $products = $query->orderBy('created_at', 'DESC')
                      ->paginate('products', $page);
    
    return [
        'products' => $products,
        'total' => $db->totalCount,
        'pages' => $db->totalPages,
        'current_page' => $page
    ];
}
```

### Pattern 3: Related Data Fetch
```php
function getUserWithPosts($userId) {
    global $db;
    
    // Get user
    $user = $db->where('id', $userId)->getOne('users');
    
    if (!$user) {
        return null;
    }
    
    // Get user's posts
    $user['posts'] = $db->where('user_id', $userId)
                        ->orderBy('created_at', 'DESC')
                        ->get('posts');
    
    return $user;
}
```

### Pattern 4: Batch Operations
```php
function bulkUpdateStatus($userIds, $newStatus) {
    global $db;
    
    return $db->where('id', $userIds, 'IN')
              ->update('users', [
                  'status' => $newStatus,
                  'updated_at' => $db->now()
              ]);
}
```

### Pattern 5: Statistics Query
```php
function getDashboardStats() {
    global $db;
    
    $stats = [];
    
    // Total users
    $db->withTotalCount()->get('users', 1);
    $stats['total_users'] = $db->totalCount;
    
    // Active users
    $db->where('status', 'active')->withTotalCount()->get('users', 1);
    $stats['active_users'] = $db->totalCount;
    
    // Orders by status
    $stats['orders'] = $db->groupBy('status')
                          ->get('orders', null, 'status, COUNT(*) as count');
    
    return $stats;
}
```

---

## AI Agent Decision Tree

```
Start Query
    │
    ├─ Need single row? → Use getOne()
    │
    ├─ Need single value? → Use getValue()
    │
    ├─ Need all rows?
    │   ├─ Small dataset? → Use get()
    │   ├─ Large dataset? → Use get() with useGenerator(true)
    │   └─ Need pagination? → Use paginate()
    │
    ├─ Insert data?
    │   ├─ Single row? → Use insert()
    │   ├─ Multiple rows? → Use insertMulti()
    │   └─ Upsert? → Use onDuplicate()->insert()
    │
    ├─ Update data?
    │   ├─ Add WHERE condition! → Use where()->update()
    │   └─ Increment/decrement? → Use inc()/dec()
    │
    ├─ Delete data?
    │   └─ Add WHERE condition! → Use where()->delete()
    │
    ├─ Complex query?
    │   ├─ Joins needed? → Use join()/leftJoin()
    │   ├─ Aggregation? → Use groupBy() + having()
    │   ├─ Subquery? → Use subQuery()
    │   └─ Raw SQL? → Use rawQuery() with params
    │
    └─ Multiple operations?
        └─ Use startTransaction() + commit()/rollback()
```

---

## Quick Reference Table

| Task | Method | Example |
|------|--------|---------|
| **SELECT** | `get()` | `$db->get('users')` |
| **SELECT ONE** | `getOne()` | `$db->where('id', 5)->getOne('users')` |
| **SELECT VALUE** | `getValue()` | `$db->getValue('users', 'email')` |
| **INSERT** | `insert()` | `$db->insert('users', $data)` |
| **BATCH INSERT** | `insertMulti()` | `$db->insertMulti('users', $dataArray)` |
| **UPDATE** | `update()` | `$db->where('id', 5)->update('users', $data)` |
| **DELETE** | `delete()` | `$db->where('id', 5)->delete('users')` |
| **WHERE** | `where()` | `$db->where('status', 'active')` |
| **OR WHERE** | `orWhere()` | `$db->orWhere('role', 'admin')` |
| **JOIN** | `join()` | `$db->join('posts', 'users.id = posts.user_id')` |
| **ORDER** | `orderBy()` | `$db->orderBy('created_at', 'DESC')` |
| **GROUP** | `groupBy()` | `$db->groupBy('category_id')` |
| **LIMIT** | `limit()` | `$db->limit(10)` |
| **PAGINATE** | `paginate()` | `$db->paginate('users', 1)` |
| **RAW SQL** | `rawQuery()` | `$db->rawQuery($sql, $params)` |
| **TRANSACTION START** | `startTransaction()` | `$db->startTransaction()` |
| **TRANSACTION COMMIT** | `commit()` | `$db->commit()` |
| **TRANSACTION ROLLBACK** | `rollback()` | `$db->rollback()` |
| **LAST INSERT ID** | `getInsertId()` | `$db->getInsertId()` |
| **LAST QUERY** | `getLastQuery()` | `$db->getLastQuery()` |
| **LAST ERROR** | `getLastError()` | `$db->getLastError()` |

---

## Database Type Differences

| Feature | MySQL | PostgreSQL | SQLite | SQL Server |
|---------|-------|------------|--------|------------|
| **Field Quote** | `` ` `` | `"` | `"` | `"` |
| **Auto Increment** | AUTO_INCREMENT | SERIAL | AUTOINCREMENT | IDENTITY |
| **NOW()** | NOW() | NOW() | datetime('now') | GETDATE() |
| **LIMIT** | LIMIT n | LIMIT n | LIMIT n | TOP n |
| **ON DUPLICATE** | ✅ | ❌ Use UPSERT | ❌ Use REPLACE | ❌ Use MERGE |
| **REGEXP** | ✅ | ✅ (~ operator) | ❌ | ❌ |

---

## Troubleshooting Guide

### Problem: Query not returning expected results
```php
// Solution: Check the actual SQL
$db->where('status', 'active')->get('users');
echo $db->getLastQuery();  // See what was executed
```

### Problem: No rows affected by UPDATE
```php
// Check row count
$db->where('id', 5)->update('users', $data);
echo "Updated {$db->rowCount} rows";

// If 0, verify:
// 1. WHERE condition is correct
// 2. Row with that ID exists
// 3. Data actually changed (PDO returns 0 if values unchanged)
```

### Problem: Transaction not working
```php
// Ensure you're checking transaction status
if (!$db->transaction) {
    echo "No active transaction";
}

// Always wrap in try-catch
try {
    $db->startTransaction();
    // operations
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

### Problem: Memory issues with large datasets
```php
// Solution: Use generator mode
$db->useGenerator(true);
$users = $db->get('large_table');

foreach ($users as $user) {
    // Process one at a time
}
```

---

## Security Checklist for AI Agents

- ✅ Always use parameter binding (automatic with PDODb)
- ✅ Never concatenate user input into SQL strings
- ✅ Use `where()` instead of raw SQL when possible
- ✅ When using `rawQuery()`, always pass params array
- ✅ Validate and sanitize user input before querying
- ✅ Use transactions for critical operations
- ✅ Check return values and handle errors
- ✅ Set appropriate table prefixes
- ✅ Use prepared statements (built-in)
- ✅ Never expose raw error messages to users

---

## Version History

**Version 1.1.0** (Current)
- PDO-based implementation
- Multi-database support (MySQL, PostgreSQL, SQLite, SQL Server)
- Fluent query builder
- Generator support
- Subquery support
- Transaction management
- Pagination
- Row locking options

---

## License

LGPL 3.0 - http://opensource.org/licenses/lgpl-3.0.html

---

## Credits

- **Jeffery Way** - Original concept
- **Josh Campbell** - Development
- **Alexander V. Butenko** - Contributions
- **Vasiliy A. Ulin** - Contributions

---

## End of Documentation

This documentation is optimized for AI agent comprehension with:
- ✅ Complete method signatures
- ✅ Parameter descriptions
- ✅ Return type specifications
- ✅ Practical examples for every method
- ✅ Common patterns and use cases
- ✅ Error handling guidance
- ✅ Security best practices
- ✅ Decision trees and quick references

**For AI Agents:** This class provides a secure, fluent interface for database operations. Always use parameter binding, check return values, and use transactions for critical operations.
