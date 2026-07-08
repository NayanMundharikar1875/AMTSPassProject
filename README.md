# AMTSPassProject
## How to Run the Project

### Prerequisites

- PHP 7.4 or higher
- MySQL
- phpMyAdmin
- XAMPP, WAMP, or Laragon

### Step 1: Clone the Repository

```bash
git clone https://github.com/your-username/amts-online-bus-pass.git
```

or download the ZIP file and extract it.

### Step 2: Copy the Project

Move the project folder to your web server directory.

For example:

- XAMPP → `C:\xampp\htdocs\`
- WAMP → `C:\wamp64\www\`
- Laragon → `C:\laragon\www\`

### Step 3: Start Apache and MySQL

Open your local server application (XAMPP/WAMP/Laragon) and start:

- Apache
- MySQL

### Step 4: Create the Database

1. Open your browser.
2. Go to:

```
http://localhost/phpmyadmin
```

3. Click **New**.
4. Create a database.

Example:

```
amts
```

### Step 5: Import the Database

1. Select the database.
2. Click **Import**.
3. Choose the SQL file included in the project.
4. Click **Go** to import.

### Step 6: Configure the Database Connection

Open the database configuration file.

Example:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "amts";
```

Update these values if your MySQL configuration is different.

### Step 7: Run the Project

Open your browser and visit:

```
http://localhost/AMTS/
```

### Admin Panel

To access the admin panel:

```
http://localhost/AMTS/admin
```

Log in using the administrator credentials stored in the database.
