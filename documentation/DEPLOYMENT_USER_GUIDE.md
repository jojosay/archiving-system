# 🚀 Civil Registry Archiving System - Deployment User Guide

## 📋 Table of Contents
1. [Overview](#overview)
2. [System Requirements](#system-requirements)
3. [Pre-Deployment Preparation](#pre-deployment-preparation)
4. [Creating Deployment Packages](#creating-deployment-packages)
5. [Installing at Target Location](#installing-at-target-location)
6. [Post-Installation Verification](#post-installation-verification)
7. [Troubleshooting](#troubleshooting)
8. [Support](#support)

---

## 🎯 Overview

The Civil Registry Archiving System includes a comprehensive deployment system that allows you to create customized installation packages for different offices or locations. Each deployment package includes:

- **Complete application files** with all functionality
- **Custom branding** (logos, themes, colors) specific to your office
- **Database schema** and initial configuration
- **Automated installation scripts** for easy setup
- **Office-specific configuration** and admin accounts

### Deployment Scenarios
- **Municipal Government Offices** - City halls, civil registry departments
- **Provincial/State Offices** - Regional government document services
- **Corporate Branches** - Company document management systems
- **Multi-location Organizations** - Consistent system across multiple sites

---

## 💻 System Requirements

### Source System (Package Creation)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 7.4 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Storage**: Minimum 500MB free space for package creation
- **Extensions**: ZIP, JSON, PDO MySQL

### Target System (Installation)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 7.4 or higher with extensions:
  - PDO MySQL
  - JSON
  - ZIP
  - GD (for image processing)
  - cURL (for external integrations)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Storage**: Minimum 200MB free space
- **Permissions**: Web server write access to application directories

---

## 🛠️ Pre-Deployment Preparation

### Step 1: Configure Source System Branding
Before creating deployment packages, ensure your source system has the desired branding configured:

1. **Access Branding Management**
   - Navigate to **Admin → Branding Management**
   - Configure office-specific branding elements

2. **Upload Custom Assets**
   - **Logo**: Upload your organization's logo (PNG/JPG, recommended 200x60px)
   - **Favicon**: Upload favicon (ICO/PNG, 16x16px or 32x32px)
   - **Colors**: Set primary and secondary brand colors
   - **Theme**: Choose or customize the visual theme

3. **Verify Branding**
   - Preview the system with your branding
   - Ensure all elements display correctly
   - Test on different screen sizes

### Step 2: Prepare Target Environment Information
Gather the following information for each deployment location:

- **Office Details**
  - Office name (will be used in package naming)
  - Contact information
  - Location details

- **Technical Details**
  - Server hostname/IP address
  - Database server details
  - Admin credentials for setup

- **Admin Account**
  - Desired admin username
  - Secure admin password
  - Admin email address

---

## 📦 Creating Deployment Packages

### Step 1: Access Deployment Center
1. Log in to your source system as an administrator
2. Navigate to **Admin → Deployment Center**
3. Review the system status dashboard

### Step 2: Use Advanced Package Builder
1. Click **"Advanced Package Builder"** button
2. You'll see the comprehensive package creation interface

### Step 3: Configure Package Details

#### Basic Information
- **Office Name**: Enter the target office name
  - Example: "City Hall Civil Registry"
  - This will be used for package naming and identification
- **Package Version**: Set version number (default: 1.0.0)
  - Use semantic versioning (e.g., 1.0.0, 1.1.0, 2.0.0)

#### Admin Account Setup (Optional but Recommended)
- **Create Admin User**: Check this option to include admin account creation
- **Admin Username**: Default is "admin" (can be customized)
- **Admin Password**: Enter a secure password
- **Admin Email**: Provide admin email address

### Step 4: Generate Package
1. Click **"Create Deployment Package"** button
2. The system will automatically:
   - Export database schema and initial data
   - Bundle all branding assets (logos, themes, CSS)
   - Copy application files
   - Create installation scripts
   - Generate package metadata

### Step 5: Package Creation Results
Upon successful creation, you'll see:
- **Package Name**: Unique identifier for the package
- **Creation Date**: When the package was created
- **Created By**: Your username
- **Deployment ID**: Unique tracking identifier
- **Package Location**: File system path to the package

### Step 6: Download Package
- The package is created in the `deployment/packages/` directory
- Package structure includes:
  ```
  office_deployment_package/
  ├── application/          # Complete application
  ├── database/            # Schema and data files
  ├── installation/        # Setup scripts
  ├── branding/           # Custom branding assets
  └── package_info.json   # Package metadata
  ```

---

## 🏗️ Installing at Target Location

### Step 1: Prepare Target Server
1. **Set up Web Server**
   - Install Apache/Nginx with PHP support
   - Configure virtual host for the application
   - Ensure proper file permissions

2. **Set up Database**
   - Install MySQL/MariaDB
   - Create database user with appropriate privileges
   - Note connection details (host, port, username, password)

### Step 2: Transfer Package
1. **Upload Package**
   - Transfer the entire package directory to your target server
   - Place in web-accessible directory (e.g., `/var/www/html/`)
   - Ensure proper file permissions

### Step 3: Run Installation Script
1. **Access Installation**
   - Navigate to: `http://your-domain.com/package-directory/installation/install.php`
   - Or run from command line: `php installation/install.php`

2. **Installation Process**
   The installation script will automatically:
   - ✅ Check system requirements
   - ✅ Validate PHP version and extensions
   - ✅ Check file permissions
   - ✅ Set up database connection
   - ✅ Import database schema
   - ✅ Import initial data and configuration
   - ✅ Create admin user (if configured)
   - ✅ Copy application files
   - ✅ Set up branding assets
   - ✅ Generate configuration files

### Step 4: Database Configuration
If manual database setup is required:

1. **Create Database**
   ```sql
   CREATE DATABASE civil_registry_archive;
   CREATE USER 'registry_user'@'localhost' IDENTIFIED BY 'secure_password';
   GRANT ALL PRIVILEGES ON civil_registry_archive.* TO 'registry_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Import Schema**
   ```bash
   mysql -u registry_user -p civil_registry_archive < database/schema.sql
   mysql -u registry_user -p civil_registry_archive < database/initial_data.sql
   mysql -u registry_user -p civil_registry_archive < database/office_config.sql
   ```

### Step 5: Configure Application
1. **Update Configuration**
   - Edit `application/config/config.php` if needed
   - Verify database connection settings
   - Set application-specific settings

2. **Set File Permissions**
   ```bash
   chmod 755 application/
   chmod 644 application/config/config.php
   chmod 777 application/uploads/ (if exists)
   chmod 777 application/backups/ (if exists)
   ```

---

## ✅ Post-Installation Verification

### Step 1: Access Application
1. Navigate to your application URL
2. You should see the login page with your custom branding

### Step 2: Test Admin Login
1. Use the admin credentials configured during package creation
2. Default: username "admin" with your specified password
3. Verify you can access all admin functions

### Step 3: Verify Branding
1. **Visual Elements**
   - ✅ Custom logo displays correctly
   - ✅ Favicon appears in browser tab
   - ✅ Color scheme matches your branding
   - ✅ Theme elements are properly applied

2. **Functionality**
   - ✅ All pages load without errors
   - ✅ Navigation works correctly
   - ✅ Forms submit properly
   - ✅ File uploads function (if applicable)

### Step 4: Test Core Features
1. **User Management**
   - Create test user accounts
   - Verify role assignments
   - Test login/logout functionality

2. **Document Management**
   - Upload test documents
   - Verify document types are configured
   - Test search and retrieval

3. **System Functions**
   - Check backup functionality
   - Verify reporting features
   - Test any custom configurations

---

## 🔧 Troubleshooting

### Common Installation Issues

#### Database Connection Errors
**Problem**: "Database connection failed"
**Solutions**:
- Verify database server is running
- Check connection credentials in config file
- Ensure database user has proper privileges
- Confirm database name exists

#### File Permission Errors
**Problem**: "Permission denied" errors
**Solutions**:
```bash
# Set proper ownership
chown -R www-data:www-data /path/to/application/

# Set directory permissions
find /path/to/application/ -type d -exec chmod 755 {} \;

# Set file permissions
find /path/to/application/ -type f -exec chmod 644 {} \;

# Set writable directories
chmod 777 /path/to/application/uploads/
chmod 777 /path/to/application/backups/
```

#### PHP Extension Missing
**Problem**: "Required PHP extension not found"
**Solutions**:
```bash
# Ubuntu/Debian
sudo apt-get install php-mysql php-zip php-gd php-curl php-json

# CentOS/RHEL
sudo yum install php-mysql php-zip php-gd php-curl php-json

# Restart web server
sudo systemctl restart apache2  # or nginx
```

#### Branding Assets Not Loading
**Problem**: Custom logos or themes not displaying
**Solutions**:
- Check file permissions on assets directory
- Verify branding files were copied correctly
- Clear browser cache
- Check web server error logs

### Installation Script Issues

#### Script Timeout
**Problem**: Installation script times out
**Solutions**:
- Increase PHP max_execution_time
- Run installation from command line
- Break installation into smaller steps

#### Memory Limit Exceeded
**Problem**: "Fatal error: Allowed memory size exhausted"
**Solutions**:
- Increase PHP memory_limit in php.ini
- Optimize database import process
- Use command-line installation

### Post-Installation Issues

#### Login Problems
**Problem**: Cannot log in with admin credentials
**Solutions**:
- Verify admin user was created in database
- Check password encryption method
- Reset admin password manually in database
- Check session configuration

#### Missing Features
**Problem**: Some features not working
**Solutions**:
- Verify all database tables were created
- Check for missing configuration files
- Review error logs for specific issues
- Ensure all required files were copied

---

## 📞 Support

### Self-Help Resources
1. **Check Error Logs**
   - Web server error log (usually `/var/log/apache2/error.log`)
   - PHP error log
   - Application-specific logs

2. **Verify Configuration**
   - Database connection settings
   - File permissions
   - PHP configuration

3. **Test Components**
   - Database connectivity
   - File upload functionality
   - Email configuration (if used)

### Getting Help
1. **Documentation Review**
   - Re-read relevant sections of this guide
   - Check system requirements
   - Verify all steps were followed

2. **System Information**
   When seeking help, provide:
   - Operating system and version
   - Web server type and version
   - PHP version and extensions
   - Database type and version
   - Error messages (exact text)
   - Steps that led to the issue

3. **Contact Information**
   - Technical support team
   - System administrator
   - Development team (if applicable)

---

## 📝 Best Practices

### Security Considerations
1. **Strong Passwords**
   - Use complex admin passwords
   - Change default credentials immediately
   - Implement password policies

2. **File Permissions**
   - Set minimal required permissions
   - Protect configuration files
   - Secure upload directories

3. **Database Security**
   - Use dedicated database users
   - Limit database privileges
   - Regular security updates

### Maintenance
1. **Regular Backups**
   - Schedule automated backups
   - Test backup restoration
   - Store backups securely

2. **Updates**
   - Keep system components updated
   - Monitor for security patches
   - Test updates in staging environment

3. **Monitoring**
   - Monitor system performance
   - Check error logs regularly
   - Monitor disk space usage

---

## 🎉 Conclusion

The Civil Registry Archiving System deployment process is designed to be straightforward and reliable. By following this guide, you should be able to successfully deploy customized installations across multiple locations while maintaining consistent functionality and office-specific branding.

For additional assistance or advanced configuration options, consult the technical documentation or contact your system administrator.

---

*Last Updated: [Current Date]*
*Version: 1.0.0*