# Comprehensive Codebase Audit Report
## SoftSam Certificate Portal - MySQL to Google Sheets Migration

**Date**: 2026-07-27
**Status**: DATA MIGRATION IN PROGRESS (MySQL → Google Sheets)

---

## CRITICAL: MySQL-to-Google Sheets Migration Status

The application has been partially migrated:
- **Certificate data**: Read from Google Sheets (in `CertificateController::index()`)
- **Learner data**: Read from Google Sheets (in `LearnerController::index()`)
- **ITGK master data**: Read from Google Sheets (in `CertificateController::index()`)
- **BUT**: Models (`Certificate.php`, `LearnerResult.php`) still use MySQL/PDO for all WRITE operations
- **BUT**: `Database.php` is entirely MySQL-dependent (hardcoded `mysql:host=...` DSN)
- **BUT**: `AuthService::attempt()` uses MySQL for login/authentication
- **BUT**: `config/connection.php` creates a MySQL PDO connection
- **BUT**: `api/login.php` uses MySQL for authentication
- **BUT**: `UploadController` uses `Database::getInstance()` for table schema introspection

---

## ISSUE CATEGORIES

### 🔴 CRITICAL ISSUES (Blockers)

#### 1. Certificate Model - MySQL Dependency
**File**: `app/Models/Certificate.php`
**Lines**: 21-33, 49-55, 63-116, 136-146, 156-200, 208-211, 221-234, 242-253, 260-271, 279-293, 300-461, 469-482, 492-538, 547-561
**Problem**: The entire Certificate model uses `Database::getInstance()` (MySQL PDO) for:
- `find()` - MySQL SELECT
- `createWithLearners()` - MySQL INSERT + UPDATE
- `create()` - calls `createWithLearners()`
- `update()` - MySQL UPDATE
- `updateTracking()` - MySQL SELECT + INSERT/UPDATE with `ON DUPLICATE KEY UPDATE`
- `delete()` - MySQL DELETE
- `getAll()` - MySQL SELECT with LIMIT/OFFSET
- `count()` - MySQL SELECT COUNT(*)
- `getAnalytics()` - MySQL SELECT with SUM/CASE
- `getMonthlyStats()` - MySQL DATE_FORMAT + INTERVAL
- `consolidateFromLearners()` - MySQL SELECT + INSERT/UPDATE with transactions
- `deleteMany()` - MySQL DELETE with IN clause
- `issueBatch()` - MySQL UPDATE
- `search()` - MySQL SELECT with LIKE

**Impact**: All certificate CRUD operations will fail or corrupt data since the data source is now Google Sheets.

#### 2. LearnerResult Model - MySQL Dependency
**File**: `app/Models/LearnerResult.php`
**Lines**: 21-33, 49-55, 63-84, 93-103, 111-114, 124-137, 145-156, 164-170, 177-190, 198-212, 221-245, 253-266, 275-288, 297-303, 312-325, 332-337, 344-349
**Problem**: Identical to Certificate model - all methods use MySQL PDO:
- `find()`, `create()`, `update()`, `delete()`, `getAll()`, `count()`, `countByResult()`, `getAnalytics()`, `getMonthlyStats()`, `issueIndividual()`, `deleteMany()`, `search()`, `getByItgkCode()`, `getByExam()`, `getDistinctItgkCodes()`, `getDistinctExams()`

**Impact**: All learner CRUD operations will fail or corrupt data.

#### 3. Database Class - Hardcoded MySQL DSN
**File**: `app/Core/Database.php`
**Lines**: 95-141
**Problem**: 
- Line 105: DSN hardcoded as `"mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}"`
- Line 116: `PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"` - MySQL specific
- Line 137: `['error' => getenv('APP_DEBUG') === 'true' ? $e->getMessage() : 'Connection error']` - exposes DB errors
- Line 224-228: `INSERT INTO \`{$table}\`` - MySQL backtick quoting
- Line 252-255: `UPDATE \`{$table}\` SET` - MySQL syntax
- Line 274: `DELETE FROM \`{$table}\`` - MySQL syntax
- Line 288: `SAVEPOINT LEVEL{$this->transactionDepth}` - MySQL savepoint syntax
- Line 291: `exec("SAVEPOINT LEVEL{$this->transactionDepth}")` - MySQL specific
- Line 307: `exec("RELEASE SAVEPOINT LEVEL{$this->transactionDepth}")` - MySQL specific
- Line 323: `exec("ROLLBACK TO SAVEPOINT LEVEL{$this->transactionDepth}")` - MySQL specific
- Line 355: `lastInsertId()` - MySQL auto-increment
- Line 355: `quote()` - MySQL-specific string quoting

**Impact**: Cannot switch to Google Sheets without completely replacing this class.

