# Business Logic & Features Inventory

## 1. ITGK Certificate Management
### Consolidation Logic (The "Core" Feature)
- **Goal**: Aggregate individual learner results into a single ITGK Certificate record.
- **Source**: `itgk_learner_result` table.
- **Target**: `itgk_certificate` table.
- **Grouping Criteria**: 
  - `itgk_code`
  - `course_name`
  - `exam_name`
  - `exam_date`
- **Aggregation Rules**:
  - `total_learners` = Count of all students in group.
  - `pass` = Count where result is 'PASS'.
  - `fail` = Count where result is 'FAIL'.
  - `absent` = Count where result is 'ABSENT'.
  - `ufm` = Count where result is 'UFM'.
  - `receiving_date` = Earliest `receiving_date` from learners.
- **Upsert Rule**:
  - If a certificate with same `itgk_code` + `course_name` + `exam_name` exists: **UPDATE** it.
  - Else: **INSERT** a new record.
  - Always set `status` = 'Available' on update/insert.

## 2. Learner Results
- **Required Fields**: `itgk_code`, `course_name`, `exam_name`, `exam_date`.
- **Validation**: Cannot consolidate if these fields are missing.

## 3. Analytics
- **Dashboard**:
  - Total Certificates
  - Total Learners
  - Status Breakdown (Available, Issued, InTransit)
  - Recent Activities (Latest Certificates)

## 4. Security & Permissions
- **SuperAdmin**: Can Add/Edit/Delete/Consolidate.
- **Employee/Admin**: View only (mostly).
