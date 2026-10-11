# Student Admin Portal (Modern Blue Theme) - PHP + MySQL

A beginner-friendly admin panel to manage student records and their uploaded photos/documents.

## 1. Project structure
```
student_admin/
├── config.php          Database connection + shared helper functions (validation, upload, layout)
├── index.php           Home dashboard + Student List + search
├── add_student.php     Add a student (+ optional profile photo)
├── edit_student.php    Edit a student (+ replace profile photo)
├── view_student.php    Student profile, files list, upload form
├── delete_student.php  Delete student + their files (POST only)
├── upload_file.php     Handles the upload form (POST only)
├── delete_file.php     Deletes one file (POST only)
├── database.sql        Database + tables + sample data
├── css/style.css       Design
└── uploads/
    ├── .htaccess       Blocks script execution in uploads
    ├── profiles/       Profile photos are saved here
    └── documents/      ID proofs, certificates, other documents
```

## 2. XAMPP setup
1. Install XAMPP (https://www.apachefriends.org) - PHP 8.0 or newer is recommended.
2. Open **XAMPP Control Panel** and click **Start** for **Apache** and **MySQL** (both turn green).
3. Copy the whole `student_admin` folder into:
   - Windows: `C:\xampp\htdocs\`
   - macOS: `/Applications/XAMPP/htdocs/`
   - Linux: `/opt/lampp/htdocs/`
   so you have `htdocs/student_admin/config.php`.
4. Make sure `uploads/profiles` and `uploads/documents` exist (they are included in the zip). On Linux/macOS run:
   `sudo chmod -R 775 /opt/lampp/htdocs/student_admin/uploads`

## 3. Create the database in phpMyAdmin
**Option A - Import (easiest)**
1. Open http://localhost/phpmyadmin
2. Click the **Import** tab -> **Choose File** -> select `student_admin/database.sql`
3. Click **Import** (at the bottom). The database `college_admin` with tables `students` and `student_files` appears on the left.

**Option B - Paste SQL**
1. Open phpMyAdmin -> **SQL** tab.
2. Paste the full content of `database.sql` -> click **Go**.

## 4. Run the project
Open: **http://localhost/student_admin/**

If your MySQL root user has a password, edit `DB_PASS` in `config.php`.

## 5. Sample student data (already inserted by database.sql)
| Name | Roll No | Course | Department | Sem | Email | Phone |
|---|---|---|---|---|---|---|
| Aarav Sharma | CS2024001 | B.Tech | Computer Science | 3 | aarav.sharma@example.com | 9876543210 |
| Priya Nair | EC2024014 | B.Tech | Electronics | 5 | priya.nair@example.com | 9123456780 |
| Rahul Verma | ME2023022 | B.E | Mechanical | 7 | rahul.verma@example.com | 9988776655 |
| Sneha Reddy | BC2024031 | B.Com | Commerce | 2 | sneha.reddy@example.com | 9090909090 |
| Karthik Raj | BS2023045 | B.Sc | Physics | 4 | karthik.raj@example.com | 9812345678 |

## 6. What each page shows
- **index.php** - 4 summary cards (students, files, courses, departments) and the student table with photo thumbnail, search box and View / Edit / Delete buttons.
- **add_student.php** - Form with all fields (Student ID is auto-generated), optional profile photo. Errors are listed in a red box; the form keeps what you typed.
- **view_student.php** - Profile photo, all student details, an upload form, and a table of files with category, name, type, size, upload date and Open / Download / Delete buttons.
- **edit_student.php** - Same form pre-filled; shows current photo; roll number may stay the same but cannot match another student.
- **delete_student.php / delete_file.php / upload_file.php** - No screen of their own. They do the work, set a green/red message, and redirect back.

## 7. Validation rules
- All fields required; name letters only; email valid; phone 10-15 digits (optional `+`); semester 1-8.
- Roll number unique (checked in PHP and by a UNIQUE key in MySQL).
- Files: profile photo JPG/JPEG/PNG; other files JPG/JPEG/PNG/PDF/DOC/DOCX; max 5 MB.
- Blocks php, exe, js, bat, sh, html etc. anywhere in the file name (e.g. `shell.php.jpg`).
- Real content is checked with `finfo` (MIME) and `getimagesize()`, not just the extension.
- Stored name is unique: `category_studentID_timestamp_randomhex.ext` - nothing is overwritten.

## 8. Test checklist
1. Add a student with an invalid email -> red error.
2. Add a student with roll number `CS2024001` -> duplicate error.
3. Upload `test.php` or `virus.exe` -> blocked.
4. Rename a `.txt` file to `fake.pdf` and upload -> rejected (content mismatch).
5. Upload a 6 MB PDF -> too large message.
6. Upload a profile photo twice -> the new one replaces the old one.
7. Delete a student -> files disappear from `uploads/` too.

## 9. Common errors and solutions
| Problem | Solution |
|---|---|
| "Database connection failed" | Start MySQL in XAMPP; check `DB_USER` / `DB_PASS` in config.php; import database.sql. |
| `Unknown database 'college_admin'` | You have not imported database.sql yet. |
| Page shows PHP code / blank | Open via `http://localhost/...`, not by double-clicking the file. Apache must be running. |
| 404 Not Found | Folder must be `htdocs/student_admin` (not nested twice). |
| "Upload folder is not writable" | Linux/macOS: `chmod -R 775 uploads`. Windows: right-click folder -> uncheck Read-only. |
| "File is too large" for files under 5 MB | Raise `upload_max_filesize` and `post_max_size` in `C:\xampp\php\php.ini`, restart Apache. |
| `Class 'finfo' not found` | In php.ini enable `extension=fileinfo` (remove the `;`), restart Apache. |
| `Call to undefined method mysqli_stmt::get_result()` | Use XAMPP's PHP (mysqlnd). Enable `extension=mysqli` in php.ini. |
| Port 80 / 3306 already in use | Stop Skype/IIS/other MySQL, or change the port in XAMPP Config. |
| Uploaded image not displaying | Check the file exists in `uploads/profiles/`; check the `uploads/.htaccess` file was not edited incorrectly. |
| "Security token expired" | Session expired; reload the page and submit again (cookies must be enabled). |
| Cannot delete (foreign key error) | Import the provided database.sql so the FK uses `ON DELETE CASCADE`. |
