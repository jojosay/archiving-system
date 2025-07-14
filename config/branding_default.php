<?php
/**
 * Default Branding Configuration
 * These are the default values used when no custom branding is configured
 */

// Application Information
define('BRAND_APP_NAME', 'Archiving System');
define('BRAND_APP_DESCRIPTION', 'Secure document archiving and management system');
define('BRAND_APP_TAGLINE', 'Efficient • Secure • Reliable');
define('BRAND_APP_VERSION', '1.0.0');

// Office Information
define('BRAND_OFFICE_NAME', 'Civil Registry Office');
define('BRAND_OFFICE_DEPARTMENT', 'Document Management Department');
define('BRAND_OFFICE_ADDRESS', '123 Government Street, City, State 12345');
define('BRAND_OFFICE_PHONE', '+1 (555) 123-4567');
define('BRAND_OFFICE_EMAIL', 'info@civilregistry.gov');
define('BRAND_OFFICE_WEBSITE', 'https://www.civilregistry.gov');

// Visual Branding
define('BRAND_PRIMARY_COLOR', '#2C3E50');
define('BRAND_SECONDARY_COLOR', '#F39C12');
define('BRAND_ACCENT_COLOR', '#3498DB');
define('BRAND_BACKGROUND_COLOR', '#ECF0F1');
define('BRAND_TEXT_COLOR', '#2C3E50');
define('BRAND_SIDEBAR_COLOR', '#2C3E50');

// Logo Settings
define('BRAND_LOGO_PRIMARY', 'assets/branding/logos/logo.png');
define('BRAND_LOGO_SECONDARY', 'assets/branding/logos/logo-small.png');
define('BRAND_FAVICON', 'assets/branding/favicons/favicon.ico');
define('BRAND_LOGO_WIDTH', '200px');
define('BRAND_LOGO_HEIGHT', 'auto');

// Footer Information
define('BRAND_FOOTER_TEXT', 'Powered by Civil Registry Archiving System');
define('BRAND_COPYRIGHT_TEXT', '© 2025 Civil Registry Office. All rights reserved.');
define('BRAND_FOOTER_LINKS', json_encode([
    'Privacy Policy' => '#',
    'Terms of Service' => '#',
    'Contact Us' => '#'
]));

// Theme Settings
define('BRAND_THEME_NAME', 'default');
define('BRAND_CUSTOM_CSS', '');
define('BRAND_FONT_FAMILY', "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif");

// Deployment Information
define('BRAND_DEPLOYMENT_ID', 'default');
define('BRAND_DEPLOYMENT_DATE', date('Y-m-d'));
define('BRAND_DEPLOYMENT_VERSION', '1.0.0');

// Feature Flags
define('BRAND_SHOW_LOGO', true);
define('BRAND_SHOW_TAGLINE', true);
define('BRAND_SHOW_OFFICE_INFO', true);
define('BRAND_SHOW_FOOTER_LINKS', true);
define('BRAND_ENABLE_CUSTOM_THEME', false);
?>