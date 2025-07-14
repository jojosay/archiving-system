<?php
// Check if user is admin
if (!$auth->hasRole('admin')) {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once 'includes/layout.php';
require_once 'includes/csv_importer.php';
require_once 'includes/csv_importer_simple.php';

$message = '';
$message_type = '';

// Handle CSV upload and import
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    // Use simple importer for large files
    $importer = new SimpleCSVImporter($database);
    
    if ($action === 'upload_regions' && isset($_FILES['regions_csv'])) {
        $upload_path = $importer->getUploadDir() . 'regions.csv';
        if (move_uploaded_file($_FILES['regions_csv']['tmp_name'], $upload_path)) {
            $result = $importer->importRegions($upload_path);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } else {
            $message = 'Failed to upload regions CSV file';
            $message_type = 'error';
        }
    }
    
    if ($action === 'upload_provinces' && isset($_FILES['provinces_csv'])) {
        $upload_path = $importer->getUploadDir() . 'provinces.csv';
        if (move_uploaded_file($_FILES['provinces_csv']['tmp_name'], $upload_path)) {
            $result = $importer->importProvinces($upload_path);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } else {
            $message = 'Failed to upload provinces CSV file';
            $message_type = 'error';
        }
    }
    
    if ($action === 'upload_citymun' && isset($_FILES['citymun_csv'])) {
        $upload_path = $importer->getUploadDir() . 'citymun.csv';
        if (move_uploaded_file($_FILES['citymun_csv']['tmp_name'], $upload_path)) {
            $result = $importer->importCityMun($upload_path);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } else {
            $message = 'Failed to upload cities/municipalities CSV file';
            $message_type = 'error';
        }
    }
    
    if ($action === 'upload_barangays' && isset($_FILES['barangays_csv'])) {
        $upload_path = $importer->getUploadDir() . 'barangays.csv';
        if (move_uploaded_file($_FILES['barangays_csv']['tmp_name'], $upload_path)) {
            $result = $importer->importBarangays($upload_path);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } else {
            $message = 'Failed to upload barangays CSV file';
            $message_type = 'error';
        }
    }
}

renderPageStart('Location Management', 'location_management');
?>

<div class="page-header">
    <h1>Location Data Management</h1>
    <p>Upload and manage location hierarchy CSV files</p>
</div>

<style>
    .upload-card {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .upload-section {
        margin-bottom: 2rem;
        padding: 1.5rem;
        border: 2px dashed #BDC3C7;
        border-radius: 8px;
        text-align: center;
    }
    
    .upload-section h3 {
        color: #2C3E50;
        margin-bottom: 1rem;
    }
    
    .file-input {
        margin: 1rem 0;
    }
    
    .upload-btn {
        background: #27AE60;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
    }
    
    .upload-btn:hover {
        background: #229954;
    }
    
    .message {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    
    .message.success {
        background: #D5EDDA;
        color: #155724;
        border: 1px solid #C3E6CB;
    }
    
    .message.error {
        background: #F8D7DA;
        color: #721C24;
        border: 1px solid #F5C6CB;
    }
</style>

<?php if ($message): ?>
    <div class="message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="upload-card">
    <h2>Upload Location CSV Files</h2>
    <p>Upload your location hierarchy CSV files in the correct order: Regions → Provinces → Cities/Municipalities → Barangays</p>
    
    <!-- Regions Upload -->
    <div class="upload-section">
        <h3>1. Regions CSV</h3>
        <p>Format: id, region_name, region_code</p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_regions">
            <div class="file-input">
                <input type="file" name="regions_csv" accept=".csv" required>
            </div>
            <button type="submit" class="upload-btn">Upload Regions</button>
        </form>
    </div>
    
    <!-- Provinces Upload -->
    <div class="upload-section">
        <h3>2. Provinces CSV</h3>
        <p>Format: id, ProvinceName, region_code</p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_provinces">
            <div class="file-input">
                <input type="file" name="provinces_csv" accept=".csv" required>
            </div>
            <button type="submit" class="upload-btn">Upload Provinces</button>
        </form>
    </div>
    
    <!-- Cities/Municipalities Upload -->
    <div class="upload-section">
        <h3>3. Cities/Municipalities CSV</h3>
        <p>Format: id, CityMunName, ProvinceID</p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_citymun">
            <div class="file-input">
                <input type="file" name="citymun_csv" accept=".csv" required>
            </div>
            <button type="submit" class="upload-btn">Upload Cities/Municipalities</button>
        </form>
    </div>
    
    <!-- Barangays Upload -->
    <div class="upload-section">
        <h3>4. Barangays CSV</h3>
        <p>Format: id, BarangayName, CityMunID</p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_barangays">
            <div class="file-input">
                <input type="file" name="barangays_csv" accept=".csv" required>
            </div>
            <button type="submit" class="upload-btn">Upload Barangays</button>
        </form>
    </div>
</div>

<?php renderPageEnd(); ?>