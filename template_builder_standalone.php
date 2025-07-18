<?php
// Redirect to the correct path
$edit_template = $_GET['edit_template'] ?? '';
if ($edit_template) {
    header("Location: pages/template_builder_standalone.php?edit_template=" . urlencode($edit_template));
} else {
    header("Location: pages/template_builder_standalone.php");
}
exit;
?>