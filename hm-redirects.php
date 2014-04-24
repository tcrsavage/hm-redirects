<?php
/*
Plugin Name: HM Redirects
Description: Create a list of URLs that you would like to 301 redirect to another page or site. Modified version of Simple 301 Redirect by <a href="http://www.scottnelle.com/simple-301-redirects-plugin-for-wordpress/">Scott Nellé</a>.
Version: 1.0
Author: Human Made Limited
Author URI: http://hmn.md/
*/

/*  Copyright 2009  Scott Nellé  (email : theguy@scottnelle.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! class_exists( "Simple301redirects" ) ) {

	class Simple301Redirects {

		/**
		 * load_resources function.
		 * load in some js and css required for the wps301 admin menu
		 * @access public
		 * @return void
		 */
		function load_resources() {

			$version = explode ( '.', get_bloginfo ( 'version' ) );

			//if they are running older versions of wordpress, include newer versions of jquery and jquery ui
			if ( (int) $version[0] < 3 || ( (int) $version[0] == 3 && (int) $version [1] < 3 ) ) {

				wp_enqueue_script( 'new-jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js' );

				wp_enqueue_script( 'new-jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js', array( 'new-jquery' ) );

				$dependancy = 'new-jquery-ui';

			} else {

				wp_enqueue_script( 'jquery-ui-sortable' );

				$dependancy = 'jquery-ui-sortable';
			}

			//include custom js and css
			wp_enqueue_script( 'simple-301-js',  WPS301_URL . '/assets/wps301-script.js', array( $dependancy ) );

			wp_enqueue_style( 'simple-301-css',  WPS301_URL . '/assets/wps301-style.css' );
		}

		/**
		 * create_menu function.
		 * generate the link to the options page under settings
		 * @access public
		 * @return void
		 */
		function create_menu() {

			add_options_page('301 Redirects', '301 Redirects', 'manage_options', '301options', array( $this,'options_page' ) );

		}

		/**
		 * options_page function.
		 * generate the options page in the wordpress admin
		 * @access public
		 * @return void
		 */
		function options_page() {
			?>
			<div class="wrap">
				<div id="icon-options-general" class="icon32"><br></div>
				<h2>Simple 301 Redirects</h2>

				<form class="wps301-form" method="post" action="options-general.php?page=301options">

					<table class="wp-list-table widefat">

						<thead>
						<tr>
							<strong>
								<th colspan="2" class="wps301-head">Request</th>
								<th>Destination</th>
							</strong>
						</tr>
						</thead>

						<tbody>
						<tr>
							<td colspan="2" class="wps301-head"><small>example: /about.htm</small></td>
							<td><small>example: <?php echo get_option('home'); ?>/about/</small></td>
						</tr>
						</tbody>

						<tbody class="wps301-sortable">
						<?php echo $this->expand_redirects(); ?>
						</tbody>

						<tbody>
						<tr id="new-item" >
							<td class="new-item-input no-border"><div id="wps301-addnew"></div><input type="text" name="301_redirects[request][]" /></td>
							<td class="spacer no-border">&raquo;</td>
							<td class="no-border"><input type="text" name="301_redirects[destination][]" /></td>
						</tr>
						</tbody>

						<?php wp_nonce_field( 'submit_301_nonce', 'submit_301_nonce' ); ?>

					</table>

					<p class="submit">
						<input type="submit" name="submit_301" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" />
					</p>

				</form>

			</div>
		<?php
		}

		/**
		 * expand_redirects function.
		 * utility function to return the current list of redirects as form fields
		 * @access public
		 * @return string <html>
		 */
		function expand_redirects() {

			$redirects = get_option( '301_redirects' );

			$output = '';

			if ( !empty( $redirects ) ) {

				foreach ( $redirects as $request => $destination ) {

					$output .= '
						<tr>
							<td class="dragger request"><input type="text" name="301_redirects[request][]" value="' . $request . '" /></td>
							<td class="spacer">&raquo;</td>
							<td class="destination"><div class="wps301-delete"></div><input type="text" name="301_redirects[destination][]" value="' . esc_url( $destination ) . '" /></td>
						</tr>';
				}

			}

			return $output;
		}

		/**
		 * save_redirects function.
		 * save the redirects from the options page to the database
		 * @access public
		 * @param mixed $data
		 * @return void
		 */
		function save_redirects( $data ) {

			$redirects = array();

			for( $i = 0; $i < sizeof( $data['request'] ); ++$i ) {

				$request = trim( $data['request'][$i] );

				$destination = trim( $data['destination'][$i] );

				if ( $request == '' && $destination == '' )
					continue;

				else
					$redirects[$request] = $destination;
			}

			update_option( '301_redirects', $redirects );
		}

		/**
		 * redirect function.
		 * Read the list of redirects and if the current page
		 * is found in the list, send the visitor on her way
		 * @access public
		 * @return void
		 */
		function redirect() {

			// this is what the user asked for (strip out home portion, case insensitive)

			$userrequest = str_ireplace( get_option( 'home' ), '', $this->get_address() );

			$userrequest = rtrim( $userrequest, '/' );

			$redirects = get_option( '301_redirects' );

			if ( ! empty( $redirects ) ) {

				if ( strpos( $this->get_address(), 'https://' ) !== false )
					return;

				foreach ( $redirects as $storedrequest => $destination ) {

					// compare user request to each 301 stored in the db
					$storedrequest = '/' . str_replace( '/', '\/', rtrim( $storedrequest, '/' ) ) . '/';

					if ( preg_match( $storedrequest, urldecode( $userrequest ) ) ) {

						$destination = preg_replace( $storedrequest , $destination, urldecode( $userrequest ) );

						header ( 'HTTP/1.1 301 Moved Permanently' );

						header ( 'Location: ' . esc_url( $destination ) );

						exit();

					} else {

						unset( $redirects );
					}
				}
			}
		}

		/**
		 * get_address function.
		 * utility function to get the full address of the current request
		 * credit: http://www.phpro.org/examples/Get-Full-URL.html
		 * @access public
		 * @return string
		 */
		function get_address() {

			/*** check for https ***/
			$protocol = ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';

			if ( ! isset( $_SERVER['HTTP_HOST'] ) )
				return '';

			/*** return the full address ***/
			return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}

		function process_form_submission() {

			if ( ! isset(  $_POST['submit_301_nonce'] ) || ! wp_verify_nonce( $_POST['submit_301_nonce'], 'submit_301_nonce' ) ) {
				wp_die( 'Nonce failed' );
			}

			$this->save_redirects( $_POST['301_redirects'] );
			wp_redirect( add_query_arg( array( 'updated' =>  'true' ) ) );
			exit;
		}

	} // end class Simple301Redirects

} // end check for existance of class

// instantiate
$redirect_plugin = new Simple301Redirects();

if ( isset( $redirect_plugin ) ) {

	//define the url of the WPS301 directory
	if ( ! defined( 'WPS301_URL' ) ) {

		$exp = explode( DIRECTORY_SEPARATOR, dirname( __FILE__ ) );
		define( 'WPS301_URL', WP_PLUGIN_URL . '/' . end( $exp ) );
	}

	// add the redirect action, high priority
	add_action( 'init', array( $redirect_plugin, 'redirect' ), 1 );

	// load the css and js resources in
	add_action( 'load-settings_page_301options', array( $redirect_plugin, 'load_resources' ) );

	// create the menu
	add_action( 'admin_menu', array( $redirect_plugin, 'create_menu' ) );

	// if submitted, process the data
	if ( isset( $_POST['submit_301'] ) ) {
		add_action( 'init',  array( $redirect_plugin, 'process_form_submission' ) );
	}

}