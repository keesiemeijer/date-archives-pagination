# Date Archives Pagination #

A WordPress plugin to paginate your date archives by year, month or day.

## Description ##

This WordPress plugin provides functions you can use in your (archive) theme template files to paginate by day, month or year dependending on the type of date archive.

For example, when visiting a monthly date archive page, the functions provided will link to the next and previous month archive page if available.

## Usage ##

**Note**
Consider creating a <a href="http://codex.wordpress.org/Child_Themes">child theme</a> instead of editing your theme directly - if you upgrade the theme all your modifications will be lost.

Activate this plugin and find your theme's [date archive templates](https://developer.wordpress.org/themes/basics/template-hierarchy/#date).

Find the [pagination functions](https://codex.wordpress.org/Pagination#Function_Reference) used by your theme and replace them with one of these functions.

Display the next date archive page link.
```php
<?php dap_next_posts_link( $label = null ); ?>
```

Return the next date archive page link.
```php
<?php dap_get_next_posts_link( $label = null ); ?>
```

Display the previous date archive page link.
```php
<?php dap_previous_posts_link( $label = null ); ?>
```

Return the previous date archive page link.
```php
<?php dap_get_previous_posts_link( $label = null ); ?>

```

If you don't use the `$label` parameter in the functions above the default text format of the links depending on the date archive is:

* daily archive   — `March 12, 2016` (date format from wp-admin > Settings > General)
* monthly archive — `March 2016`
* yearly archive  — `2016`

### Example ###
Simple example to use the functions above.

```php
<?php
// Check if it's a date archive
if ( is_date() ) {

	// Next date archive
	if ( function_exists( 'dap_next_posts_link' ) ) {
		dap_next_posts_link();
	}

	// Previous date archive
	if ( function_exists( 'dap_previous_posts_link' ) ) {
		dap_previous_posts_link();
	}
} else {
	// Other archive pagination here
}
?>
```

## Next and Previous Date Archive Date ##
If you need to know the date of the next or previous date archive use these functions.

Returns a next adjacent date archive page date.
```php
<?php dap_get_next_date_archive_date(); ?>

```

Returns a previous adjacent date archive page date.
```php
<?php dap_get_previous_date_archive_date(); ?>

```

### Example ###
Example to print the previous date archive date.
```php
<?php
$previous_date = '';

if ( function_exists( 'dap_get_previous_date_archive_date' ) ) {
	$previous_date = dap_get_previous_date_archive_date();	
}

echo $previous_date;
// If a next date archive date exist it prints something similar to this
// 2016-03-12
?>
```




