# Student Assignment Management System (SAMS)

SAMS is a university web application. Teachers publish assignments, students upload their work, and teachers record marks and feedback. An administrator looks after user accounts and the name, session, and announcement shown on the site.

The project is plain PHP and MySQL. It does not use Laravel, React, Node.js, Composer, or npm. Another person can run it on a laptop with XAMPP after following the steps below from start to finish.

You read this file in the project folder or on GitHub. The website itself does not display this file.

## What the other laptop must have

Install these before opening the site. Nothing else is required.

| Item | Why it is needed | Notes |
| --- | --- | --- |
| Windows 10 or Windows 11 | The paths in this guide use `C:\xampp` | About 1 GB of free disk space |
| [XAMPP 8.2](https://www.apachefriends.org/) | Provides Apache, PHP 8, MySQL, and phpMyAdmin | PHP must be 8.0 or newer |
| Google Chrome, Microsoft Edge, or Firefox | To open the site | Any current browser is fine |
| The SAMS folder | The application code and the database file | From a GitHub ZIP or `git clone` |
| Git (optional) | Only if you clone instead of downloading a ZIP | [https://git-scm.com/download/win](https://git-scm.com/download/win) |

You do not install Node.js, Python, Composer, or a separate MySQL server. XAMPP already includes the database.

XAMPP 8.2 labels the database service **MySQL**. That service is MariaDB, and it accepts this project's MySQL script. The same `database/sams_db.sql` file also imports on MySQL 8.

After XAMPP and the project files are on the laptop, the site runs with no internet connection. Bootstrap is already stored inside the project.

## Setup checklist

Do the steps in this order. Skip none of them.

1. Install XAMPP.
2. Get the project by ZIP download or Git clone.
3. Put the folder that contains `index.php` at `C:\xampp\htdocs\sams`.
4. Start Apache.
5. Start MySQL.
6. Import `database/sams_db.sql`.
7. Leave `config/database.php` unchanged unless the MySQL root user has a password.
8. Open [http://localhost/sams/](http://localhost/sams/).
9. Log in with one of the demo accounts at the bottom of this file.

## 1. Install XAMPP

1. Open [https://www.apachefriends.org/](https://www.apachefriends.org/).
2. Download the Windows installer for **XAMPP 8.2** (or any XAMPP build that includes PHP 8).
3. Run the installer. If Windows asks for permission, choose **Yes**.
4. If the installer offers the Microsoft Visual C++ libraries, accept them.
5. When it asks for a folder, keep `C:\xampp`.
6. Select **Apache**, **MySQL**, **PHP**, and **phpMyAdmin**. Tomcat, Perl, Mercury, and FileZilla can stay unselected.
7. Finish the installer. You do not need to install extra Bitnami applications.
8. If a Windows Firewall prompt appears for Apache or MySQL, allow them on private networks.

## 2. Get the project

Use either the ZIP or Git. You only need one of them.

### Option A: download the ZIP

1. Open the GitHub repository in a browser.
2. Click the green **Code** button.
3. Click **Download ZIP**.
4. Extract the ZIP. Windows usually creates a folder whose name ends in `-main`, for example `student-assignment-management-system-main`.
5. Open that folder and find the directory that contains both of these files:

```text
index.php
database\sams_db.sql
```

That directory is the application. If those two files are inside a nested `sams` folder, use that inner folder.

### Option B: clone with Git

Install Git, then open Command Prompt and run the real repository address. This example uses the suggested repository name. Replace the address with the one shown on GitHub:

```bat
cd C:\xampp\htdocs
git clone https://github.com/YOUR-USERNAME/student-assignment-management-system.git sams
```

Cloning straight into `C:\xampp\htdocs\sams` finishes the next step as well, but only when `index.php` is at the root of the repository. If the clone contains a nested `sams` folder, move that inner folder as described below.

## 3. Place the project in htdocs

Apache only serves files from `C:\xampp\htdocs`.

1. Rename the application folder to `sams` if it has another name.
2. Move that folder so this file exists:

```text
C:\xampp\htdocs\sams\index.php
```

These files must sit beside `index.php`:

```text
C:\xampp\htdocs\sams\database\sams_db.sql
C:\xampp\htdocs\sams\config\database.php
C:\xampp\htdocs\sams\auth\login.php
```

A correct layout looks like this:

```text
C:\xampp\htdocs\sams\index.php
C:\xampp\htdocs\sams\README.md
C:\xampp\htdocs\sams\admin\
C:\xampp\htdocs\sams\auth\
C:\xampp\htdocs\sams\database\sams_db.sql
```

This layout is wrong, and the site will not open at the expected address:

```text
C:\xampp\htdocs\sams\sams\index.php
C:\xampp\htdocs\student-assignment-management-system-main\index.php
```

Do not leave the project on the Desktop or inside Downloads. Apache will not see it there.

## 4. Start Apache

1. Open the XAMPP Control Panel from the Start menu, or run `C:\xampp\xampp-control.exe`.
2. Next to **Apache**, click **Start**.
3. The Apache row turns green and shows port 80.
4. If it stays red, use the Troubleshooting section below. Do not continue until Apache is running.

## 5. Start MySQL

1. In the same XAMPP Control Panel, next to **MySQL**, click **Start**.
2. The MySQL row turns green and shows port 3306.
3. Leave both Apache and MySQL running while anyone uses SAMS.
4. Closing the Control Panel window does not stop the services. Use **Stop** only when you want to shut the site down.

## 6. Import the database

The file `database/sams_db.sql` creates a database named `sams_db`, builds the tables, and inserts the demo accounts, assignments, and sample submissions.

Importing that file again deletes the current `sams_db` database and loads the demo data from scratch. Use a fresh import when you want the original presentation data back.

### Option A: phpMyAdmin

1. Apache and MySQL are both green in XAMPP.
2. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
3. Click the **Import** tab.
4. Click **Choose File** and select:

```text
C:\xampp\htdocs\sams\database\sams_db.sql
```

5. Leave the other settings as they are.
6. Scroll down and click **Go**.
7. phpMyAdmin reports a successful import. The left column shows a database named `sams_db`.
8. Click `sams_db`, then click the `users` table. You should see seven accounts, including `admin@sams.local`.

### Option B: Command Prompt

Open Command Prompt and run:

```bat
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\sams\database\sams_db.sql
```

There is no success message when this command works. It simply returns to a new line. If Windows says the system cannot find the file, the project is not at `C:\xampp\htdocs\sams`.

If the MySQL root account has a password, add `-p` and type the password when asked:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -p < C:\xampp\htdocs\sams\database\sams_db.sql
```

## 7. Database connection

Open `C:\xampp\htdocs\sams\config\database.php`. A normal XAMPP install already matches these values, so most people change nothing:

| Setting in `database.php` | Default value |
| --- | --- |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `sams_db` |
| `DB_USER` | `root` |
| `DB_PASS` | `''` (empty) |

Change `DB_PASS` only when you previously set a password for the MySQL root user in XAMPP. Put the password between the quotes:

```php
const DB_PASS = 'your-mysql-password';
```

Do not create a `.env` file and do not put the password in a web page. This PHP file is the only place the password belongs. Apache is set to refuse direct requests for the `config` folder.

## 8. Open the site

1. Apache is started.
2. MySQL is started.
3. `sams_db` has been imported.
4. `C:\xampp\htdocs\sams\index.php` exists.
5. Open a browser and go to:

[http://localhost/sams/](http://localhost/sams/)

You should see the SAMS home page with **Log in** and **Register**. The login page does not print the passwords. They are listed in the next section of this file.

Type the address exactly. `http://localhost/sams` without the project in `htdocs\sams` shows an XAMPP or 404 page.

## 9. Sign in and confirm it works

Use **Log in** with one account at a time. After each test, use **Log out** before trying the next role.

| Check | Account | What you should see |
| --- | --- | --- |
| Administrator | `admin@sams.local` / `Admin@123` | User totals, a user list, and **System information** |
| Teacher | `teacher@sams.local` / `Teacher@123` | Assignments, including a draft and a closed assignment |
| Student | `student@sams.local` / `Student@123` | Published assignments, one uploaded file, and feedback on Operating Systems Review |
| Wrong role | Stay logged in as the student and open `http://localhost/sams/admin/dashboard.php` | An Unauthorized page, not the administrator dashboard |
| Registration | Log out, then open **Register** | A new student account can be created with a new email |

Useful pages for a demonstration:

- Teacher: open **Web Security Case Study**. One submission is waiting for a grade and one is already graded.
- Student `student@sams.local`: open **My submissions** and then the Operating Systems feedback.
- Administrator: search users, and edit the institution name under **System information**. That name appears in the top bar.

## Demo login credentials

These passwords are stored in the database only as hashes. They are not shown on the login page.

| Role | Email | Password |
| --- | --- | --- |
| Administrator | admin@sams.local | Admin@123 |
| Teacher | teacher@sams.local | Teacher@123 |
| Student | student@sams.local | Student@123 |

Extra demo accounts use the same teacher or student password:

| Role | Email | Password | What it is for |
| --- | --- | --- | --- |
| Teacher | teacher2@sams.local | Teacher@123 | Owns a separate published assignment |
| Student | student2@sams.local | Student@123 | Has a graded submission |
| Student | student3@sams.local | Student@123 | Has a submission waiting for a grade |
| Student | student.inactive@sams.local | Student@123 | Deactivated. Login is rejected |

Passwords are case sensitive. `Teacher@123` has a capital T and an `@` sign.

## Features

- Separate administrator, teacher, and student areas
- Login, registration when an administrator allows it, and logout
- Passwords stored with `password_hash()` and checked with `password_verify()`
- Role checks so a student cannot open teacher or administrator pages
- Administrator: search and filter users, add, edit, deactivate, reactivate, and delete accounts
- The only active administrator account cannot be deleted or deactivated
- Teacher: create, edit, publish, close, and delete assignments
- Assignment title, instructions, deadline, maximum marks, and status (`draft`, `published`, `closed`)
- Students see published assignments only
- Secure file upload for PDF, DOC, DOCX, and ZIP files, up to 10 MB
- Teachers download submitted files, enter marks, and write feedback
- Students see submission status, marks, and feedback
- Late uploads are blocked unless an administrator turns that option on
- CSRF protection, prepared SQL statements, and escaped output

## Technologies

- PHP 8 or newer
- MySQL 8-compatible SQL through PDO
- HTML5, CSS3, and JavaScript
- Bootstrap 5.3, stored in `assets/vendor` so pages work offline
- Apache from XAMPP
- PHP sessions for login

## Folder structure

```text
sams/
├── admin/                  Administrator pages
├── assets/
│   ├── css/style.css
│   ├── js/script.js
│   ├── uploads/            Submitted files, including four demo PDFs
│   └── vendor/             Bootstrap and icons
├── auth/                   Login, registration, logout
├── config/
│   ├── config.php          Sessions, errors, and security headers
│   └── database.php        Database username and password
├── database/sams_db.sql    Tables and demo data
├── includes/               Shared layout, auth, and validation
├── logs/                   PHP error log, hidden from visitors
├── student/                Student pages
├── teacher/                Teacher pages
├── index.php               Public home page
└── README.md
```

## How file uploads work

1. A student opens a published assignment and chooses a file.
2. PHP checks the upload error, the 10 MB size limit, the extension, the MIME type, and the file signature.
3. Allowed types are PDF, DOC, DOCX, and ZIP.
4. The original file name is not used on disk. The file is stored under a random name such as `a1b2c3....pdf` in `assets/uploads/`.
5. That folder refuses direct browser requests. Guessing a file name does not download it.
6. A teacher, or the student who owns the work, downloads the file through a PHP page that checks the login and the role.
7. A student can replace a file until a teacher grades it, and only before the deadline, unless late submissions are enabled in **System information**.
8. Deleting an assignment also deletes its uploaded files.

The four demo PDFs are already in `assets/uploads`. Their names start with `c0ffee`. Keep those files when you copy the project, or the sample download buttons will have nothing to send.

If an upload says the file is too large even when it is under 10 MB, PHP itself is rejecting it. Open `C:\xampp\php\php.ini`, set both lines below to at least `16M`, save the file, and restart Apache from the XAMPP Control Panel:

```ini
upload_max_filesize=16M
post_max_size=16M
```

A fresh XAMPP 8.2 install already allows 40 MB, so this change is only needed on a laptop where someone lowered those limits.

## Troubleshooting

**The browser says the site cannot be reached.** Apache is not running. Start it in the XAMPP Control Panel.

**Apache will not start.** Another program is using port 80. In the XAMPP Control Panel use the Netstat button beside Apache, or stop IIS, Skype, or the Windows service named World Wide Web Publishing Service. You can change Apache's port in `C:\xampp\apache\conf\httpd.conf`, but then the address becomes `http://localhost:YOURPORT/sams/`.

**The address shows the plain XAMPP dashboard, or a 404 page.** The folder is in the wrong place. `index.php` must be exactly at `C:\xampp\htdocs\sams\index.php`. A ZIP often leaves an extra folder such as `htdocs\sams\sams` or `htdocs\something-main`. Move the inner folder up and rename it to `sams`.

**MySQL will not start.** Port 3306 may already be used by another MySQL installation. Stop that Windows service, or change the port in the XAMPP MySQL configuration and in `DB_PORT` inside `config/database.php`.

**The page says SAMS cannot reach the database.** Start MySQL. If the message says `sams_db` was not found, import `database/sams_db.sql`. If it says the password was rejected, set `DB_PASS` in `config/database.php`.

**The browser shows a blank page.** Open `C:\xampp\htdocs\sams\logs\error.log`. Visitors do not see the raw database error. It is written to that log.

**Login always fails.** Import the SQL file after the folder is in `htdocs`. The teacher password is `Teacher@123`, with a capital T and an `@`. An inactive account such as `student.inactive@sams.local` is rejected until an administrator reactivates it.

**phpMyAdmin import fails because the file is too large.** This SQL file is small. If a future file is rejected, raise `upload_max_filesize` and `post_max_size` in `php.ini`, restart Apache, and import again. You can also use the Command Prompt method, which has no upload limit.

**A student opens a teacher or administrator address and sees Unauthorized.** The role check is working. Log out and log in with the matching account.

**Upload fails or the file is rejected.** Use a real PDF, DOC, DOCX, or ZIP under 10 MB. A text file renamed to `.pdf` is rejected because the contents do not match the extension.

**A demo download says the file is missing.** The matching PDF was not copied. The sample files belong in `assets\uploads` and their names start with `c0ffee`.

**PHP file changes do not appear.** Refresh the browser. Restart Apache only after you edit `php.ini`.

**You want the original demo data back.** Import `database/sams_db.sql` again. This removes accounts, assignments, and submissions created during testing.

## Everyday use after setup

1. Open the XAMPP Control Panel.
2. Start **Apache** and **MySQL** if they are not already green.
3. Open [http://localhost/sams/](http://localhost/sams/).
4. When you are finished, you may stop Apache and MySQL. The database and uploaded files stay on the laptop.
