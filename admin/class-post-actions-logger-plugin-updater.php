<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

// Add this to your plugin's main file

class Post_Actions_Logger_GitHub_Plugin_Updater  {

	/**
	 * Stores the config.
	 *
	 * @since 1.0
	 * @var type
	 */
	protected $config;

    /**
	 * Stores the GitHub API URL.
	 *
	 * @since 1.0
	 * @var string
	 */
	protected $github_api_url;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 * @param array $config
	 */
	public function __construct( $config ) {
		$defaults = array(
			'repo'			=> null,
			'owner'			=> null,
			'slug'			=> null,
			'access_token'  => null,
			'http_args'		=> array(),
		);
		$this->config = (object) array_merge( $defaults, $config );
        $this->github_api_url = 'https://api.github.com/repos/' . $this->config->owner . '/' . $this->config->repo . '/releases/latest';
        // default slug equals the repo name
		if ( empty( $this->config->slug ) ) {
			$this->config->slug = $this->config->repo . '/' . $this->config->repo . '.php';
        }
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_available' ) );
    }

	/**
	 * Call the GitHub API and return a json decoded body.
	 *
	 * @since 1.0
	 * @param string $url
	 * @see http://developer.github.com/v3/
	 * @return boolean|object
	 */
	protected function api( $url ) {
        $github_api_url = $this->github_api_url;
		$response = wp_remote_get( $github_api_url );
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != '200' ) return false;
		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Reads the remote plugin file.
	 *
	 * Uses a transient to limit the calls to the API.
	 *
	 * @since 1.0
	 */
	protected function get_remote_info() {
		$remote = get_site_transient( __CLASS__ . ':remote' );
		if ( ! $remote ) {
			$remote = $this->api( '/repos/:owner/:repo/contents/' . basename( $this->config->slug ) );
			if ( $remote ) set_site_transient( __CLASS__ . ':remote', $remote, 60 * 60 );
		}
		return $remote;
	}

	/**
	 * Retrieves the remote version from the file header of the plugin
	 *
	 * @since 1.0
	 * @return string|boolean
	 */
	protected function get_remote_version() {
		$response = $this->get_remote_info();
		if ( ! $response ) return false;
		if ( is_object( $response ) && isset( $response->tag_name ) ) return $response->tag_name;
		return false;
	}

	/**
	 * Hooks into pre_set_site_transient_update_plugins to update from GitHub.
	 *
	 * @since 1.0
	 * @todo fill url with value from remote repostory
	 * @param $transient
	 * @return $transient If all goes well, an updated one.
	 */
	public function update_available( $transient ) {

		// if ( empty( $transient->checked ) )
		// 	return $transient;

		$local_version  = $this->config->current_version;
		$remote_version = $this->get_remote_version();
		if ( $local_version && $remote_version && version_compare( $remote_version, $local_version, '>' ) ) {
			$plugin = array(
				'slug'		  => dirname( $this->config->slug ),
				'new_version' => $remote_version,
				'url'		  => null,
				'package'	  => $this->get_remote_info()->assets[0]->browser_download_url
			);
			$transient->response[ $this->config->slug ] = (object) $plugin;
		}

		return $transient;
	}
}