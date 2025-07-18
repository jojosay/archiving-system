# New Template Feature Plan - Visual Template Builder

## 🎯 Overview
Create an intuitive visual template builder where users can:
1. Search and select records from the database
2. Drag and drop metadata fields onto a PDF template
3. Type directly in the template
4. Generate filled documents instantly

## 🔄 New Workflow

### Step 1: Template Builder Page
- **Page**: `template_builder.php`
- **Layout**: Main content area with collapsible right sidebar
  - **Main Area**: PDF template viewer/editor (full width when sidebar closed)
  - **Right Sidebar**: Record search and metadata fields (collapsible/toggleable)
  - **Existing Left Sidebar**: Navigation menu (unchanged)

### Step 2: Record Selection
- Search interface to find existing records
- Filter by document type, date range, etc.
- Select a record to populate metadata fields

### Step 3: Visual Template Editing
- PDF viewer with overlay for field placement
- Drag fields from sidebar to specific positions on PDF
- Click to add text directly on the template
- Visual indicators for field positions

### Step 4: Template Generation
- Real-time preview with actual data
- Generate final PDF with merged data
- Save template layout for reuse

## 🏗️ Technical Architecture

### Core Components

#### 1. Template Builder Interface (`pages/template_builder.php`)

**Layout Option A: Collapsible Right Sidebar**
```
┌─────────────────────────────────────────────────────────────────────────────┐
│ Nav │                    Template Builder                          │ [≡] │
│ Bar │                                                              │ R   │
├─────┼──────────────────────────────────────────────────────────────┤ i   │
│ 🏠  │                                                              │ g   │
│ 📄  │            PDF Viewer/Editor (Main Area)                    │ h   │
│ 📊  │                                                              │ t   │
│ ⚙️  │  ┌─────────────────────────────────────────────────────────┐ │     │
│     │  │                                                         │ │ S   │
│     │  │         PDF Template                                    │ │ i   │
│     │  │                                                         │ │ d   │
│     │  │  [Field Position Markers]                               │ │ e   │
│     │  │                                                         │ │ b   │
│     │  │  ← Drag fields from right sidebar                      │ │ a   │
│     │  │                                                         │ │ r   │
│     │  └─────────────────────────────────────────────────────────┘ │     │
│     │                                                              │ 📋  │
│     │  📝 Text Tool | 🔧 Field Tool | 💾 Save | 👁️ Preview        │ 🔍  │
└─────┴──────────────────────────────────────────────────────────────┴─────┘
```

**Layout Option B: Full-Width with Toggle**
```
┌─────────────────────────────────────────────────────────────────────────────┐
│ Nav │                    Template Builder                    [🔍 Fields] │
│ Bar │                                                                     │
├─────┼─────────────────────────────────────────────────────────────────────┤
│ 🏠  │                                                                     │
│ 📄  │                PDF Viewer/Editor (Full Width)                      │
│ 📊  │                                                                     │
│ ⚙️  │  ┌─────────────────────────────────────────────────────────────┐   │
│     │  │                                                             │   │
│     │  │         PDF Template                                        │   │
│     │  │                                                             │   │
│     │  │  [Field Position Markers]                                   │   │
│     │  │                                                             │   │
│     │  │  Click [🔍 Fields] to open search & fields panel →        │   │
│     │  │                                                             │   │
│     │  └─────────────────────────────────────────────────────────────┘   │
│     │                                                                     │
│     │  📝 Text Tool | 🔧 Field Tool | 💾 Save | 👁️ Preview              │
└─────┴─────────────────────────────────────────────────────────────────────┘
```

**When Fields Panel is Open (Overlay/Slide-in):**
```
┌─────────────────────────────────────────────────────────────────────────────┐
│ Nav │                    Template Builder                          │ [✕] │
│ Bar │                                                              │ ┌───┤
├─────┼──────────────────────────────────────────────────────────────┤ │ 📋│
│ 🏠  │                                                              │ │ S │
│ 📄  │            PDF Viewer/Editor                                 │ │ e │
│ 📊  │                                                              │ │ a │
│ ⚙️  │  ┌─────────────────────────────────────────────────────────┐ │ │ r │
│     │  │                                                         │ │ │ c │
│     │  │         PDF Template                                    │ │ │ h │
│     │  │                                                         │ │ └───┤
│     │  │  [Field Position Markers]                               │ │ 📊 F│
│     │  │                                                         │ │ i e│
│     │  │  ← Drag fields from panel                              │ │ e l│
│     │  │                                                         │ │ l d│
│     │  └─────────────────────────────────────────────────────────┘ │ d s│
│     │                                                              │ s   │
│     │  📝 Text Tool | 🔧 Field Tool | 💾 Save                     │     │
└─────┴──────────────────────────────────────────────────────────────┴─────┘
```

#### 2. PDF Viewer with Overlay (`assets/js/pdf-template-editor.js`)
- PDF.js for rendering PDF files
- Canvas overlay for interactive elements
- Drag and drop functionality
- Field positioning system