#### 4. config/connection.php - MySQL PDO Connection
**File**: `config/connection.php`
**Lines**: 40-59
**Problem**:
- Line 41-51: Hardcoded `mysql:host=$servername;dbname=$dbname;charset=utf8mb4` DSN
- Line 49: `PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"` - MySQL specific
- Used by legacy `api/login.php` and other old procedural files

**Impact**: Any code including this file will try to connect to MySQL.

#### 5. AuthService::attempt() - MySQL Login
**File**: `app/Services/AuthService.php`
**Lines**: 408-491
**Problem**:
- Lines 416-418: Creates MySQL PDO connection via `Database::getInstance()`
- Lines 426-438: MySQL SELECT with JOIN on users/user_roles/roles tables
- Lines 454-459: MySQL SELECT on role_permissions/permissions tables

**Impact**: Login will fail if MySQL database is unavailable or if user auth has also moved to Google Sheets.

#### 6. api/login.php - Legacy MySQL Login
**File**: `api/login.php`
**Lines**: 1-108
**Problem**: Direct MySQL connection via `config/connection.php`, uses raw PDO for authentication queries.

**Impact**: Legacy login endpoint is MySQL-only and will not work with Sheets migration.

#### 7. UploadController - MySQL Table Schema Introspection
**File**: `app/Controllers/UploadController.php`
**Lines**: 32, 84-107, 298-309
**Problem**:
- Line 32: `$systemTables = ['users', 'user_roles', 'role_permissions', 'app_settings', 'upload_templates']` - MySQL table names
- Line 100: `$db->fetchAll("DESCRIBE \`{$table}\"`)` - MySQL `DESCRIBE` command
- Line 302: `$db->fetchAll("SHOW TABLES")` - MySQL `SHOW TABLES` command

**Impact**: Upload feature will fail since it queries MySQL information_schema.

#### 8. CertificateController::store() - MySQL Write
**File**: `app/Controllers/CertificateController.php`
**Lines**: 421-458
**Problem**:
- Line 442: `$this->certificateModel->createWithLearners($sanitized)` - calls MySQL INSERT
- Line 423: `if (!AuthService::isSuperAdmin())` - role check (OK)

**Impact**: Creating certificate packets writes to MySQL (should write to Google Sheet).

#### 9. CertificateController::consolidate() - MySQL Write
**File**: `app/Controllers/CertificateController.php`
**Lines**: 466-600
**Problem**:
- Lines 526-597: Reads from Google Sheet but writes back to MySQL (`itgk_certificate` and `itgk` tables)
- Line 534: `INSERT IGNORE INTO itgk` - MySQL
- Line 539: `SELECT id FROM itgk_certificate` - MySQL
- Line 552: `UPDATE itgk_certificate` - MySQL
- Line 562: `INSERT INTO itgk_certificate` - MySQL

**Impact**: Consolidation reads from Sheets but stores results in MySQL - data inconsistency.

#### 10. CertificateController::issueBatch() - MySQL Write
**File**: `app/Controllers/CertificateController.php`
**Lines**: 605-679
**Problem**:
- Line 629: `$db = \App\Core\Database::getInstance()` - MySQL connection
- Line 634: `SELECT * FROM itgk_certificate WHERE id = ?` - MySQL
- Line 649: `UPDATE itgk_certificate` - MySQL write

**Impact**: Issuing certificates updates MySQL instead of Google Sheets.

#### 11. LearnerController::edit() - MySQL Read
**File**: `app/Controllers/LearnerController.php`
**Lines**: 125-149
**Problem**:
- Line 138: `$this->learnerModel->find($id)` - MySQL SELECT

**Impact**: Editing a learner record reads from MySQL (should read from Google Sheet).

#### 12. LearnerController::update() - MySQL Write
**File**: `app/Controllers/LearnerController.php`
**Lines**: 198-228
**Problem**:
- Line 216: `$this->learnerModel->update($id, $data)` - MySQL UPDATE

**Impact**: Updating a learner writes to MySQL (should write to Google Sheet).

#### 13. LearnerController::delete() - MySQL Delete
**File**: `app/Controllers/LearnerController.php`
**Lines**: 235-264
**Problem**:
- Line 251: `$this->learnerModel->deleteMany($ids)` - MySQL DELETE

**Impact**: Deleting learners removes data from MySQL (should update Google Sheet).

#### 14. LearnerController::issue() - MySQL Write
**File**: `app/Controllers/LearnerController.php`
**Lines**: 351-400
**Problem**:
- Line 368: `$this->learnerModel->find($id)` - MySQL SELECT
- Line 374: `$this->learnerModel->update($id, ...)` - MySQL UPDATE

**Impact**: Issuing individual learner certificates writes to MySQL (should write to Google Sheet).

#### 15. LearnerController::acknowledgement() - MySQL Read
**File**: `app/Controllers/LearnerController.php`
**Lines**: 405-420
**Problem**:
- Line 409: `$this->learnerModel->find($id)` - MySQL SELECT

**Impact**: Acknowledgement page reads from MySQL (should read from Google Sheet).

---

### 🟠 HIGH SEVERITY ISSUES

#### 16. Controller Index Pages Mix Sheet and DB Data
**File**: `app/Controllers/CertificateController.php`
**Lines**: 35-150
**Problem**: `index()` correctly reads from Google Sheets for display, BUT:
- Lines 115-137: Also reads ITGK master data from Google Sheets (correct)
- However, the `store()`, `update()`, `consolidate()`, `delete()`, `issueBatch()`, `acknowledgement()` methods still use MySQL
- The controller is inconsistent: reads from Sheets, writes to MySQL

#### 17. LearnerController Index Reads from Sheets, But CRUD Uses MySQL
**File**: `app/Controllers/LearnerController.php`
**Lines**: 48-118
**Problem**: `index()` correctly reads from Google Sheets, but:
- `edit()` (line 138) reads from MySQL via `LearnerResult::find()`
- `store()` (line 178) writes to MySQL via `LearnerResult::create()`
- `update()` (line 216) writes to MySQL via `LearnerResult::update()`
- `delete()` (line 251) deletes from MySQL via `LearnerResult::deleteMany()`
- `issue()` (lines 368, 374) uses MySQL
- `acknowledgement()` (line 409) reads from MySQL

#### 18. DashboardController Uses MySQL Models
**File**: `app/Controllers/DashboardController.php`
**Lines**: 39-44
**Problem**:
- Line 43: `$certModel->count()` - MySQL COUNT
- Line 44: `$learnerModel->count()` - MySQL COUNT
- Falls back to Sheets if count is 0 (line 46-52), but primary path uses MySQL

#### 19. Database Table References in Models
**File**: `app/Models/Certificate.php`
**Lines**: 33, 88-106, 170-199
**Problem**: References MySQL table names directly:
- `itgk_certificate` table
- `itgk_learner_result` table (in `createWithLearners` UPDATE)
- `itgk` table (in `consolidateFromLearners`)

These tables may not exist in Google Sheets context.

#### 20. GoogleSheetService - No Write Methods for Certificate/Learner Data
**File**: `app/Services/GoogleSheetService.php`
**Problem**: The service has `fetchSheet()`, `fetchParsedSheet()`, `updateSheetRow()`, `batchUpdateRows()`, and `fetchRawRow()` but NO methods for:
- Inserting new rows into certificate or learner sheets
- Deleting rows from sheets
- Searching/filtering within sheets
- The write methods (`updateSheetRow`, `batchUpdateRows`) work but are not integrated with the models

---

### 🟡 MEDIUM SEVERITY ISSUES

#### 21. Mixed Data Source Pattern Throughout Codebase
The application has an inconsistent pattern where some methods read from Google Sheets and others read/write to MySQL. This will cause data inconsistency and confusion.

#### 22. Role Hierarchy Mismatch with Database
**File**: `app/Services/AuthService.php`
**Lines**: 44-51
**Problem**: The `ROLE_HIERARCHY` includes `COORDINATOR` (20) and `GUEST` (0) but the database only has PARTNER, EMPLOYEE, ADMIN, SUPERADMIN. COORDINATOR and GUEST roles exist in code but NOT in the database.

#### 23. `updateTracking()` Method - MySQL-Specific SQL
**File**: `app/Models/Certificate.php`
**Lines**: 175-197
**Problem**: Uses `ON DUPLICATE KEY UPDATE` - MySQL-specific syntax that won't work with Google Sheets.

#### 24. `consolidateFromLearners()` Method - MySQL Transactions
**File**: `app/Models/Certificate.php`
**Lines**: 300-461
**Problem**: Uses MySQL transactions (`BEGIN`, `COMMIT`, `ROLLBACK`) which don't exist in Google Sheets API.

#### 25. `getMonthlyStats()` - MySQL Date Functions
**File**: `app/Models/Certificate.php` line 283-292 and `app/Models/LearnerResult.php` line 202-211
**Problem**: Uses `DATE_FORMAT()`, `NOW()`, `INTERVAL` - MySQL-specific SQL functions.

#### 26. `getAnalytics()` - MySQL Aggregate Functions
**File**: `app/Models/Certificate.php` lines 262-271 and `app/Models/LearnerResult.php` lines 179-190
**Problem**: Uses `SUM(CASE WHEN...)`, `COUNT(*)` - MySQL aggregate syntax (these would need to be computed in PHP for Sheets data).

#### 27. `search()` Method - MySQL LIKE
**File**: `app/Models/Certificate.php` line 551-559 and `app/Models/LearnerResult.php` line 279-288
**Problem**: Uses `LIKE` with `%` wildcards - MySQL-specific. Google Sheets would need different search approach.

#### 28. `updateTracking()` Uses `ON DUPLICATE KEY UPDATE`
**File**: `app/Models/Certificate.php` lines 190-196
**Problem**: MySQL-specific upsert syntax. Google Sheets API doesn't support this.

#### 29. Legacy `/actions/` Files Still Reference MySQL
**Files**: `actions/add_certificate.php`, `actions/add_learner_result.php` (if they exist)
**Problem**: These procedural files likely contain MySQL queries that are redundant with the models but still reference the old database.

#### 30. `_backup/` Folder Contains Legacy PHP Files with MySQL Queries
**Files**: `_backup/itgk_certificate.php`, `_backup/learner_result.php`
**Problem**: Old monolithic pages with embedded MySQL queries that are no longer needed.

---

### 🔵 LOW SEVERITY / COSMETIC ISSUES

#### 31. Inconsistent Role Display Names
**File**: `app/Services/AuthService.php` line 386-395
**Problem**: `getRoleDisplayName()` only handles SUPERADMIN, ADMIN, EMPLOYEE, GUEST but not PARTNER or COORDINATOR (which are in the hierarchy).

#### 32. Mixed Auth Patterns
**Files**: Various controllers
**Problem**: Some controllers use `$this->requireAuth()`, others use `$this->requireRole()`, others use `AuthService::isSuperAdmin()`, others use `AuthService::hasRoleLevel()`. Inconsistent patterns.

#### 33. CSRF Token Validation Inconsistency
**Files**: Various controllers
**Problem**: Some methods validate CSRF tokens, others don't. Some check `$_POST['csrf_token']`, others check `$_SERVER['HTTP_X_CSRF_TOKEN']`.

#### 34. Error Handling Inconsistency
**Files**: Various controllers
**Problem**: Some methods use `$this->json()` for errors, others use `Logger::error()` + return arrays, others use `throw new \Exception()`.

#### 35. SQL Injection Risk in Legacy Files
**File**: `api/login.php` line 34-38
**Problem**: Uses named placeholders (`:input1`, `:input2`, `:input3`) which is safe, but the file is a legacy endpoint that bypasses the middleware layer.

#### 36. GoogleSheetService - SSL Verification Disabled
**File**: `app/Services/GoogleSheetService.php` line 157
**Problem**: `CURLOPT_SSL_VERIFYPEER => false` - security risk in production.

---

## SUMMARY OF MIGRATION GAPS

| Component | Read from Sheets | Write to Sheets | MySQL Still Used | Status |
|-----------|:---:|:---:|:---:|:---|
| Certificate `index()` | ✅ | ❌ | ❌ | Partial ✅ |
| Certificate `store()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Certificate `update()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Certificate `delete()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Certificate `consolidate()` | ✅ | ❌ | ✅ | 🔴 Broken |
| Certificate `issueBatch()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Certificate `updateTracking()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Learner `index()` | ✅ | ❌ | ❌ | Partial ✅ |
| Learner `store()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Learner `update()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Learner `delete()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Learner `issue()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Learner `issueIndividual()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Learner `acknowledgement()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Auth `attempt()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Upload `getTableSchema()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Upload `performUpload()` | ❌ | ❌ | ✅ | 🔴 Broken |
| Dashboard `index()` | ✅ (fallback) | ❌ | ✅ | Partial ✅ |

