<?php
/*
Plugin Name: ZAWiW Multi Languages
Plugin URI:
Description: Simple multi-language plugin, based on subdomains for each language
Version: 1.0
Author: Sascha Winkelhofer
Author URI:
License: MIT
*/

class Blog
{
	public $blogPrefix;
	public $isFallback;
	public $mainBlogPrefix;
	public $subDomain;
	public $languageShortcut;
	public $mainDomain;
	public function __construct($wpdb)
	{
		// Get Blog Prefix
		$this->blogPrefix = $wpdb->get_blog_prefix();
		$isfallback = $wpdb->get_results('SELECT * FROM zawiw_multi_languages WHERE mainBlogPrefix=\'' . $wpdb->get_blog_prefix() . '\'', ARRAY_A);
		// Is Fallback?
		$this->isFallback = (count($isfallback) > 0 ? 1 : 0);
		// is fallback -> has no fallback
		if($this->isFallback == 1)
		{
			$this->mainBlogPrefix = $this->blogPrefix;
			$this->subDomain = NULL;
			$blogdata = $wpdb->get_results('SELECT languageShortcut FROM zawiw_multi_languages WHERE subBlogPrefix=\'' . $this->blogPrefix . '\' AND mainBlogPrefix=\'' . $this->blogPrefix . '\'', ARRAY_A);
			$this->languageShortcut = $blogdata[0]['languageShortcut'];
			$blogdata = $wpdb->get_results('SELECT option_value FROM '. $this->blogPrefix . 'options WHERE option_name=\'siteurl\'', ARRAY_A);
			$this->mainDomain = preg_replace('/https?:\/\/(.*?)\/$/', '\1', $blogdata[0]['option_value']);
		}
		else
		{
		//Get languageShortcut
			$blogdata = $wpdb->get_results('SELECT * FROM zawiw_multi_languages WHERE subBlogPrefix=\'' . $this->blogPrefix . '\' AND NOT mainBlogPrefix=\'' . $this->blogPrefix . '\'', ARRAY_A);
			$this->languageShortcut = $blogdata[0]['languageShortcut'];
		//Get mainBlogPrefix
			$this->mainBlogPrefix = $blogdata[0]['mainBlogPrefix'];
		//Get subdomain
			$this->subDomain = $blogdata[0]['subDomain'];
			$blogdata = $wpdb->get_results('SELECT option_value FROM '. $this->mainBlogPrefix . 'options WHERE option_name=\'siteurl\'', ARRAY_A);
			$this->mainDomain = preg_replace('/https?:\/\/(.*?)\/$/', '\1', $blogdata[0]['option_value']);
		}
	}
}

// Load Scripts
add_action( 'wp_enqueue_scripts', 'zawiw_multi_languages_queue_script' );
add_action( 'wp_enqueue_scripts', 'zawiw_multi_languages_queue_stylesheet' );
add_action( 'admin_menu', 'menu_insert');
register_activation_hook( dirname( __FILE__ ).'/zawiw-multi-languages.php', 'zawiw_multi_languages_activation');
add_action( 'admin_head', 'zawiw_admin_multi_languages_queue_script' );
add_action( 'admin_head', 'zawiw_admin_multi_languages_queue_stylesheet' );

function zawiw_multi_languages_activation()
{
	global $wpdb;
	zawiw_multi_languages_create_db();
}

function zawiw_multi_languages_create_db()
{
	$creation_query = "CREATE TABLE zawiw_multi_languages (
		id int(20) NOT NULL AUTO_INCREMENT,
		mainBlogPrefix TEXT NOT NULL,
		subBlogPrefix TEXT NOT NULL,
		languageShortcut TEXT NOT NULL,
		subDomain TEXT,
		UNIQUE KEY id (id)
		) DEFAULT CHARACTER SET=utf8;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $creation_query );
}

function menu_insert() {
	add_options_page("ZAWiW Multi Languages", "ZAWiW Multi Languages", "activate_plugins", "zawiw-multi-languages", "multi_lang_settings");
}

