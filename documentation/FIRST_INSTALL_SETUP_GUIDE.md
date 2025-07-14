# 🚀 First Install Setup Guide

## Overview

The Civil Registry Archiving System includes an automated first-time installation wizard that guides you through the complete setup process. This feature ensures a smooth and error-free installation experience.

## 🎯 Features

### **Automatic Detection**
- Detects if this is a first-time installation
- Automatically redirects to setup wizard
- Prevents access to system until setup is complete

### **4-Step Installation Process**
1. **Database Configuration** - Test and configure database connection
2. **System Requirements Check** - Verify server compatibility
3. **Admin Account Setup** - Create administrator account
4. **Complete Installation** - Finalize setup and create database

### **Smart Validation**
- Real-time database connection testing
- Comprehensive system requirements checking
- Secure password handling
- Automatic configuration file updates

## 📋 Prerequisites

Before starting the installation, ensure you have:

### **Server Requirements**
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 7.4 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+

### **PHP Extensions Required**
- PDO MySQL
- JSON
- ZIP
- GD (for image processing)
- cURL (for external integrations)

### **File Permissions**
- Write access to `config/` directory
- Write access to `assets/` directory
- Web server read access to all application files

### **Database Access**
- MySQL/MariaDB server running
- Database user with CREATE DATABASE privileges
- Connection details (host, username, password)

## 🛠️ Installation Steps

### **Step 1: Database Configuration**

1. **Access the System**
   - Navigate to your application URL
   - You'll be automatically redirected to the setup wizard

2. **Enter Database Details**
   - **Database Host**: Usually `localhost` for local installations
   - **Database Name**: Choose a name (e.g., `civil_registry_db`)
   - **Database Username**: Your MySQL username
   - **Database Password**: Your MySQL password

3. **Test Connection**
   - Click "Test Database Connection"
   - System will verify the connection
   - Proceed to next step if successful

### **Step 2: System Requirements Check**

The system automatically checks:

- ✅ **PHP Version**: Ensures PHP 7.4 or higher
- ✅ **Extensions**: Verifies all required PHP extensions
- ✅ **Permissions**: Checks write access to required directories

**If Requirements Fail:**
- Install missing PHP extensions
- Fix file permissions
- Upgrade PHP version if needed
- Click "Recheck Requirements" after fixes

### **Step 3: Admin Account Setup**

1. **Create Administrator**
   - **Username**: Choose admin username (default: `admin`)
   - **Password**: Enter a secure password
   - **Email**: Provide admin email address

2. **Security Notes**
   - Use a strong password (8+ characters)
   - Include numbers, letters, and symbols
   - Store credentials securely

### **Step 4: Complete Installation**

1. **Review Summary**
   - Database connection verified
   - System requirements met
   - Admin account configured

2. **Finalize Setup**
   - Click "Complete Installation"
   - System will:
     - Create the database
     - Import database schema
     - Create admin user
     - Update configuration files
     - Mark installation as complete

3. **Success**
   - Installation complete message
   - Redirect to login page
   - Ready to use the system

## 🔧 Technical Details

### **Installation Detection**

The system detects first install by checking:
1. Existence of `.installed` flag file in `config/` directory
2. Database connectivity
3. Presence of users table and data

### **Files Created/Modified**

During installation:
- **`config/.installed`** - Installation flag file
- **`config/config.php`** - Updated with database settings
- **Database tables** - Complete schema imported
- **Admin user** - Created in users table

### **Security Measures**

- Passwords are hashed using PHP's `password_hash()`
- Database credentials are validated before storage
- Installation wizard is disabled after completion
- Session data is cleared after installation

## 🚨 Troubleshooting

### **Common Issues**

#### **Database Connection Failed**
```
Error: Database connection failed: Access denied for user
```
**Solutions:**
- Verify database credentials
- Ensure MySQL server is running
- Check user privileges
- Confirm database host is correct

#### **Permission Denied**
```
Error: Write Permission (config/) - Fail
```
**Solutions:**
```bash
# Set proper permissions
chmod 755 config/
chmod 755 assets/
chown -R www-data:www-data /path/to/application/
```

#### **Missing PHP Extensions**
```
Error: PDO MySQL Extension - Fail
```
**Solutions:**

**Linux (Ubuntu/Debian):**
```bash
sudo apt-get install php-mysql php-zip php-gd php-curl php-json
sudo systemctl restart apache2
```

**Linux (CentOS/RHEL):**
```bash
sudo yum install php-mysql php-zip php-gd php-curl php-json
sudo systemctl restart httpd
```

**Windows (XAMPP):**
1. Open `php.ini` file (usually in `C:\xampp\php\php.ini`)
2. Uncomment these lines by removing the semicolon (;):
   ```ini
   extension=pdo_mysql
   extension=zip
   extension=gd
   extension=curl
   extension=json
   ```
3. Save the file and restart Apache from XAMPP Control Panel

**Windows (WAMP):**
1. Left-click WAMP icon → PHP → PHP Extensions
2. Enable the following extensions:
   - pdo_mysql
   - zip
   - gd2
   - curl
   - json
3. Restart all services from WAMP menu

**Windows (Manual PHP Installation):**
1. Download PHP extensions from https://windows.php.net/downloads/
2. Copy extension files to PHP extensions directory
3. Edit `php.ini` to enable extensions:
   ```ini
   extension_dir = "C:\php\ext"
   extension=pdo_mysql
   extension=zip
   extension=gd
   extension=curl
   extension=json
   ```
4. Restart web server (Apache/IIS)

#### **Schema Import Failed**
```
Error: Schema import failed: Table already exists
```
**Solutions:**
- Drop existing database and recreate
- Ensure database is empty before installation
- Check database user has CREATE/DROP privileges

### **Manual Recovery**

If installation fails:

1. **Reset Installation**
   ```bash
   # Remove installation flag
   rm config/.installed
   
   # Clear session data
   # Restart web browser or clear cookies
   ```

2. **Database Cleanup**
   ```sql
   DROP DATABASE IF EXISTS civil_registry_db;
   CREATE DATABASE civil_registry_db;
   ```

3. **Restart Installation**
   - Navigate to application URL
   - Installation wizard will restart

## 📁 File Structure

After successful installation:

```
config/
├── .installed              # Installation flag file
├── config.php              # Updated with database settings
└── branding.php            # Branding configuration

database/
└── schema.sql              # Database schema (imported)

includes/
├── first_install_manager.php    # Installation detection
└── first_install_database.php   # Database setup

pages/
└── first_install.php            # Installation wizard UI
```

## 🎉 Post-Installation

### **First Login**
1. Navigate to the login page
2. Use the admin credentials you created
3. Access the dashboard
4. Configure additional settings as needed

### **Next Steps**
- **Branding**: Customize logos, colors, and themes
- **Users**: Create additional user accounts
- **Document Types**: Configure document categories
- **Locations**: Set up office locations
- **Backup**: Configure automated backups

### **Security Recommendations**
- Change default admin password regularly
- Create additional admin users
- Configure proper file permissions
- Enable HTTPS for production use
- Regular security updates

## 📞 Support

If you encounter issues during installation:

1. **Check Requirements**: Ensure all prerequisites are met
2. **Review Logs**: Check web server and PHP error logs
3. **Verify Permissions**: Confirm file and directory permissions
4. **Database Access**: Test database connectivity manually
5. **Clean Install**: Remove `.installed` file and restart

The First Install feature ensures a professional, user-friendly setup experience that gets your Civil Registry Archiving System up and running quickly and reliably.

---

*This guide covers the automated first-time installation process. For advanced configuration or deployment scenarios, refer to the Deployment User Guide.*