---

## RECOMMENDATIONS

### Immediate (Blockers)
1. **Replace `Certificate` model** with Google Sheets read/write operations
2. **Replace `LearnerResult` model** with Google Sheets read/write operations
3. **Remove MySQL dependency from `Database.php`** or create a `SheetService` abstraction
4. **Remove `config/connection.php`** MySQL connection if no longer needed
5. **Remove `api/login.php`** legacy file or migrate to Sheets
6. **Replace `UploadController`** table introspection with Sheets metadata API
7. **Migrate AuthService::attempt()** to Google Sheets or external auth provider

### Short-term
8. **Create a `SheetCertificateService`** to handle all certificate sheet operations
9. **Create a `SheetLearnerService`** to handle all learner sheet operations
10. **Update `CertificateController`** to use sheet services instead of models for writes
11. **Update `LearnerController`** to use sheet services instead of models for CRUD
12. **Remove `ON DUPLICATE KEY UPDATE`** and replace with Sheets append/update

### Long-term
13. **Remove `Database.php`** entirely if MySQL is no longer needed
14. **Remove MySQL-related environment variables** from `.env`
15. **Clean up `_backup/` and `/actions/`** legacy files
16. **Standardize auth pattern** across all controllers
17. **Add integration tests** for Google Sheets operations