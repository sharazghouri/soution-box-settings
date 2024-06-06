WordPress Settings Framework
============================
<p align="center">

<a href="https://packagist.org/packages/solutionbox/wordpress-settings-framework"><img src="https://img.shields.io/packagist/dt/solutionbox/wordpress-settings-framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/solutionbox/wordpress-settings-framework"><img src="https://img.shields.io/packagist/v/solutionbox/wordpress-settings-framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/solutionbox/wordpress-settings-framework"><img src="https://img.shields.io/packagist/l/solutionbox/wordpress-settings-framework" alt="License"></a>
</p>
The WordPress Settings Framework aims to take the pain out of creating settings pages for your WordPress plugins
by effectively creating a wrapper around the WordPress settings API and making it super simple to create and maintain
settings pages.

This repo is actually a working plugin which demonstrates how to implement SBSA in your plugins. See `src/sbsa-test.php`
for details.

You can use this framework with composer if you are using auto loading in your plugin.

## Installation

```php
composer require solutionbox/wordpress-settings-framework
```

Setting Up Your Plugin
----------------------

1. Install the package via composer.
2. Create a "settings" folder in your plugin root.
3. Create a settings file in your new "settings" folder (e.g. `settings-general.php`)

Now you can set up your plugin like:

```php
use  Solution_Box_Settings;

class SBSATest {
	/**
	 * @var string
	 */
	private $plugin_path;

	/**
	 * @var WordPressSettingsFramework
	 */
	private $sbsa;

	/**
	 * SBSATest constructor.
	 */
	function __construct() {
		$this->plugin_path = plugin_dir_path( __FILE__ );

		$this->sbsa = new Solution_Box_Settings\SettingsAPI( $this->plugin_path . 'src/settings/example-settings.php', 'my_example_settings' );

		// Add admin menu
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 20 );
		
		// Add an optional settings validation filter (recommended)
		add_filter( $this->sbsa->get_option_group() . '_settings_validate', array( &$this, 'validate_settings' ) );
	}

	/**
	 * Add WooCommerce sub settings page.
	 */
	function add_settings_page() {
		$this->sbsa->add_settings_page( array(
			'parent_slug' => 'woocommerce',
			'page_title'  => __( 'Page Title', 'text-domain' ),
			'menu_title'  => __( 'menu Title', 'text-domain' ),
			'capability'  => 'manage_woocommerce',
		) );
	}

	/**
	 * Validate settings.
	 * 
	 * @param $input
	 *
	 * @return mixed
	 */
	function validate_settings( $input ) {
		// Do your settings validation here
		// Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting
		return $input;
	}

	// ...
}
```

Your settings values can be accessed like so:

```php
// Get settings
$this->sbsa->get_settings();
```

This will get either the saved setting values, or the default values that you set in your settings file.

Or by getting individual settings:

```php
// Get individual setting
$setting = Solution_Box_Settings\SettingsAPI::get_setting( 'prefix_settings_general', 'general', 'text' );
```


The Settings Files
------------------

The settings files work by filling the global `$sbsa_settings` array with data in the following format:

```php
$sbsa_settings[] = array(
    'section_id' => 'general', // The section ID (required)
    'section_title' => 'General Settings', // The section title (required)
    'section_description' => 'Some intro description about this section.', // The section description (optional)
    'section_order' => 5, // The order of the section (required)
    'fields' => array(
        array(
            'id' => 'text',
            'title' => 'Text',
            'desc' => 'This is a description.',
            'placeholder' => 'This is a placeholder.',
            'type' => 'text',
            'default' => 'This is the default value'
        ),
        array(
            'id' => 'select',
            'title' => 'Select',
            'desc' => 'This is a description.',
            'type' => 'select',
            'default' => 'green',
            'choices' => array(
                'red' => 'Red',
                'green' => 'Green',
                'blue' => 'Blue'
            )
        ),

        // add as many fields as you need...

    )
);
```

Valid `fields` values are:

