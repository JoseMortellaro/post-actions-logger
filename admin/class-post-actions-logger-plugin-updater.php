<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

// Add this to your plugin's main file

class Post_Actions_Logger_GitHub_Plugin_Updater {

    private $plugin_slug;
    private $github_repo;
    private $plugin_name;
    private $author_name;
    private $current_version;
    private $plugin_file_name;

    public function __construct($plugin_slug, $current_version, $plugin_file_name, $github_repo, $plugin_name, $author_name) {
        $this->plugin_slug = $plugin_slug;
        $this->github_repo = $github_repo;
        $this->plugin_name = $plugin_name;
        $this->author_name = $author_name;
        $this->plugin_file_name = $plugin_file_name;
        $this->current_version = $current_version;

        // Hook into the update process
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_plugin_update_from_github']);
        add_filter('plugins_api', [$this, 'github_plugin_api_call'], 10, 3);
    }

    // Check if there's a new version on GitHub
    public function check_plugin_update_from_github($transient) {


        
        // GitHub API URL for the latest release
        $github_api_url = 'https://api.github.com/repos/' . $this->github_repo . '/releases/latest';

        // Get the latest release info from GitHub
        $response = wp_remote_get($github_api_url);
        if (is_wp_error($response)) {
            return $transient; // If we cannot fetch data from GitHub, return the original transient
        }

        $release_data = json_decode(wp_remote_retrieve_body($response));

        if (!isset($release_data->tag_name)) {
            return $transient; // If no release data, return the original transient
        }

        // Check if the version on GitHub is newer than the installed one
        $new_version = $release_data->tag_name;
        $current_version = $this->current_version;
        if (version_compare($current_version, $new_version, '<')) {
            $plugin_data = (object) [
                'slug' => $this->plugin_slug,
                'new_version' => $new_version,
                'url' => $release_data->html_url,
                'plugin' => $this->plugin_file_name,
                'package' => $release_data->zipball_url, // GitHub zipball URL for plugin package
            ];

            // Add update data to the transient
            $transient->response[$this->plugin_file_name] = $plugin_data;
        }
        return $transient;
    }

    // Fetch plugin info from GitHub when WordPress requests it
    public function github_plugin_api_call($false, $action, $args) {
        // We only want to process the 'plugin_information' API call
        if ('plugin_information' !== $action) {
            return $false;
        }

        // GitHub API URL for the latest release
        $github_api_url = 'https://api.github.com/repos/' . $this->github_repo . '/releases/latest';

        $response = wp_remote_get($github_api_url);

        if (is_wp_error($response)) {
            return false;  // Return false if we cannot fetch data from GitHub
        }

        $release_data = json_decode(wp_remote_retrieve_body($response));

        if (!isset($release_data->tag_name)) {
            return false;  // No release data, so return false
        }

        // Set the plugin information that will be displayed in the WordPress update page
        $plugin_info = (object) [
            'name' => $this->plugin_name,
            'slug' => $this->plugin_slug,
            'version' => $release_data->tag_name,
            'author' => $this->author_name,
            'homepage' => $release_data->html_url,
            'download_link' => $release_data->zipball_url,  // GitHub zipball URL for plugin package
        ];

        return $plugin_info;
    }
}