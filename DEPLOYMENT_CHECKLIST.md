# Deployment Checklist

## ✅ Pre-Deployment Setup

### 1. **Clean Repository State**
- [ ] Remove any `.installed` flag files from `config/` directory
- [ ] Clear any session files or temporary data
- [ ] Remove any `tmp_rovodev_*` test files
- [ ] Clear error logs if they contain sensitive information

### 2. **Configuration Updates**
- [ ] Update `BASE_URL` in `config/config.php` to match your server
- [ ] Verify database credentials are set to defaults (will be configured during install)
- [ ] Check file permissions for web server access

## 🚀 Deployment Steps

### 1. **Copy Files**
- [ ] Copy entire repository to web server directory
- [ ] Ensure all files and folders are copied completely
- [ ] Verify file permissions (web server needs read/write access)

### 2. **Web Server Setup**
- [ ] Point web server to the application directory
- [ ] Ensure PHP is enabled and configured
- [ ] Verify MySQL/MariaDB is installed and running

### 3. **First Access**
- [ ] Access the application URL in browser
- [ ] Should automatically redirect to first install wizard
- [ ] If you see login page instead, check troubleshooting section

## 🔧 Configuration Requirements

### **PHP Requirements**
- PHP 7.4 or higher
- PDO MySQL extension
- JSON extension
- File upload enabled

### **MySQL Requirements**
- MySQL 5.7+ or MariaDB 10.2+
- User with database creation privileges
- Recommended: dedicated database user

### **File Permissions**
- Web server needs read access to all files
- Web server needs write access to:
  - `config/` directory (for .installed flag)
  - `storage/` directory (for uploads)
  - `backups/` directory (for database backups)

## 🛠 Troubleshooting

### **Issue: Seeing Login Page Instead of Install Wizard**
1. Check if `config/.installed` file exists - delete it if found
2. Verify database doesn't exist or is empty
3. Clear browser cache and cookies
4. Try accessing `/index.php?page=first_install` directly

### **Issue: Database Connection Errors**
1. Verify MySQL service is running
2. Check database credentials
3. Ensure database user has proper privileges
4. Test connection manually

### **Issue: File Permission Errors**
1. Ensure web server can read all application files
2. Verify write permissions on storage directories
3. Check SELinux settings (Linux servers)

### **Issue: Path-Related Errors**
1. All paths use relative references - should work anywhere
2. Backup/restore functions auto-detect MySQL paths
3. If MySQL tools not found, add to system PATH

## 📋 Post-Installation

### **Security Checklist**
- [ ] Change default admin password
- [ ] Review user permissions
- [ ] Configure regular backups
- [ ] Update BASE_URL to production domain
- [ ] Enable HTTPS if available
- [ ] Review file upload restrictions

### **Customization**
- [ ] Configure branding (logo, colors, office name)
- [ ] Set up document types and fields
- [ ] Configure user roles and permissions
- [ ] Import location data if needed

## 🔄 Updates and Maintenance

### **Regular Tasks**
- [ ] Regular database backups
- [ ] Monitor storage space
- [ ] Review user accounts
- [ ] Update system if new versions available

### **Before Updates**
- [ ] Create full backup
- [ ] Test in staging environment
- [ ] Review changelog for breaking changes
- [ ] Plan rollback procedure

---

## 📞 Support

If you encounter issues during deployment:

1. Check the error logs in your web server
2. Review PHP error logs
3. Verify all requirements are met
4. Test with minimal configuration first

**Note**: This system is designed to be portable and should work on any standard LAMP/WAMP/XAMPP setup without modification.