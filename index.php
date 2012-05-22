<?php
function esc_attr( $s ) {
	return htmlspecialchars( $s, ENT_QUOTES );
}

function esc_url( $url ) {
	return 'http://' . str_replace( 'http://', '', $url );
}

// Unset, to be safe
unset( $plugin, $url, $headers, $wpdir, $install, $e, $errors, $confirm, $focus );

if ( isset( $_REQUEST['plugin'] ) )
	$plugin = preg_replace( '#[^0-9a-z-]#i', '', $_REQUEST['plugin'] );

if ( isset( $_REQUEST['url'] ) ) {
	$url = 'http://' . str_replace( 'http://', '', stripslashes( $_REQUEST['url'] ) );
	$url = preg_replace( '|/+$|', '', $url ) . '/';
	if ( 'http:/' == $url )
		$url = 'http://';
} else {
	$url = 'http://';
}

if ( isset( $_POST['form'] ) ) {
	try {
		$focus = 'url';
		if ( empty( $_REQUEST['url'] ) || $_REQUEST['url'] == 'http://' )
			throw new Exception( "You must provide a WordPress installation URL" );
		$headers = @get_headers( $url, true );
		if ( !$headers )
			throw new Exception( "Invalid URL" );
		if ( isset( $headers['X-Pingback'] ) && is_array( $headers['X-Pingback'] ) )
			$headers['X-Pingback'] = $headers['X-Pingback'][0];
		if ( !isset( $headers['X-Pingback'] ) || substr( $headers['X-Pingback'], -10 ) !== 'xmlrpc.php' ) {
			// Gonna have to look at the contents
			$contents = file_get_contents( $url );
			if ( !preg_match( '#(http://[^"?\']+/xmlrpc\.php)#', $contents, $matches ) ) {
				$headers = @get_headers( $url . 'wp-login.php', true );
				if ( isset( $headers['X-Pingback'] ) && is_array( $headers['X-Pingback'] ) )
					$headers['X-Pingback'] = $headers['X-Pingback'][0];
				if ( !isset( $headers['X-Pingback'] ) || substr( $headers['X-Pingback'], -10 ) !== 'xmlrpc.php' )
					throw new Exception( "This does not appear to be a WordPress installation" );
			}
			if ( isset( $matches[1] ) )
				$headers['X-Pingback'] = $matches[1];
		}
		$pheaders = @get_headers( 'http://wordpress.org/extend/plugins/' . $plugin . '/', true );
		if ( strpos( $pheaders[0], '200' ) === false ) {
			$focus = 'plugin';
			throw new Exception( "The plugin slug is not valid" );
		}
		// Still here? Let's grab the WP install directory URL:
		$wpdir = str_replace( 'xmlrpc.php', '', $headers['X-Pingback'] );
		$install = true;
		header( "Location: " . $wpdir . 'wp-admin/plugin-install.php?tab=plugin-information&plugin=' . $plugin );
		exit();
	} catch( Exception $e ) {
		$errors = '<span>Error:</span> ' . $e->getMessage();
	}
} elseif ( isset( $_REQUEST['plugin'] ) ) {
	$confirm = true;
	$focus = 'url';
	try {
		$pheaders = @get_headers( 'http://wordpress.org/extend/plugins/' . $plugin . '/', true );
		if ( strpos( $pheaders[0], '200' ) === false ) {
			$focus = 'plugin';
			throw new Exception( "The plugin slug is not valid" );
		}
	} catch( Exception $e ) {
		$confirm = false;
		$about = true;
		$errors = '<span>Error:</span> ' . $e->getMessage();
	}
	if ( empty( $_REQUEST['plugin'] ) ) {
		header( "Location: /wp-plugin-install/" );
		exit();
	}
} else {
	$focus = 'plugin';
	$about = true;
}
?><!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" href="style.css?v=2" type="text/css" />
	<title>WordPress Plugin Installer</title>
</head>
<body<?php if ( isset( $install ) && $install ) {?> id="install"<?php } ?>>
<div id="wrap">
<?php if ( !isset( $install ) || !$install ) : ?>
<div id="about">
<?php if ( isset( $about ) && $about ) : ?>
<p>This tool makes it easy to directly install a WordPress plugin.</p>
<h2>Users</h2>
<p>Drag this to your bookmarks bar: <a onclick="return false;" href="javascript:l=window.location.toString();window.location='http://coveredwebservices.com/wp-plugin-install/?plugin='+l.replace(/.*?wordpress\.org\/extend\/plugins\/([^\/]+)\/.*/, '$1');">Install WP Plugin</a>. While browsing the <a href="http://wordpress.org/extend/plugins/">WordPress Plugin Directory</a>, click that bookmarklet to install the plugin you&#8217;re currently viewing.</p>
<h2>Developers:</h2>
<p>Craft a URL like this:</p>
<p><code>http://coveredwebservices.com/wp-plugin-install/?plugin=your-plugin-slug</code></p>
<?php else : ?>
<p><a href="/wp-plugin-install/">This tool</a> helps you install WordPress plugins.</p>
<p>If you would like to install the WordPress plugin listed below, please provide your WordPress URL and click &#8220;Install Plugin.&#8221;</p>
<?php endif; ?>
</div>
<?php endif; ?>

<?php if ( isset( $errors ) ) : ?>
	<div id="errors">
	<p><?php echo $errors; ?></p>
	</div>
<?php endif; ?>

<?php if ( isset( $install ) && $install ) : ?>
	<div id="install-box"><h1>Install <span><?php echo htmlspecialchars( $plugin ); ?></span> on <span><?php echo htmlspecialchars( $wpdir ); ?></span> ?</h1><iframe src="<?php echo esc_url( $wpdir . 'wp-admin/plugin-install.php?tab=plugin-information&plugin=' . $plugin . '&TB_iframe=true&width=800&height=900' ); ?>" width="800" height="900" /></div>
<?php elseif ( !isset( $about) || !$about ): ?>
<form action="" <?php if ( isset( $about ) && $about ) { ?>method="get"<?php } else { ?>method="post"<?php } ?>>
<table>
	<tr>
		<td class="label"><label for="plugin">Plugin Slug:</label></td>
		<td class="val"><input<?php if ( !isset( $about ) || !$about ) {?> readonly="readonly"<?php } ?> type="text" name="plugin" id="plugin" value="<?php echo esc_attr( $plugin ); ?>" /></td>
	</tr>
	<tr>
		<td class="label"><label for="url">WordPress URL:</label></td>
		<td class="val"><input type="text" name="url" id="url" value="<?php echo esc_attr( $url ); ?>" /></td>
	</tr>
</table>
<p id="install"><input id="s" type="submit" value="<?php if ( isset( $about ) && $about ) { ?>Get Installation Form<?php } else { ?>Install Plugin<?php } ?> &rarr;" /><?php if ( !isset( $about ) || !$about ) { ?><input type="hidden" name="form" value="1" /><?php } ?></p>
</form>
<?php endif; ?>
</div>
<?php if ( isset( $focus ) ) : ?>
<script>document.getElementById('<?php echo $focus; ?>').focus();</script>
<?php endif; ?>

</body>
</html>