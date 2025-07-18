# 📚 Civil Registry Archiving System - User Training Guide

## Table of Contents
1. [Getting Started](#getting-started)
2. [Basic Operations](#basic-operations)
3. [Document Management](#document-management)
4. [Search and Retrieval](#search-and-retrieval)
5. [Template System](#template-system)
6. [Administrative Functions](#administrative-functions)
7. [Troubleshooting](#troubleshooting)

---

## Getting Started

### System Requirements
- Modern web browser (Chrome, Firefox, Safari, Edge)
- Internet connection
- Valid user account

### Logging In
1. Navigate to the system URL
2. Enter your username and password
3. Click "Login"
4. You'll be redirected to the Dashboard

### Dashboard Overview
The Dashboard provides:
- **System Statistics**: Document counts, user counts, recent activity
- **Quick Actions**: Fast access to common tasks
- **Navigation Menu**: Access to all system features

---

## Basic Operations

### Navigation
- **Sidebar Menu**: Main navigation on the left
- **Breadcrumbs**: Shows your current location
- **Quick Actions**: Dashboard shortcuts for common tasks

### User Interface Elements
- **Blue Buttons**: Primary actions (Save, Submit)
- **Green Buttons**: Success actions (Confirm, Approve)
- **Red Buttons**: Destructive actions (Delete, Remove)
- **Gray Buttons**: Secondary actions (Cancel, Back)

### Notifications
The system shows notifications for:
- ✅ **Success**: Green notifications for completed actions
- ❌ **Error**: Red notifications for problems
- ⚠️ **Warning**: Yellow notifications for important information
- ℹ️ **Info**: Blue notifications for general information

---

## Document Management

### Uploading Documents

#### Step 1: Access Upload Page
- Click "Upload Document" from Dashboard
- Or navigate to **Documents > Upload Document**

#### Step 2: Select Document Type
- Choose the appropriate document category
- This determines what fields you'll need to fill

#### Step 3: Fill Document Information
- **Title**: Descriptive name for the document
- **Description**: Optional detailed description
- **Metadata Fields**: Varies by document type

#### Step 4: Upload Files
- Click "Choose Files" or drag files to upload area
- Supported formats: PDF, DOC, DOCX, JPG, PNG
- Maximum file size: 50MB per file

#### Step 5: Review and Submit
- Check all information is correct
- Click "Upload Document"
- Wait for confirmation message

### Editing Documents

#### Accessing Document Editor
1. Go to **Documents > Browse Archive**
2. Find the document you want to edit
3. Click the "Edit" button (pencil icon)

#### Making Changes
- Update any field as needed
- Add or remove files
- Modify metadata
- Click "Save Changes" when done

### Document Status
- **Active**: Document is available and searchable
- **Archived**: Document is stored but not actively used
- **Deleted**: Document is marked for removal

---

## Search and Retrieval

### Basic Search
1. Use the search box in the top navigation
2. Enter keywords related to the document
3. Press Enter or click the search icon
4. Results show matching documents

### Advanced Search
1. Navigate to **Search > Advanced Search**
2. Use multiple criteria:
   - **Document Type**: Filter by category
   - **Date Range**: Specify upload or creation dates
   - **Location**: Filter by geographic data
   - **Custom Fields**: Search specific metadata

### Search Tips
- Use quotation marks for exact phrases: `"birth certificate"`
- Use wildcards: `john*` finds "john", "johnson", "johnny"
- Combine terms: `birth certificate manila` finds documents with all terms
- Use filters to narrow results

### Viewing Documents
- Click document title to view details
- Use the PDF viewer for document preview
- Download original files using the download button
- Print documents directly from the viewer

---

## Template System

### Understanding Templates
Templates are pre-designed forms that help standardize document creation and data entry.

### Using Templates

#### For Document Creation
1. Select a template when uploading documents
2. Template fields auto-populate the form
3. Fill in the remaining information
4. Submit as normal

#### Template Builder (Admin Only)
1. Navigate to **Templates > Template Builder**
2. Upload a PDF template
3. Drag and drop fields onto the PDF
4. Configure field properties
5. Save the template

### Template Library
- Browse available templates
- Preview template layouts
- Duplicate existing templates
- Set default templates for document types

---

## Administrative Functions

### User Management (Admin Only)

#### Adding New Users
1. Go to **Administration > Add User**
2. Fill in user information:
   - Username (must be unique)
   - Full name
   - Email address
   - Role (Admin or Staff)
3. Set initial password
4. Click "Create User"

#### Managing Existing Users
1. Navigate to **Administration > Manage Users**
2. View list of all users
3. Edit user details by clicking the edit icon
4. Reset passwords using the reset button
5. Deactivate users instead of deleting

### Document Types Management

#### Creating Document Types
1. Go to **Administration > Document Types**
2. Click "Add New Document Type"
3. Configure:
   - Type name and description
   - Custom fields
   - Validation rules
   - Default templates

#### Custom Fields
Available field types:
- **Text**: Single line text input
- **Textarea**: Multi-line text
- **Date**: Date picker
- **Number**: Numeric input
- **Dropdown**: Predefined options
- **Cascading Dropdown**: Location hierarchies
- **File Upload**: Additional file attachments

### System Backup

#### Creating Backups
1. Navigate to **Administration > Backup Management**
2. Choose backup type:
   - **Database Only**: User data and settings
   - **Files Only**: Uploaded documents
   - **Complete Backup**: Everything
3. Click "Start Backup"
4. Wait for completion notification
5. Download backup file

#### Restoring from Backup
1. Go to **Administration > Backup Management**
2. Upload backup file
3. Select restore options
4. Confirm the restoration
5. System will restart after restoration

---

## Troubleshooting

### Common Issues

#### Login Problems
**Problem**: Can't log in
**Solutions**:
- Check username and password spelling
- Ensure Caps Lock is off
- Contact administrator for password reset
- Clear browser cache and cookies

#### Upload Failures
**Problem**: Document won't upload
**Solutions**:
- Check file size (must be under 50MB)
- Verify file format is supported
- Ensure stable internet connection
- Try uploading one file at a time

#### Search Not Working
**Problem**: Search returns no results
**Solutions**:
- Check spelling of search terms
- Try broader search terms
- Use advanced search with filters
- Ensure documents exist in the system

#### Slow Performance
**Problem**: System is running slowly
**Solutions**:
- Close unnecessary browser tabs
- Clear browser cache
- Check internet connection speed
- Contact administrator if problem persists

### Getting Help

#### Self-Help Resources
1. Check this user guide
2. Look for tooltips and help text in the interface
3. Review error messages carefully
4. Try the action again after a few minutes

#### Contacting Support
If you need additional help:
1. Note the exact error message
2. Record what you were trying to do
3. Include your username and the time the issue occurred
4. Contact your system administrator

### Best Practices

#### Document Organization
- Use consistent naming conventions
- Fill in all required fields completely
- Add descriptive titles and descriptions
- Tag documents with relevant keywords

#### Security
- Log out when finished using the system
- Don't share your login credentials
- Report suspicious activity to administrators
- Keep your password secure and unique

#### Data Quality
- Double-check information before submitting
- Use standard formats for dates and names
- Verify file uploads completed successfully
- Review documents after upload for accuracy

---

## Quick Reference

### Keyboard Shortcuts
- `Ctrl + S`: Save current form
- `Ctrl + F`: Open search
- `Esc`: Close modal dialogs
- `Tab`: Navigate between form fields

### File Format Support
- **Documents**: PDF, DOC, DOCX, TXT
- **Images**: JPG, JPEG, PNG, GIF
- **Maximum Size**: 50MB per file

### User Roles
- **Admin**: Full system access, user management, system configuration
- **Staff**: Document management, search, basic operations

---

*This guide covers the essential functions of the Civil Registry Archiving System. For additional help or advanced features, contact your system administrator.*