* `id` - Field ID
* `title` - Field title
* `desc` - Field description
* `placeholder` - Field placeholder
* `type` - Field type (text/password/textarea/select/radio/checkbox/checkboxes/color/file/editor/code_editor)
* `default` - Default value (or selected option)
* `choices` - Array of options (for select/radio/checkboxes)
* `mimetype` - Any valid mime type accepted by Code Mirror for syntax highlighting (for code_editor)

See `settings/example-settings.php` for an example of possible values.


API Details
-----------

    new Solution_Box_Settings\SettingsAPI( string $settings_file [, string $option_group = ''] )

Creates a new settings [option_group](http://codex.wordpress.org/Function_Reference/register_setting) based on a settings file.

* `$settings_file` - path to the settings file
* `$option_group` - optional "option_group" override (by default this will be set to the basename of the settings file)

<pre> Solution_Box_Settings\SettingsAPI::get_setting( $option_group, $section_id, $field_id )</pre>

Get a setting from an option group

* `$option_group` - option group id.
* `$section_id` - section id (change to `[{$tab_id}_{$section_id}]` when using tabs.
* `$field_id` - field id.

<pre>Solution_Box_Settings\SettingsAPI::delete_settings( $option_group )</pre>

Delete all the saved settings from a option group

* `$option_group` - option group id

Actions & Filters
---------------

**Filters**

* `sbsa_register_settings_[option_group]` - The filter used to register your settings. See `settings/example-settings.php` for an example.
* `[option_group]_settings_validate` - Basically the `$sanitize_callback` from [register_setting](http://codex.wordpress.org/Function_Reference/register_setting). Use `$sbsa->get_option_group()` to get the option group id.
* `sbsa_defaults_[option_group]` - Default args for a settings field

**Actions**

* `sbsa_before_settings_page_[option_group]` - Before setting page HTML is output
* `sbsa_after_settings_page_[option_group]` - After setting page HTML is output
* `sbsa_before_settings_page_header_[option_group]` - Before setting page header HTML is output
* `sbsa_after_settings_page_header_[option_group]` - After setting page header HTML is output
* `sbsa_settings_sections_args_[option_group]` - Section extra args for to wrap the section with HTML and extra class [More](https://developer.wordpress.org/reference/functions/add_settings_section/#parameters)
* `sbsa_before_field_[option_group]` - Before a field HTML is output
* `sbsa_before_field_[option_group]_[field_id]` - Before a field HTML is output
* `sbsa_after_field_[option_group]` - After a field HTML is output
* `sbsa_after_field_[option_group]_[field_id]` - After a field HTML is output
* `sbsa_before_settings_[option_group]` - Before settings form HTML is output
* `sbsa_after_settings_[option_group]` - After settings form HTML is output
* `sbsa_before_tabless_settings_[option_group]` - Before settings  section HTML is output
* `sbsa_after_tabless_settings_[option_group]` - After settings section HTML is output
* `sbsa_before_settings_fields_[option_group]` - Before settings form fields HTML is output (inside the `<form>`)
* `sbsa_do_settings_sections_[option_group]` - Settings form fields HTMLoutput (inside the `<form>`)
* `sbsa_before_tab_links_[option_group]` - Before tabs HTML is output
* `sbsa_after_tab_links_[option_group]` - After tabs HTML is output

Examples
-----------
**Example 1 Tabless settings**
<img width="1190" alt="image" src="https://user-images.githubusercontent.com/17900945/227388614-e0bb62c4-f09a-49f9-875f-b37e2d0e9fce.png">

**Example 2 Tabbed settings**
<img width="1251" alt="image" src="https://user-images.githubusercontent.com/17900945/227388843-719a1f93-39f7-4459-afa1-13642c799a31.png">

Credits
-------

The WordPress Settings Framework was Cloned from [iconicwp](https://github.com/iconicwp/WordPress-Settings-Framework) then converted into php package with more features.

Please contribute by [reporting bugs](https://github.com/sharazghouri/soution-box-settings/issues) and submitting [pull requests](https://github.com/sharazghouri/soution-box-settings/pulls).

Want to say thanks? [Consider tipping me](https://www.paypal.me/jamesckemp).
