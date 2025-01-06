=== Post Actions Logger ===
Contributors: Jose Mortellaro
Plugin URI: https://github.com/your-username/post-actions-logger
Tags: logging, $_POST, backend, debug, actions
Requires at least: 5.0
Tested up to: 6.0
Stable tag: 1.0.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

**Post Actions Logger** is a simple plugin that allows you to start and stop a recorder in the WordPress backend to log all the `$_POST` actions. This can be useful for debugging, tracking form submissions, or monitoring other `$_POST` data that is being sent in the backend.

With the Post Actions Logger, you can easily track the `$_POST` variables being sent and logged in your WordPress installation, helping you understand and debug various actions in the backend of your site.

Key Features:
- Start and stop the logging of `$_POST` actions from the backend.
- Log all `$_POST` data when activated.
- Easily disable or enable the logger for specific actions.
- Clean and simple interface.

== Installation ==

1. Upload the `post-actions-logger` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Once activated, the plugin will add a simple interface to start and stop the recorder of `$_POST` actions.
4. When the recorder is started, all `$_POST` actions will be logged for review.

== Frequently Asked Questions ==

= What does this plugin do? =

This plugin records all the `$_POST` actions in the WordPress backend. You can start and stop the recorder as needed. It can be useful for debugging or tracking user actions and form submissions.

= How do I start logging the actions? =

After activating the plugin, go to the plugin's settings page in the backend and click the button to start the recording of `$_POST` actions.

= How do I stop the logging? =

To stop logging the `$_POST` actions, simply go back to the settings page and click the button to stop the recorder.

= Is this plugin suitable for production environments? =

This plugin is designed primarily for debugging and development purposes. It's not recommended to keep logging enabled on production websites as it can store sensitive data. Please ensure to turn off logging when not in use.

== Changelog ==

= 1.0.0 =
* Initial release.
* Plugin logs all `$_POST` actions when the recorder is activated in the backend.

== Upgrade Notice ==

= 1.0.0 =
* First version of the plugin. No upgrade notice needed.

== Arbitrary section ==

If you would like to contribute to the Post Actions Logger plugin or need support, please visit the GitHub repository for more information:

GitHub Repository: https://github.com/JoseMortellaro/post-actions-logger