#### 3. Template Engine (`includes/visual_template_manager.php`)
- Store template layouts in database
- Field position coordinates
- Template reuse system
- PDF generation with positioned fields

#### 4. Record Search API (`api/record_search.php`)
- Search existing documents
- Filter by various criteria
- Return metadata for selected records

## 📊 Database Schema

### New Tables

#### `visual_templates`
```sql
CREATE TABLE visual_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    pdf_file_path VARCHAR(500) NOT NULL,
    document_type_id INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

#### `template_field_positions`
```sql
CREATE TABLE template_field_positions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_label VARCHAR(255) NOT NULL,
    x_position DECIMAL(10,2) NOT NULL,
    y_position DECIMAL(10,2) NOT NULL,
    width DECIMAL(10,2) DEFAULT 100,
    height DECIMAL(10,2) DEFAULT 20,
    page_number INT DEFAULT 1,
    font_size INT DEFAULT 12,
    font_family VARCHAR(50) DEFAULT 'Arial',
    text_color VARCHAR(7) DEFAULT '#000000',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES visual_templates(id) ON DELETE CASCADE
);
```

#### `template_text_elements`
```sql
CREATE TABLE template_text_elements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    text_content TEXT NOT NULL,
    x_position DECIMAL(10,2) NOT NULL,
    y_position DECIMAL(10,2) NOT NULL,
    width DECIMAL(10,2) DEFAULT 200,
    height DECIMAL(10,2) DEFAULT 20,
    page_number INT DEFAULT 1,
    font_size INT DEFAULT 12,
    font_family VARCHAR(50) DEFAULT 'Arial',
    text_color VARCHAR(7) DEFAULT '#000000',
    is_bold BOOLEAN DEFAULT FALSE,
    is_italic BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES visual_templates(id) ON DELETE CASCADE
);
```

## 🎨 User Interface Features

### Left Sidebar Components

#### 1. Record Search
```html
<div class="record-search">
    <input type="text" placeholder="Search records..." />
    <div class="filters">
        <select name="document_type">Document Type</select>
        <input type="date" name="date_from" placeholder="From Date">
        <input type="date" name="date_to" placeholder="To Date">
    </div>
    <div class="search-results">
        <!-- Dynamic search results -->
    </div>
</div>
```

#### 2. Metadata Fields Palette
```html
<div class="metadata-fields">
    <h4>Available Fields</h4>
    <div class="field-list">
        <div class="field-item" draggable="true" data-field="client_name">
            📝 Client Name
        </div>
        <div class="field-item" draggable="true" data-field="case_number">
            🔢 Case Number
        </div>
        <!-- More fields... -->
    </div>
</div>
```

#### 3. Tools Panel
```html
<div class="tools-panel">
    <button class="tool-btn" data-tool="text">📝 Add Text</button>
    <button class="tool-btn" data-tool="field">🏷️ Add Field</button>
    <button class="tool-btn" data-tool="delete">🗑️ Delete</button>
    <button class="tool-btn" data-tool="save">💾 Save Template</button>
</div>
```

### Right Panel - PDF Editor

#### 1. PDF Viewer with Interactive Overlay
- PDF.js for rendering
- Canvas overlay for interactions
- Zoom controls
- Page navigation

#### 2. Field Positioning System
- Visual field markers
- Resize handles
- Alignment guides
- Snap-to-grid functionality

## 🔧 Implementation Steps

### Phase 1: Core Infrastructure
1. Create database tables
2. Build basic page layout
3. Implement PDF viewer with PDF.js
4. Create drag and drop framework

### Phase 2: Record Search
1. Build search API
2. Implement search interface
3. Add filtering capabilities
4. Create record selection system

### Phase 3: Visual Editor
1. Implement drag and drop from sidebar to PDF
2. Add text editing capabilities
3. Create field positioning system
4. Build property panels for styling

### Phase 4: Template Management
1. Save/load template layouts
2. Template reuse system
3. Template sharing capabilities
4. Version control for templates

### Phase 5: PDF Generation
1. Integrate PDF generation library (TCPDF/FPDF)
2. Position fields accurately on PDF
3. Handle multi-page documents
4. Export functionality

## 🚀 Key Features

### 1. Intuitive Interface
- Visual drag and drop
- Real-time preview
- WYSIWYG editing
- Responsive design

### 2. Flexible Field Placement
- Precise positioning
- Multiple field types
- Custom styling options
- Multi-page support

### 3. Template Reusability
- Save template layouts
- Share between users
- Template categories
- Version management

### 4. Advanced Search
- Full-text search
- Multiple filters
- Recent records
- Favorites system

### 5. Professional Output
- High-quality PDF generation
- Accurate positioning
- Font and styling preservation
- Print-ready documents

## 📱 Responsive Design
- Mobile-friendly interface
- Touch-friendly controls
- Adaptive layout
- Gesture support

## 🔒 Security Features
- User permissions
- Template access control
- Audit logging
- Data validation

This new approach will be much more user-friendly and powerful than the current placeholder-based system!