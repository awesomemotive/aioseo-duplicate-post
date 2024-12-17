<?php
namespace AIOSEO\DuplicatePost\Traits\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contains constant specific helper methods.
 *
 * @since 1.0.0
 */
trait Constants {
	/**
	 * Returns the plugin menu icon.
	 *
	 * @since 1.0.0
	 *
	 * @param  int    $width  The width of the icon.
	 * @param  int    $height The height of the icon.
	 * @param  string $color  Hexadecimal representation of the color.
	 *
	 * @return string The icon as a string.
	 */
	public function icon( $width = 32, $height = 32, $color = '#A0A5AA' ) {
		return '<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="-269 361 72 72" style="enable-background:new -269 361 72 72; width: ' . $width . '; height: ' . $height . '; fill: ' . $color . '" xml:space="preserve"><g><path class="st0" d="M-233,361c-19.9,0-36,16.1-36,36s16.1,36,36,36s36-16.1,36-36S-213.1,361-233,361z M-233,424.5 c-15.2,0-27.5-12.3-27.5-27.5s12.3-27.5,27.5-27.5s27.5,12.3,27.5,27.5S-217.8,424.5-233,424.5z"/><g><g><g><g><defs><circle id="SVGID_1_" cx="-233" cy="397" r="27.5"/></defs><clipPath id="SVGID_2_"><use xlink:href="#SVGID_1_"  style="overflow:visible;"/></clipPath><polygon class="st1" points="-242.7,387.1 -242.7,396.4 -225.8,406.6 -225.8,425.2 -216.6,428.7 -216.6,401.9"/></g></g></g></g><g><g><g><g><defs><circle id="SVGID_3_" cx="-233" cy="397" r="27.5"/></defs><clipPath id="SVGID_4_"><use xlink:href="#SVGID_3_"  style="overflow:visible;"/></clipPath><polygon class="st2" points="-227.9,379.3 -227.9,390 -211.8,399.1 -211.8,417.1 -201.8,415 -201.8,394"/></g></g></g></g><g><g><g><g><defs><circle id="SVGID_5_" cx="-233" cy="397" r="27.5"/></defs><clipPath id="SVGID_6_"><use xlink:href="#SVGID_5_"  style="overflow:visible;"/></clipPath><path class="st3" d="M-253.4,395.4v28l22.8,12.5v-26.6L-253.4,395.4z M-240.6,417.7c-0.2,0.4-0.7,0.7-1.2,0.7c-0.2,0-0.5-0.1-0.7-0.2l-7.5-4.2c-0.7-0.4-0.9-1.2-0.6-1.9c0.4-0.7,1.2-0.9,1.9-0.6l7.6,4.2C-240.4,416.2-240.2,417-240.6,417.7z M-234.6,414.5c-0.2,0.5-0.7,0.7-1.2,0.7c-0.2,0-0.5-0.1-0.7-0.2l-13.5-7.3c-0.7-0.3-0.9-1.2-0.6-1.9c0.3-0.7,1.2-0.9,1.9-0.6l13.6,7.4C-234.5,413-234.2,413.9-234.6,414.5z"/></g></g></g></g></g></svg>'; // phpcs:ignore Generic.Files.LineLength.MaxExceeded
	}
}