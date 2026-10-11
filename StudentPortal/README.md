# StudentPortal – Java Servlet + JSP + MySQL

Login-protected student profile portal. Student data comes from MySQL (JDBC + PreparedStatement); profile images and documents are streamed by `FileServlet`, so real server paths are never shown in the browser.

**Use Tomcat 9** (it uses the `javax.servlet` packages). Tomcat 10/11 will NOT work with this code.

---------------------------------------------------------------------

## 1. Sample login (created by database.sql)

| Username | Password   |
|----------|------------|
| admin    | admin123   |
| teacher  | teacher123 |

## 2. Sample students

| ID | Name          | Roll      | Profile image | Documents |
|----|---------------|-----------|---------------|-----------|
| 1  | Priya Sharma  | 25CSE001  | yes (jpg)     | 2 (PDF + PNG) |
| 2  | Arjun Kumar   | 25ECE014  | yes (png)     | 1 PDF |
| 3  | Meena Lakshmi | 25IT007   | **none**      | 2 PDFs |
| 4  | Rahul Verma   | 25ME021   | yes (jpg)     | **none** |
| 5  | Sneha Reddy   | 25AIDS003 | yes (png)     | 1 PDF |

Meena shows the "no profile image" case, Rahul shows "No documents uploaded."

---------------------------------------------------------------------

## 3. Setup steps

### 3.1 MySQL (XAMPP)
1. Open XAMPP Control Panel → Start **MySQL** (Apache is not needed).
2. Open http://localhost/phpmyadmin → **Import** tab → choose `database.sql` → **Go**.
   (or command line: `mysql -u root -p < database.sql`)
3. You should now see the database `student_portal` with tables `users`, `students`, `student_files`.

### 3.2 Database username / password
Open `src/main/java/com/studentportal/dao/DBConnection.java` and edit:
```java
private static final String USER = "root";
private static final String PASSWORD = "";      // XAMPP default is empty
```
If MySQL runs on another port, change `3306` in the `URL` line.

### 3.3 JDBC MySQL Connector + JSTL
* **With Maven (recommended):** `pom.xml` already downloads `mysql-connector-j` and `jstl`. Nothing else to do.
* **Without Maven:** download `mysql-connector-j-8.3.0.jar` and `jstl-1.2.jar` and copy both into `src/main/webapp/WEB-INF/lib/`.

### 3.4 Tomcat 9
1. Download "Tomcat 9 – zip/installer" from tomcat.apache.org and unzip it (e.g. `C:\tomcat9`).
2. Install JDK 11 or newer and set `JAVA_HOME`.

### 3.5 Create / import the project
**Eclipse (Enterprise Java edition)**
1. File → Import → Maven → *Existing Maven Projects* → select the `StudentPortal` folder → Finish.
2. Window → Preferences → Server → Runtime Environments → Add → *Apache Tomcat v9.0* → point to your Tomcat folder.
3. Right-click project → Run As → *Run on Server* → choose Tomcat 9 → Finish.
4. To get the URL `/StudentPortal`: Servers view → double-click server → Modules tab → select the project → Edit → Path = `/StudentPortal`.

**IntelliJ IDEA (Ultimate, or Community + Smart Tomcat plugin)**
1. File → Open → select `StudentPortal/pom.xml` → *Open as Project*. Wait for Maven to import.
2. Run → Edit Configurations → + → *Tomcat Server → Local* → select Tomcat 9.
3. Deployment tab → + → Artifact → `StudentPortal:war exploded` → set *Application context* to `/StudentPortal`.
4. Click Run.

**Manual WAR deployment**
```
mvn clean package
copy target\StudentPortal.war  to  <tomcat>\webapps\
<tomcat>\bin\startup.bat
```

### 3.6 Folders
Already created inside the project:
```
src/main/webapp/uploads/profiles/     <- profile images
src/main/webapp/uploads/documents/    <- PDFs and other documents
```
Values in the database are paths **relative to `uploads`**, for example `profiles/priya_sharma.jpg`.
Direct URLs such as `/StudentPortal/uploads/...` are blocked by `web.xml`; files are only served through `/file`.

Optional: to keep uploads outside the web app, create e.g. `C:/student_portal_uploads/profiles` and `.../documents`,
copy the sample files there, and put that path in the `uploadBasePath` context-param of `web.xml`.

### 3.7 Run
Open **http://localhost:8080/StudentPortal/** and log in with `admin / admin123`.

---------------------------------------------------------------------

## 4. URLs

