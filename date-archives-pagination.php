<?php
/*
Plugin Name: Date Archives Pagination
Version: 0.1
Plugin URI: http://keesiemeijer.wordpress.com/date-archives-pagination
Description: This plugin adds pagination functions for date based archives.
Author: keesiemijer
Author URI:
License: GPL v2
*/

if ( !is_admin() ) {


	/**
	 * Displays next date archive posts link
	 *
	 * @since 0.1
	 *
	 * @param array $args See km_dap_get_date_archive_link() for default arguments
	 * @return array
	 */
	function km_dap_next_date_archive_link( $args = '' ) {
		echo km_dap_get_next_date_archive_link( $args );
	}


	/**
	 * Displays previous date archive posts link
	 *
	 * @since 0.1
	 *
	 * @param array $args See km_dap_get_date_archive_link() for default arguments
	 * @return array
	 */
	function km_dap_previous_date_archive_link( $args = '' ) {
		echo km_dap_get_previous_date_archive_link( $args, true );
	}


	/**
	 * Returns next date archive posts link
	 *
	 * @since 0.1
	 *
	 * @param array $args See km_dap_get_date_archive_link() for default arguments
	 * @return array
	 */
	function km_dap_get_next_date_archive_link( $args = '' ) {
		return km_dap_get_date_archive_link( $args );
	}


	/**
	 * Returns next archive posts link
	 *
	 * @since 0.1
	 *
	 * @param array $args See km_dap_get_date_archive_link() for default arguments.
	 * @return array
	 */
	function km_dap_get_previous_date_archive_link(  $args = '' ) {
		return km_dap_get_date_archive_link(  $args , true );
	}


	/**
	 * Gets next or previous date archive posts html link
	 *
	 * @since 0.1
	 *
	 * @param array $args See get_archive_link() for default arguments.
	 * @return string Archive html link or empty string.
	 */
	function km_dap_get_date_archive_link(  $args = '', $previous = false ) {
		global $wp_locale, $wp_query;

		$link_html = '';

		if ( is_date() ) {

			$defaults = array(
				'format' => '',
				'text' => '',
				'before_text' => '',
				'after_text' => '',
				'before' => '',
				'after' => '',
				'query' => $wp_query,
			);

			$args = wp_parse_args(  $args, $defaults );
			extract( $args, EXTR_SKIP );

			if ( !is_a( (object) $query, 'WP_Query' ) )
				return '';
			
			// get 'where' sql for next or previous archive posts link from current post object
			$sql = km_dap_date_archives_pagination_sql( $previous );

			if ( '' != $sql ) {

				// unset date query vars (not needed for query)
				unset( $query->query['m'], $query->query['year'], $query->query['monthnum'], $query->query['day'] );

				$order = ( $previous ) ? 'ASC' : 'DESC';

				$archive_args = array(
					'posts_per_page' => 1,
					'order' => $order,
					'date_archives_pagination_sql' => $sql,
				);

				$args = array_merge( $query->query, $archive_args );
				
				add_filter( 'posts_where', 'km_dap_date_archives_pagination_posts_where', 10, 2 );
				$date_query = new WP_Query( $args );
				remove_filter( 'posts_where', 'km_dap_date_archives_pagination_posts_where', 20, 2 );

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

						if ( '' != $format ) {
							$text = mysql2date( (string) $format, $date[0] );
						}

						$text = wptexturize( $text );

						if ( ( '' != $url ) && ( '' != $text ) ) {
							$title_text = esc_attr( $text );
							$link_html = "\t$before<a href='$url' title='$title_text'>{$before_text}{$text}{$after_text}</a>$after\n";
						}
					}
				}
			}
		}

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
	 * @param array $query_vars
	 * @return array
	 */
	function km_dap_date_archives_pagination_query_var( $query_vars ) {

		$query_vars[] = 'date_archives_pagination_sql';

		return $query_vars;
	}


	/**
	 * Adds to where sql if query var date_archives_pagination_sql is set.
	 *
	 * Called by 'posts_where' filter.
	 *
	 * @since 0.1
	 *
	 * @param string $where Where sql string.
	 * @param object $query WP_Query opject.
	 * @return string Where sql.
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
	 * @param bool $previous If true get sql for previous date archives posts.
	 * @return string where sql for next or previous date archives.
	 */
	function km_dap_date_archives_pagination_sql( $previous = false ) {
		global $wpdb;

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
