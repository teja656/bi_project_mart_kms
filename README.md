 teja.k
 This provides the ui of the main page
![Main Page](images/image1.png)

This is for the dashboard page
![dashboard page](images/image2.png)


    🚀 Deployment Instructions (XAMPP)
✅ Prerequisites
XAMPP installed on your system.

The Apache and MySQL services running from the XAMPP Control Panel.

📥 1. Clone or Download the Project
If you’re using Git:

bash
Copy
Edit
git clone https://github.com/teja656/bi_project_mart_kms.git
Or manually:

Go to: https://github.com/teja656/bi_project_mart_kms

Click Code > Download ZIP

Extract it.

Then move the folder to:

makefile
Copy
Edit
C:\xampp\htdocs\
So your path becomes:

makefile
Copy
Edit
C:\xampp\htdocs\bi_project_mart_kms\
🛢️ 2. Set Up the Database
Start Apache and MySQL in the XAMPP Control Panel.

Open your browser and go to:

arduino
Copy
Edit
http://localhost/phpmyadmin
Click on New, and create a database (e.g., mart_kms).

With your new database selected, click Import.

Upload and import the database.sql file from your project folder.

⚙️ 3. Configure (if needed)
If there’s a config.php or database connection file (e.g., inside includes/ or db_connect.php), make sure it has the following:

php
Copy
Edit
$servername = "localhost";
$username = "root";
$password = "";
$database = "mart_kms";
(XAMPP uses root with no password by default.)

🌐 4. Run the Application
Open your browser and go to:

arduino
Copy
Edit
http://localhost/bi_project_mart_kms/
You should see the login page (login.php). Use credentials from the users table in the database (or manually add one via phpMyAdmin if needed).