function zawiw_multi_languages_queue_script()
{
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'zawiw_multi_languages_script', plugins_url( 'helper.js', __FILE__ ) );
}

function zawiw_multi_languages_queue_stylesheet() {
	wp_enqueue_style( 'zawiw_multi_languages_style', plugins_url( 'style.css', __FILE__ ) );
}

function zawiw_admin_multi_languages_queue_script()
{
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'zawiw_admin_multi_languages_script', plugins_url( 'adminhelper.js', __FILE__ ) );
}

function zawiw_admin_multi_languages_queue_stylesheet()
{
	wp_enqueue_style( 'zawiw_multi_languages_style', plugins_url( 'adminstyle.css', __FILE__ ) );
}

function multi_lang_settings() {
global $wpdb;
$blog = new Blog($wpdb);
//print_r($blog);
if(isset($_POST['zawiw-lang-select']) && isset($_POST['zawiw-multi-lang-select']) && isset($_POST['zawiw-multi-lang-subdomain']) && isset($_POST['zawiw-multi-lang-submit']))
{
	//echo "POST DATA received<br>";
	//$blog = new Blog($wpdb);

	if($_POST['zawiw-multi-lang-select'] == "Default" && !isset($_POST['zawiw-multi-lang-isfallback']))
	{
		echo "<div class='error'>First you have to set one Blog to default-language-Blog</div>";
	}
	else
	{
		if(isset($_POST['zawiw-multi-lang-isfallback']) && $_POST['zawiw-multi-lang-isfallback'] == '1')
		{
			//CASE1: War fallback, bleibt fallback
			if($blog->isFallback == 1)
			{
				if($blog->languageShortcut == NULL)
					$wpdb->insert('zawiw_multi_languages', array('mainBlogPrefix' => $blog->blogPrefix, 'subBlogPrefix' => $blog->blogPrefix, 'languageShortcut' => $_POST['zawiw-lang-select'], 'subDomain' => NULL));
				else
					$wpdb->update('zawiw_multi_languages', array('mainBlogPrefix' => $blog->blogPrefix, 'subBlogPrefix' => $blog->blogPrefix, 'languageShortcut' => $_POST['zawiw-lang-select'], 'subDomain' => NULL), array('mainBlogPrefix' => $blog->blogPrefix));
			}
			//CASE2: Neuer fallback
			else
			{
				$wpdb->delete('zawiw_multi_languages', array('subBlogPrefix' => $blog->blogPrefix));
				$wpdb->insert('zawiw_multi_languages', array('mainBlogPrefix' => $blog->blogPrefix, 'subBlogPrefix' => $blog->blogPrefix, 'languageShortcut' => $_POST['zawiw-lang-select'], 'subDomain' => NULL));
			}
			echo "<div class='success'>Settings succesfully saved</div>";
		}
		else
		{
			//CASE3: War fallback, ist keiner mehr
			if($blog->isFallback == 1)
			{
				$wpdb->delete('zawiw_multi_languages', array('mainBlogPrefix' => $blog->blogPrefix));
				$wpdb->insert('zawiw_multi_languages', array('mainBlogPrefix' => $_POST['zawiw-multi-lang-select'], 'subBlogPrefix' => $blog->blogPrefix, 'languageShortcut' => $_POST['zawiw-lang-select'], 'subDomain' => $_POST['zawiw-multi-lang-subdomain']));
			}
			//CASE3: war kein fallback, ist immer noch keiner
			else
			{
				if($blog->languageShortcut == NULL)
					$wpdb->insert('zawiw_multi_languages', array('mainBlogPrefix' => $_POST['zawiw-multi-lang-select'], 'subBlogPrefix' => $blog->blogPrefix, 'languageShortcut' => $_POST['zawiw-lang-select'], 'subDomain' => $_POST['zawiw-multi-lang-subdomain']));
				else
					$wpdb->update('zawiw_multi_languages', array('mainBlogPrefix' => $_POST['zawiw-multi-lang-select'], 'subBlogPrefix' => $blog->blogPrefix, 'languageShortcut' => $_POST['zawiw-lang-select'], 'subDomain' => $_POST['zawiw-multi-lang-subdomain']), array('subBlogPrefix' => $blog->blogPrefix));
			}
			echo "<div class='success'>Settings succesfully saved</div>";
		
		}
	}


$blog = new Blog($wpdb);

}

?>
<div class="wrap">
	<h2>Einstellungen > ZAWiW Multi Languages</h2>
	<form action="" method="post">
		<p class="clear">
			 <table class="form-table">
                                <tr valign="top">
                                        <th scope="row">
                                                <label for="zawiw-lang-select">
                                                        Sprache für den aktuellen Blog
                                                </label>
                                        </th>
                                        <td>
                                                <select name="zawiw-lang-select">
                                                        <option>Bitte auswählen</option>
                                                        <option value="DE" <?php if($blog->languageShortcut == "DE") echo "selected='true'" ?>>Deutsch</option>
                                                        <option value="EN" <?php if($blog->languageShortcut == "EN") echo "selected='true'" ?>>Englisch</option>
                                                        <option value="FR" <?php if($blog->languageShortcut == "FR") echo "selected='true'" ?>>Französisch</option>
                                                        <option value="IT" <?php if($blog->languageShortcut == "IT") echo "selected='true'" ?>>Italienisch</option>
                                                        <option value="PL" <?php if($blog->languageShortcut == "PL") echo "selected='true'" ?>>Polnisch</option>
                                                        <option value="BG" <?php if($blog->languageShortcut == "BG") echo "selected='true'" ?>>Bulgarisch</option>
                                                        <option value="HU" <?php if($blog->languageShortcut == "HU") echo "selected='true'" ?>>Ungarisch</option>
                                                </select>
                                        </td>   
                                </tr>
                        </table>
		</p>
		<p class="clear">
			<input type="checkbox" id="zawiw-multi-lang-isfallback" name="zawiw-multi-lang-isfallback" <?php if($blog->isFallback) echo "checked='checked'"; ?> value="1">
			<label for="zawiw-multi-lang-isfallback">Diese Seite zur Standard-Seite machen. Sie müssen in ihren anderen Sprachversionen jeweils die aktuelle Seite auswählen, um die Sprachversionen miteinander zu verknüpfen</label>
		</p>
		<p class="clear">
			<table class="form-table" id="select_main">
				<tr valign="top">
					<th scope="row">
						<label for="zawiw-multi-lang-select">
							Haupt-Sprachseite
						</label>
					</th>
					<td>
						<select name="zawiw-multi-lang-select">
							<option>Default</option>
							<?php
								$mainBlogs = $wpdb->get_results('SELECT DISTINCT mainBlogPrefix FROM zawiw_multi_languages', ARRAY_A);
								foreach ($mainBlogs as $mainBlog)
								{
									$blogdata = $wpdb->get_results('SELECT option_value FROM '. $mainBlog['mainBlogPrefix'] . 'options WHERE option_name=\'siteurl\'', ARRAY_A);
									$url = preg_replace('/https?:\/\/(.*?)\/$/', '\1', $blogdata[0]['option_value']);
									if(preg_match('/'.$url.'/', $_SERVER['HTTP_HOST']) === 1)
										echo "<option value=\"".$mainBlog["mainBlogPrefix"]."\" " . (($mainBlog['mainBlogPrefix'] == $blog->mainBlogPrefix) ? "selected='selected'" : "") . ">".$url."</option>";
								}
							?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="zawiw-multi-lang-subdomain">
							Sub-Domain für aktuellen Blog
						</label>
					</th>
					<td>
						<input type="text" name="zawiw-multi-lang-subdomain" id="zawiw-multi-lang-subdomain" value="<?php echo $blog->subDomain; ?>" placeholder="de für de.example.com" />
				</tr>
			</table>
		</p>
		<p class="clear">
			<input class="button-primary" type="submit" name="zawiw-multi-lang-submit" id="zawiw-multi-lang-submit" value="Einstellungen speichern">
		</p>
	</form>
</div>
<?php
}

?>
