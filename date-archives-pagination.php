<?php
/*
Plugin Name: Date Archives Pagination
Version: 1.0.0
Plugin URI:
Description:  Functions for theme developers to paginate date based archives by year, month or day.
Author: keesiemijer
Author URI:
Text Domain: custom-post-type-date-archives-pagination
Domain Path: languages
License: GPL v2

Date Archives Pagination
Copyright 2016  Kees Meijer  (email : keesie.meijer@gmail.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version. You may NOT assume that you can use any other version of the GPL.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Removes pagination on custom post type date archives
add_action( 'pre_get_posts', 'dap_remove_date_archives_pagination' );

function dap_remove_date_archives_pagination( $query ) {
	if ( !is_admin() && is_date() && $query->is_main_query() ) {
		$query->set( 'nopaging', true );
	}
}

/**
 * Display the next date archive page link.
 *
 * @param string  $label Next page link text.
 * @return void.
 */
function dap_next_posts_link( $label = null ) {
	echo  dap_get_next_posts_link( $label );
}


/**
 * Return the next date archive page link.
 *
 * @param string  $label Next page link text.
 * @return string        HTML-formatted next date archive page link.
 */
function dap_get_next_posts_link( $label = null ) {
	return dap_get_date_archive_link( $label, 'next' );
}


/**
 * Display the previous date archive page link.
 *
 * @param string  $label Previous page link text.
 * @return void
 */
function dap_previous_posts_link( $label = null ) {
	echo dap_get_previous_posts_link( $label );
}


/**
 * Return the previous date archive page link.
 *
 * @param string  $label Previous page link text.
 * @return string        HTML-formatted previous date archive page link.
 */
function dap_get_previous_posts_link( $label = null ) {
	return dap_get_date_archive_link( $label, 'previous' );
}


/**
 * Returns a next adjacent date archive page date.
 *
 * @return string String with post date or empty string.
 */
function dap_get_next_date_archive_date() {

	$date_post = dap_get_next_cpt_date_archive_post();
	if ( !empty( $date_post ) && isset( $date_post->post_date ) ) {
		$date = explode( ' ', $date_post->post_date );
		return $date[0];
	}

	return '';
}


/**
 * Returns previous adjacent date archive page date.
 *
 * @return string String with post date or empty string.
 */
function dap_get_previous_date_archive_date() {

	$date_post = dap_get_previous_cpt_date_archive_post();

	if ( !empty( $date_post ) && isset( $date_post->post_date ) ) {
		$date = explode( ' ', $date_post->post_date );
		return $date[0];
	}

	return '';
}


/**
 * Returns a next adjacent date archive page post.
 *
 * @return object Post object
 */
function dap_get_next_date_archive_post() {
	return dap_get_adjacent_cpt_date_archive_post( 'next' );
}


/**
 * Returns previous adjacent date archive page post.
 *
 * @return object Post object
 */
function dap_get_previous_date_archive_post() {
	return dap_get_adjacent_cpt_date_archive_post( 'previous' );
}


/**
 * Returns previous or next adjacent date archive page post.
 *
 * @param string  $adjacent 'next' or 'previous'.
 * @return object|bool Post object or false (there is no next post).
 */
function dap_get_adjacent_date_archive_post( $adjacent = 'next' ) {
	global $wp_query;

	if ( !is_date() ) {
		return;
	}

	$query = $wp_query->query;

	$post_type   = isset( $query['post_type'] ) ? $query['post_type'] : 'post';
	$post_status = isset( $query['post_status'] ) ? $query['post_status'] : 'publish';
	
	$reset_query_vars =  array(
		'second' , 'minute', 'hour',
		'day', 'monthnum', 'year',
		'w', 'm',
		'paged', 'offset',
	);

	// unset date query vars (not needed for next and prev query)
	foreach ( $reset_query_vars as $var ) {
		unset( $query[ $var ] );
	}

	$previous = ( 'previous' === strtolower( $adjacent ) ) ? true : false;
	$order    = ( $previous ) ? 'ASC' : 'DESC';
	$type     = ( $previous ) ? 'after' : 'before';

	// Get the date from the current post object
	$year     = get_the_date( 'Y' );
	$month    = get_the_date( 'm' );
	$day      = get_the_date( 'd' );

	$args = array(
		'post_type'      => $post_type,
		'post_status'    => $post_status,
		'posts_per_page' => 1,
		'order'          => $order,
		'no_found_rows'  => true,
	);

	if ( is_year() && $year ) {
		$args['date_query'][0][ $type ] = array( 'year' => $year );
	}

	if ( is_month() && $year && $month ) {
		$args['date_query'][0][ $type ] = array( 'year' => $year, 'month' => $month );
	}

	if ( is_day() && $year && $month && $day ) {
		$args['date_query'][0][ $type ] = array( 'year' => $year, 'month' => $month, 'day' => $day );
	}

	$args = array_merge( $query, $args );

	$post = get_posts( $args );

	if ( isset( $post[0] ) ) {
		$post = $post[0];
	} else {
		$post = false;
	}

	return $post;
}


/**
 * Returns a HTML-formatted next or previous adjacent date archive page link.
 *
 * @param string  $label    Link text.
 * @param string  $adjacent 'next' or 'previous'
 * @return [type]            HTML-formatted next or previous date archive page link.
 */
function dap_get_date_archive_link( $label = null, $adjacent = 'next' ) {
	global $wp_locale;

	if ( !is_date() ) {
		return '';
	}

	$post     = dap_get_adjacent_date_archive_post( $adjacent );
	$previous = ( 'previous' === strtolower( $adjacent ) ) ? true : false;

	if ( !isset( $post->post_date ) ) {
		return '';
	}

	$year  = get_the_date( 'Y', $post );
	$month = get_the_date( 'm', $post );
	$day   = get_the_date( 'd', $post );

	$url  = '';
	$text = '';
	if ( is_year() ) {
		$url  = get_year_link( $year );
		$text = sprintf( '%d', $year );
	}

	if ( is_month() && $year && $month ) {
		$url  = get_month_link( $year, $month );
		$text = sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year );
	}

	if ( is_day() && $year && $month && $day ) {
		$url  = get_day_link( $year, $month, $day );
		$date_format = get_option( 'date_format' );
		$date_format = ( $date_format ) ? $date_format : 'Y/m/d';
		$text = mysql2date( $date_format, $post->post_date );
	}

	if ( $label ) {
		$text = trim( $label );
	}

	if ( $url && $text ) {
		return "<a href='$url'>{$text}</a>";
	}

	return '';
}