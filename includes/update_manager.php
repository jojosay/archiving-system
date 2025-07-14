<?php
// Update Manager Class for GitHub-based updates
require_once 'config/version.php';

class UpdateManager {
    private $github_api_base;
    private $repo_owner;
    private $repo_name;
    private $cache_file;
    
    public function __construct() {
        $this->github_api_base = GITHUB_API_BASE;
        $this->repo_owner = GITHUB_REPO_OWNER;
        $this->repo_name = GITHUB_REPO_NAME;
        $this->cache_file = UPDATE_CACHE_FILE;
        
        // Create cache directory if it doesn't exist
        $cache_dir = dirname($this->cache_file);
        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
    }
    
    // Check for available updates
    public function checkForUpdates($force = false) {
        try {
            // Check cache first (unless forced)
            if (!$force && $this->isCacheValid()) {
                return $this->getCachedUpdateInfo();
            }
            
            // Fetch latest release from GitHub
            $latest_release = $this->fetchLatestRelease();
            
            if (!$latest_release) {
                return ['success' => false, 'message' => 'Unable to fetch release information'];
            }
            
            $current_version = defined('APP_VERSION') ? APP_VERSION : '1.0.0';
            $latest_version = ltrim($latest_release['tag_name'], 'v');
            
            $update_info = [
                'success' => true,
                'current_version' => $current_version,
                'latest_version' => $latest_version,
                'has_update' => compareVersions($latest_version, $current_version) > 0,
                'release_info' => $latest_release,
                'checked_at' => time()
            ];
            
            // Cache the result
            $this->cacheUpdateInfo($update_info);
            
            return $update_info;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error checking for updates: ' . $e->getMessage()
            ];
        }
    }
    
    // Fetch latest release from GitHub API
    private function fetchLatestRelease() {
        $url = "{$this->github_api_base}/repos/{$this->repo_owner}/{$this->repo_name}/releases/latest";
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: ' . (defined('APP_NAME') ? APP_NAME : 'Archiving System') . '/' . (defined('APP_VERSION') ? APP_VERSION : '1.0.0'),
                    'Accept: application/vnd.github.v3+json'
                ],
                'timeout' => 30
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception('Failed to fetch release information from GitHub');
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from GitHub API');
        }
        
        return $data;
    }
    
    // Check if cached update info is still valid
    private function isCacheValid() {
        if (!file_exists($this->cache_file)) {
            return false;
        }
        
        $cache_data = json_decode(file_get_contents($this->cache_file), true);
        
        if (!$cache_data || !isset($cache_data['checked_at'])) {
            return false;
        }
        
        return (time() - $cache_data['checked_at']) < UPDATE_CHECK_INTERVAL;
    }
    
    // Get cached update information
    private function getCachedUpdateInfo() {
        if (!file_exists($this->cache_file)) {
            return null;
        }
        
        return json_decode(file_get_contents($this->cache_file), true);
    }
    
    // Cache update information
    private function cacheUpdateInfo($info) {
        file_put_contents($this->cache_file, json_encode($info, JSON_PRETTY_PRINT));
    }
    
    // Get download URL for the latest release
    public function getDownloadUrl($asset_name = null) {
        $update_info = $this->checkForUpdates();
        
        if (!$update_info['success'] || !$update_info['has_update']) {
            return null;
        }
        
        $assets = $update_info['release_info']['assets'] ?? [];
        
        // If no specific asset name provided, get the first .zip file
        if (!$asset_name) {
            foreach ($assets as $asset) {
                if (str_ends_with(strtolower($asset['name']), '.zip')) {
                    return $asset['browser_download_url'];
                }
            }
        } else {
            // Look for specific asset
            foreach ($assets as $asset) {
                if ($asset['name'] === $asset_name) {
                    return $asset['browser_download_url'];
                }
            }
        }
        
        return null;
    }
}
?>