<?php
	require_once("../../../wp-load.php");
	global $wpdb;


?>
<li class="language entry-title menu-item">
<?php
	$blogdata = $wpdb->get_results('SELECT * FROM zawiw_multi_languages WHERE subBlogPrefix=\'' . $wpdb->get_blog_prefix() . '\'', ARRAY_A);
	$mainBlogPrefix = $blogdata[0]['mainBlogPrefix'];
	$currentLangShort = $blogdata[0]['languageShortcut'];
	$url = preg_replace('/https?:\/\//', "", $_SERVER['HTTP_REFERER']);
	echo "<a href='http://" . $url . "'>$currentLangShort</a>";
?>

	<ul class="menu">
<?php
	$blogdata = $wpdb->get_results('SELECT * FROM zawiw_multi_languages WHERE mainBlogPrefix=\''. $mainBlogPrefix .'\'', ARRAY_A);
	foreach ($blogdata as $data)
	{

		if($currentLangShort == $data['languageShortcut'])
			continue;
		if($mainBlogPrefix == $data['subBlogPrefix'])
		{
			$url = preg_replace('/https?:\/\/.*?\./', "", $_SERVER['HTTP_REFERER']);
		}
		else
		{
			$url = preg_replace('/https?:\/\//', "", $_SERVER['HTTP_REFERER']);
			$url = $data['subDomain'] . "." . $url;
		}
		echo "<li class='menu-item'><a href='http://" . $url . "'>" . $data['languageShortcut'] . "</a></li>";
	}

?>
	</ul>
</li>