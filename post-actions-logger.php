<?php
/*
Plugin Name: POST Action Logger
Plugin URI: https://josemortellaro.com
Description: Logs all $_POST actions and displays them on a dedicated backend page.
Version: 1.0.1
Author: Jose Mortellaro
Author URI: https://example.com
License: GPL2
*/

defined('ABSPATH') || exit; // Exit if accessed directly.
define( 'POST_ACTIONS_LOGGER_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
define( 'POST_ACTIONS_LOGGER_PLUGIN_FILENAME', untrailingslashit( plugin_basename( __FILE__ ) ) );
define( 'POST_ACTIONS_LOGGER_VERSION', '1.0.0' );

/**
 * Class Post Action Logger
 *
 *
 * @version  1.0.0
 * @package  Post Action Logger
 */
class Post_Action_Logger {

	/**
	 * Table name.
	 *
	 * @var string $table_name Table name
	 * @since  1.0.0
	 */	
    private $table_name;

	/**
	 * Is logging.
	 *
	 * @var bool $is_logging Is logging
	 * @since  1.0.0
	 */	
    private $is_logging;

    /**
	 * Current user ID.
	 *
	 * @var int $current_user_id Current user ID
	 * @since  1.0.0
	 */	
    private $current_user_id;

    /*
    *
    * Class constructor
    *
    */
    public function __construct() {
        $this->current_user_id = get_current_user_id();
        global $wpdb;
        // Initialize table name and logging state.
        $this->table_name = $wpdb->prefix . 'post_action_logs';
        $is_logging = get_user_meta($this->current_user_id, 'post_action_logging_enabled', true);
        $this->is_logging  = $is_logging === 'on' ? true : false;
        // Register hooks for activation, deactivation, actions, and localization.
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        $this->log_post_action();
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_post_toggle_logging', [$this, 'toggle_logging']);
        add_action('admin_post_clear_table', [$this, 'clear_table']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('admin_init', [$this, 'manage_updates']);
    }

    /*
    *
    * Load plugin textdomain for translations
    *
    */
    public function load_textdomain() {
        load_plugin_textdomain('post-action-logger', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /*
    *
    * Runs on plugin activation: creates the database table and sets default options
    *
    */
    public function activate() {
        $this->create_table();
        add_user_meta($this->current_user_id, 'post_action_logging_enabled', 'off'); // Set logging disabled by default.
    }

    /*
    *
    * Create table
    *
    */
    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            post_data LONGTEXT NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    /*
    *
    * Runs on plugin deactivation: removes options
    *
    */
    public function deactivate() {
        delete_metadata( 'user', 0, 'post_action_logging_enabled', '', true );
    }

    /*
    *
    * Logs $_POST data if logging is enabled
    *
    */
    public function log_post_action() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST) && $this->is_logging) {
            // Sanitize and encode POST data.
            $post_data = wp_json_encode(array_map('sanitize_text_field', $_POST));
            $this->insert_log($post_data);
        }
    }

    /*
    *
    * Inserts a log entry into the database using wpdb->prepare
    *
    */
    private function insert_log($post_data) {
        global $wpdb;
        $table_name = $this->table_name;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            $this->create_table();
        }
        $prepared_query = $wpdb->prepare(
            "INSERT INTO {$table_name} (post_data) VALUES(%s)",
            $post_data
        );

        $wpdb->query($prepared_query);
    }

    /*
    *
    * Delete database table
    *
    */
    public function clear_table() {

        if 
            (current_user_can('manage_options') 
            && check_admin_referer('post_action_logger_clear_table')
        ) {     
            global $wpdb;
            $table_name = $this->table_name;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            if ($table_exists) {
                // Delete the table.
                $wpdb->query("DROP TABLE IF EXISTS $table_name");
            }
            wp_safe_redirect( esc_url( admin_url('admin.php?page=post-action-logs') ) );
            die();
            exit; 
        }
    }

    /*
    *
    * Adds an admin page for viewing logs
    *
    */
    public function add_admin_page() {
        add_menu_page(
            esc_html__('POST Logs', 'post-action-logger'),
            esc_html__('POST Logs', 'post-action-logger'),
            'manage_options',
            'post-action-logs',
            [$this, 'render_admin_page'],
            'dashicons-list-view',
            26
        );
    }

    /*
    *
    * Renders the admin page displaying logs and a toggle button for logging state
    *
    */
    public function render_admin_page() {
        $logs = $this->get_logs();
        $is_logging = $this->is_logging;
        echo '<style id="post-actions-logger-css" type="text/css">
                #post-actions-logger {
                    margin-top: 20px;
                }
                #post-actions-logger thead th {
                    font-weight: bold;
                    padding: 10px 20px
                }
                #post-actions-logger tbody td {
                    vertical-align: top;
                    padding: 10px 20px;
                }
                #post-actions-logger tbody td pre {
                    white-space: pre-wrap;
                    margin-top:0;
                    margin-bottom:0;
                }
              </style>';
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('POST Action Logs', 'post-action-logger') . '</h1>';

        // Form to toggle logging state.
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:block;float:' . (is_rtl() ? 'right' : 'left') . ';">
                <input type="hidden" name="action" value="toggle_logging">
                ' . wp_nonce_field('post_action_logger_toggle_logging', '_wpnonce', true, true) . '
                <button type="submit" class="button ' . ($is_logging ? 'button-secondary' : 'button-primary') . '">
                    ' . ($is_logging ? esc_html__('Stop Logging', 'post-action-logger') : esc_html__('Start Logging', 'post-action-logger')) . '
                </button>
              </form>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin:0 10px 10px 10px">
                <input type="hidden" name="action" value="clear_table">
                ' . wp_nonce_field('post_action_logger_clear_table', '_wpnonce', true, true) . '
                <button type="submit" class="button button-secondary">
                    ' .  esc_html__('Clear logs', 'post-action-logger') . '
                </button>
              </form>';

        // Display logs in a table
        echo '<table id="post-actions-logger" class="widefat striped" cellspacing="0">
                <thead>
                    <tr>
                        <th>' . esc_html__('ID', 'post-action-logger') . '</th>
                        <th>' . esc_html__('Timestamp', 'post-action-logger') . '</th>
                        <th>' . esc_html__('POST Data', 'post-action-logger') . '</th>
                    </tr>
                </thead>
                <tbody>';

        if ($logs) {
            foreach ($logs as $row) {
                echo '<tr>
                        <td>' . esc_html($row->id) . '</td>
                        <td>' . esc_html($row->timestamp) . '</td>
                        <td><pre>' . esc_html(print_r(json_decode($row->post_data, true), true)) . '</pre></td>
                    </tr>';
            }
        } else {
            echo '<tr><td colspan="3">' . esc_html__('No POST actions logged yet.', 'post-action-logger') . '</td></tr>';
        }

        echo '</tbody>
              </table>
              </div>';
    }

    /*
    *
    * Retrieves logs from the database
    *
    */
    private function get_logs() {
        global $wpdb;
        $table_name = $this->table_name;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            $this->create_table();
        }
        $prepared_query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY timestamp DESC", []);
        return $wpdb->get_results($prepared_query);
    }

    /*
    *
    * Toggles logging state and redirects to the admin page
    *
    */
    public function toggle_logging() {
        if (current_user_can('manage_options') 
            && check_admin_referer('post_action_logger_toggle_logging')
        ) {
            $is_logging = get_user_meta($this->current_user_id, 'post_action_logging_enabled', true);
            update_user_meta($this->current_user_id, 'post_action_logging_enabled', ($is_logging === 'on' ? 'off' : 'on'));
        }

        wp_safe_redirect( esc_url( admin_url('admin.php?page=post-action-logs') ) );
        die();
        exit;
    }

    /*
    *
    * Manage updates.
    *
    */
    public function manage_updates() {
        require_once POST_ACTIONS_LOGGER_PLUGIN_DIR . '/admin/class-post-actions-logger-plugin-updater.php';

        $github_updater = new Post_Actions_Logger_GitHub_Plugin_Updater(
            array(
                'owner'	=> 'JoseMortellaro',
                'repo'	=> 'post-actions-logger',
                'slug'	=> POST_ACTIONS_LOGGER_PLUGIN_FILENAME,
            )
        );
    }
}

// Instantiate the plugin class to initialize functionality.
add_action( 'wp_loaded', function() {
    new Post_Action_Logger();
} );