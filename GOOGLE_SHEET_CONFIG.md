# ITGK Certificate Portal — Google Sheet Configuration & Schema Reference

## 📌 Active Google Sheet Credentials

- **Spreadsheet ID**: `18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4`
- **Tab Name**: `Certificate`
- **Cell Range**: `Certificate!A1:V`
- **Total Records**: `2,386` (as of July 2026)

---

## 📊 Table Schema Column Mappings

| Column | Google Sheet Header Name | Database / Model Field Name | Description |
| :--- | :--- | :--- | :--- |
| **A** | `S. No.` | `s_no` / `id` | Serial Number / Packet ID |
| **B** | `Course Name` | `course_name` | Course Title (e.g. RS-CIT) |
| **C** | `DATE` | `receiving_date` / `date` | Receipt / Issue Date |
| **D** | `EXAM` | `exam_name` | Exam Session Name |
| **E** | `EXAM_DATE_ITGK` | `exam_date_itgk` | Combined Exam Date & Code |
| **F** | `ITGK CODE` | `itgk_code` | Unique ITGK Center Code |
| **G** | `DISTRICT` | `district` | District Name |
| **H** | `ABSENT` | `absent` | Absent Learner Count |
| **I** | `FAIL` | `fail` | Failed Learner Count |
| **J** | `PASS` | `pass` | Passed Learner Count |
| **K** | `UFM` | `ufm` | Malpractice / UFM Count |
| **L** | `Grand Total` | `grand_total` | Total Learner Packet Count |
| **M** | `Packet No.` | `packet_no` | Physical Packet Number |
| **N** | `Certificate No. From` | `cert_no_from` | Starting Certificate Serial Number |
| **O** | `Certificate No. To` | `cert_no_to` | Ending Certificate Serial Number |
| **P** | `Current Location` | `current_location` | Current Physical Location |
| **Q** | `STATUS` | `status` | Status (`Available`, `ISSUED`, `In Transit`) |
| **R** | `Remark` | `remark` | Remarks / Notes |
| **S** | `Receiver Name` | `receiver_name` | Recipient Full Name |
| **T** | `Receiver Designation` | `receiver_designation` | Recipient Job Title / Role |
| **U** | `Receiver Mobile Number` | `receiver_mobile` | Recipient Mobile Number |
| **V** | `Image` | `image` | Proof Image URL / Asset Link |

---

## ⚙️ Environment Variables (`.env`)

```env
# Google Sheet Configuration
GSHEET_CERTIFICATE_ID=18fxE3NS6fT2Nkrgpw-pFFvLSIXIUD2mvSeCBiacJVv4
GSHEET_CERTIFICATE_TAB=Certificate
GSHEET_CERTIFICATE_RANGE=Certificate!A1:V
```
