<?php
/*
Plugin Name: Date Archives Pagination
Version: 0.1
Plugin URI: http://keesiemeijer.wordpress.com/date-archives-pagination
Description: Functions for theme developers to paginate date based archives by year, month or day.
Author: keesiemijer
Author URI:
License: GPL v2
*/

// functions can only be used in the front end.
if ( !is_admin() ) {

	/**
	 * Displays or returns next date archive HTML link.
	 *
	 * @since 0.1
	 *
	 * @see km_dap_get_date_archive_link() Type of arguments that can be changed.
	 *
	 * @param array|string $args Optional. Override default arguments.
	 * @return string|void String (html link) when retrieving, void when displaying.
	 */
	function km_dap_next_date_archive_link( $args = '' ) {
		km_dap_get_date_archive_link( $args );
	}


	/**
	 * Displays or returns previous date archive HTML link.
	 *
	 * @since 0.1
	 *
	 * @see km_dap_get_date_archive_link() Type of arguments that can be changed.
	 *
	 * @param array|string $args Optional. Override default arguments.
	 * @return string|void String (html link) when retrieving, void when displaying.
	 */
	function km_dap_previous_date_archive_link( $args = '' ) {
		km_dap_get_date_archive_link( $args, true );
	}


	/**
	 * Gets next or previous date archive html link.
	 *
	 * @since 0.1
	 *
	 * @global $wpdb
	 * @global $wp_locale
	 * @param array|string $args     {
	 *     An array of arguments to override. Optional.
	 *     @type string 'format'      PHP date format. Defaults to a date format depending on the date archive.
	 *     @type string 'text'        Link text. Defaults to 'format' if left empty.
	 *     @type string 'before_text' Text used before 'format' or 'text'.
	 *     @type string 'after_text'  Text used after 'format' or 'text'.
	 *     @type object 'query'       WP_Query object.
	 *     @type bool   'echo'        Display or return the archive link. Default true.
	 * }
	 * @param bool    $previous Optional. Previous or next date archive link. Default: next date archive link (false).
	 * @return string|void String (html link) when retrieving, void when displaying.
	 */
	function km_dap_get_date_archive_link(  $args = '', $previous = false ) {
		global $wp_locale;

		$link_html = '';

		if ( is_date() ) {

			$defaults = array(
				'format'      => '',
				'text'        => '',
				'before_text' => '',
				'after_text'  => '',
				'query'       => $GLOBALS['wp_query'],
				'echo'        => 1,
			);

			$args = wp_parse_args(  $args, $defaults );
			extract( $args, EXTR_SKIP );

			if ( !is_a( (object) $query, 'WP_Query' ) )
				return '';

			// get date sql for next and previous date based on current archive date
			$sql = km_dap_date_archives_pagination_sql( $previous );

			if ( ( '' != $sql ) && isset( $query->query ) ) {

				$temp_query = $query->query;

				$query->query = (array) $query->query;


				$reset_query_vars =  array(
					'second' , 'minute', 'hour',
					'day', 'monthnum', 'year',
					'w', 'm',
					'paged', 'offset',
				);

				// unset date query vars (not needed for next and prev query)
				foreach ( $reset_query_vars as $var ) {
					unset( $query->query[ $var ] );
				}

				$order = ( $previous ) ? 'ASC' : 'DESC';

				// get one next or previous post
				$archive_args = array(
					'posts_per_page'               => 1,
					'order'                        => $order,
					'no_found_rows'                => true,
					'date_archives_pagination_sql' => $sql,
				);

				$args = array_merge( $query->query, $archive_args );

				// no filters needed in WordPress 3.7 (http://core.trac.wordpress.org/changeset/25139)
				add_filter( 'posts_where', 'km_dap_date_archives_pagination_posts_where', 10, 2 );
				$date_query = new WP_Query( $args );
				remove_filter( 'posts_where', 'km_dap_date_archives_pagination_posts_where', 10, 2 );

				// clean up after query
				wp_reset_postdata();

				// restore the query object
				$query->query = $temp_query;

				// check if there is a next or previous post
				if ( $date_query->have_posts() ) {

					$date = explode( ' ', $date_query->post->post_date );

					if ( isset( $date[0] ) && $date[0] ) {

						list( $year, $month, $day ) = explode( '-', $date[0] );
						$url = '';

						if ( is_year() ) {
							$url = get_year_link( $year );
							if ( '' == $text )
								$text = sprintf( '%d', $year );
						}

						if ( is_month() ) {
							$url = get_month_link( $year, $month );
							if ( '' == $text )
								$text = sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year );
						}

						if ( is_day() ) {
							$url = get_day_link( $year, $month, $day );
							if ( '' == $text ) {
								$date_format = get_option( 'date_format' );
								$date_format = ( $date_format ) ? $date_format : 'Y/m/d';
								$text = mysql2date( $date_format, $date[0] );
							}
						}

						$url = esc_url( $url );

						if ( '' != $format )
							$text = mysql2date( (string) $format, $date[0] );

						$text = wptexturize( $text );

						if ( ( '' != $url ) && ( '' != $text ) )
							$link_html = "\t<a href='$url'>{$before_text}{$text}{$after_text}</a>\n";
					}
				}
			}

			if ( $echo )
				echo $link_html;

		} // if ( is_date () ) {}

		return $link_html;
	}


	add_filter( 'query_vars', 'km_dap_date_archives_pagination_query_var' );

	/**
	 * Adds query var 'date_archives_pagination_sql' to the public query vars.
	 *
	 * Called by 'query_vars' filter.
	 *
	 * @since 0.1
	 *
	 * @param array   $query_vars
	 * @return array
	 */
	function km_dap_date_archives_pagination_query_var( $query_vars ) {

		$query_vars[] = 'date_archives_pagination_sql';

		return $query_vars;
	}


	/**
	 * Adds sql to where clause if query var date_archives_pagination_sql is set.
	 * Called by 'posts_where' filter.
	 *
	 * @since 0.1
	 *
	 * @param string  $where where clause string.
	 * @param object  $query WP_Query opject.
	 * @return string where clause.
	 */
	function km_dap_date_archives_pagination_posts_where( $where, $query ) {

		$sql = $query->get( 'date_archives_pagination_sql' );
		if ( !empty( $sql ) )
			$where .=  $sql;

		return $where;
	}


	/**
	 * Returns where sql for next and previous date archives.
	 *
	 * @since 0.1
	 *
	 * @global $wpdb
	 * @param bool    $previous Previous or next date archive sql. Default: next date archive sql (false).
	 * @return string Where sql for next or previous date archives or empty string.
	 */
	function km_dap_date_archives_pagination_sql( $previous = false ) {
		global $wpdb;

		// get the date from a post object in a date archive
		$year =  get_the_date( 'Y' );
		$month =  get_the_date( 'm' );
		$day =  get_the_date( 'd' );

		$prev_date = $next_date = $prev_sql = $next_sql = '';

		if ( is_year() && $year ) {
			$prev_date = date( 'Y-m-t H:i:s', mktime( 23, 59, 59, 12, 1, $year ) );
			$next_date = $year . '-01-01 00:00:00';
		}

		if ( is_month() && $year && $month ) {
			$prev_date = date( 'Y-m-t H:i:s', mktime( 23, 59, 59, $month, 1, $year ) );
			$next_date = $year . '-' .  $month . '-01 00:00:00';
		}

		if ( is_day() && $year && $month && $day ) {
			$prev_date = date( 'Y-m-d H:i:s', mktime( 23, 59, 59, $month, $day, $year ) );
			$next_date = $year . '-' .  $month . '-' .  $day . ' 00:00:00';
		}

		if ( $prev_date && $next_date ) {
			$prev_sql = $wpdb->prepare( " AND $wpdb->posts.post_date > %s", $prev_date );
			$next_sql = $wpdb->prepare( " AND $wpdb->posts.post_date < %s", $next_date );
		}

		return ( $previous ) ? $prev_sql : $next_sql;
	}


} // if ( !is_admin() )