| URL                                   | Handled by                | Purpose |
|---------------------------------------|---------------------------|---------|
| `/login.jsp`, `/login`                | login.jsp / LoginServlet  | login page / check password |
| `/students` (`?q=search`)             | StudentServlet → students.jsp | student list |
| `/student?id=1`                       | StudentServlet → student-profile.jsp | profile page |
| `/file?type=profile&id=1`             | FileServlet               | profile image of student 1 |
| `/file?type=document&id=1`            | FileServlet               | view document 1 |
| `/file?type=document&id=1&download=1` | FileServlet               | download document 1 |
| `/logout`                             | LogoutServlet             | end session |

## 5. File guide

| File | Put it in | What it does |
|------|-----------|--------------|
| `database.sql` | project root | Creates DB, 3 tables, foreign key, sample data |
| `pom.xml` | project root | Maven build (WAR), MySQL driver, JSTL |
| `dao/DBConnection.java` | `src/main/java/com/studentportal/dao` | Opens JDBC connections (edit user/password here) |
| `dao/UserDAO.java` | same | Checks username/password (password stored as SHA-256) |
| `dao/StudentDAO.java` | same | Fetches students, search, profile image path |
| `dao/FileDAO.java` | same | Fetches the files of a student / one file |
| `model/Student.java`, `model/StudentFile.java` | `.../model` | Plain Java objects for table rows |
| `filter/AuthFilter.java` | `.../filter` | Sends users without a session to `login.jsp`; blocks page caching |
| `controller/LoginServlet.java` | `.../controller` | Login, creates `HttpSession` |
| `controller/LogoutServlet.java` | same | Logout, invalidates session |
| `controller/StudentServlet.java` | same | Loads data and forwards to the JSPs |
| `controller/FileServlet.java` | same | Streams images/PDFs, hides paths, blocks path traversal |
| `login.jsp`, `students.jsp`, `student-profile.jsp` | `src/main/webapp` | The three pages (JSTL + EL) |
| `css/style.css` | `src/main/webapp/css` | Blue/white responsive theme |
| `WEB-INF/web.xml` | `src/main/webapp/WEB-INF` | Welcome page, session timeout, uploads folder, blocks `/uploads/*` |

(`UserDAO` and `AuthFilter` are two small extra classes beyond the requested structure.)

## 6. Security summary
* Pages are protected twice: `AuthFilter` and a session check at the top of each JSP.
* Login creates a brand-new session (prevents session fixation); sessions expire after 30 minutes.
* The browser only sees ids (`/file?type=document&id=3`), never server paths.
* `FileServlet` resolves each path inside the uploads folder and rejects `..`, absolute paths and symlink escapes.
* All SQL uses `PreparedStatement`; all output uses `<c:out>` (prevents XSS).

## 7. Expected output
* **Login page:** blue gradient background, white card with logo, "College Student Portal", username/password, Login button. Wrong password → red message "Invalid username or password."
* **Students page:** blue navigation bar with "Logged in as admin" and Logout; search box; 5 cards (photo, name, roll number, course, document count, View Profile). Meena's card shows a letter "M" and "No profile image". Rahul's card says "No documents uploaded."
* **Profile page:** large photo on the left; information card (name, roll number, course, department, semester, email, phone); documents list with View / Download buttons. Priya shows a PDF row and an image row with a thumbnail. Rahul shows "No documents uploaded."
* **After logout:** login page with green "logged out" message; pressing Back or opening `/students` sends you to `login.jsp`.

## 8. Common errors and solutions

| Problem | Solution |
|---------|----------|
| HTTP 404 on every URL | Wrong context path. Use the path set in step 3.5 (`/StudentPortal`). |
| `ClassNotFoundException: com.mysql.cj.jdbc.Driver` | MySQL connector jar missing. Use Maven, or copy the jar to `WEB-INF/lib`. |
| `Access denied for user 'root'@'localhost'` | Wrong user/password in `DBConnection.java`. |
| `Communications link failure` | MySQL is not running (start it in XAMPP) or the port is not 3306. |
| `Unknown database 'student_portal'` | Import `database.sql` first. |
| "Cannot connect to the database" on login | Same causes as the three rows above; check the Tomcat console log for the real exception. |
| `javax.servlet` cannot be resolved / `jakarta` errors | You are on Tomcat 10+. Use Tomcat 9. |
| `The absolute uri http://java.sun.com/jsp/jstl/core cannot be resolved` | JSTL jar missing (see 3.3). |
| Invalid username or password (but correct) | `database.sql` was not imported fully. Re-import. Passwords are SHA-256 hashed by MySQL's `SHA2()`. |
| Profile image / document shows 404 or broken icon | The file in `uploads/...` does not match the path in the DB. Names are case-sensitive on Linux. |
| Images missing after republishing in Eclipse | Eclipse copies `src/main/webapp` to its own deploy folder. Do Project → Clean, then republish. |
| Changes not visible | Stop the server, Clean the server (Servers view → right-click → Clean), start again. |
| Port 8080 already in use | Stop the other program, or change the Tomcat port in `conf/server.xml`. |
