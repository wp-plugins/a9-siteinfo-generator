<?php 
/*
 
 $Id: siteinfo.php $

 SiteInfo Generator for WordPress
 ==============================================================================
 
 This plugin will generate an A9 compatible SiteInfo file for your WordPress blog.
 More information about SiteInfo can be found at
 The A9 Developer Blog:		http://blog.a9.com/blog/
 The A9 SiteInfo Website:	http://a9.com/-/company/help/siteinfo/
 
 Feel free to visit my website under www.arnebrachhold.de!
 
 Thanks to Christian Heindel (www.christian-heindel.de) for testing!
 
 Have fun! 
   Arne
   
   
 Installation:
 ==============================================================================
 If your Blog is in the root directory of your domain (like www.myblog.com/) and your server supports mod_rewrite:

   1. Upload the plugin into your wp-content/plugins directory
   2. Activate the plugin
   3. Rebuild your Permalink structure in "Options" -> "Permalinks"
   4. Customize the settings of this plugin in "Options" -> "SiteInfo"

 If your Blog is NOT located in the root directory (like www.mydomain.com/blog/) or your server doesn't support mod_rewrite:

   1. Upload the plugin into your wp-content/plugins directory
   2. Create a new file named "siteinfo.xml" in your domain root directory (like www.mydomain.com/siteinfo.xml).
   3. Make it writable using FTP or SSH and the CHMOD 777 command. The WordPress Codex has additional information about that.
   4. Activate the plugin
   5. Go into "Options" -> "SiteInfo" and verify if the file system path to the siteinfo.xml is correct.


 Info for WordPress:
 ==============================================================================
 Plugin Name: SiteInfo
 Plugin URI: http://www.arnebrachhold.de/redir/siteinfo-home/
 Description: This plugin will generate an A9 compatible SiteInfo file for your WordPress blog.
 Version: 1.2
 Author: Arne Brachhold
 Author URI: http://www.arnebrachhold.de/
  
 
 Release History:
 ==============================================================================
 2006-06-19     1.0     First release                       
 2006-06-20     1.1     Addes Recent Posts, added fix for empty page titles
 2006-06-21     1.2     Fixed incorrect encodings, added missing translations, corrected date on admin page


 Todo /  Known Problems:
 ==============================================================================
 -
 
 
 License and Copyright:
 ==============================================================================
 Copyright 2005  ARNE BRACHHOLD  (email : himself [a|t] arnebrachhold [dot] de)

 THIS SOFTWARE IS NOT GPL! It is copyright and all rights reserved. I (Arne Brachhold)
 grant you the the following rights:
 
 - You may FREELY distribute this software in an UNMODIFIED state.
 - You may NOT CHARGE for the software or any distribution costs, however, 
   you may charge for technical support for the software, including but not 
   limited to, installation, customisation, and upgrading.
 - I allow derivatives but any major additions and changes must be provided 
   to me so that I can make those changes freely available to the community.
 - Anything else is subject to prior written permission by Arne Brachhold. 
   If you contact me, there is a good chance we will say yes to any reasonable request.
   
 What this mean in practice: This plugin is "free software", in that it is absolutely 
 free to download, free to use and even free to tinker with (although I typically would 
 require any modifications made to it to be clearly indicated to potential users and 
 supplied to me). What I don't want to see, though, is people grabbing a version of 
 WordPress and this plugin, packaging them together and selling them (as they could do, 
 with GPL software). Bottom line is that I am not making money with this, and I don’t 
 see why somebody else should be able to without me having a say first.

 Once again, this type of licensing doesn’t make any difference for 99% of users 
 (it’s free for whatever you need it to do), and shouldn’t stand in the way of the 
 remaining 1% with more specific needs. If you have doubt or questions, contact me.
 I'm very open to any discussion or criticism regarding this format of licensing.  

 This software is provided "as is", without any guarantee of warranty of any kind, 
 nor could I ever be held liable for any damages it could do to your system.

*/

//Enable for dev! Good code doesn't generate any notices...
//error_reporting(E_ALL);
//ini_set("display_errors",1);

define('SIG_OPTIONS_NAME','SIG_OPTIONS_ARRAY');
define('SIG_SINGLETON_NAME','SIG_INSTANCE');
define('SIG_FILE_NAME','siteinfo.xml');

if(!class_exists('SiteInfoGenerator')) {
	class SiteInfoGenerator {
		
		#region Properties
		/**
		* @var string Version of the generator
		* @access private
		*/
		var $_version = '1.2';
		
		/**
		* @var bool True if iniated (loaded, configured)
		* @access private
		*/
		var $_initiated = false;
		
		/**
		* @var array Array with configuration options
		* @access private
		*/
		var $_options = array();
		#endregion

		#region Option + Plugin Settings Store/Load/Init handling
		/**
		* Sets up the default configuration
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		*/
		function InitOptions() {
			
			$this->_options=array();
			$this->_options['use_rewrite'] = true;
			$this->_options['file_path'] = '';
			$this->_options['file_url'] = '';
			$this->_options['welcome'] = true;
			$this->_options['donated'] = false;
			$this->_options['hide_donors'] = false;
			$this->_options['hide_donated'] = false;
			
			$this->_options['include_home'] = true;
			$this->_options['include_search'] = true;
			$this->_options['include_pages'] = true;
			$this->_options['include_archives'] = true;
			$this->_options['include_categories'] = true;
			$this->_options['include_recentposts'] = true;
		}
		
		/**
		* Loads the options from the database
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @return bool true on success
		*/
		function LoadOptions() {
			$this->InitOptions();
			$storedoptions=get_option(SIG_OPTIONS_NAME);
			if($storedoptions && is_array($storedoptions)) {
				foreach($storedoptions AS $k=>$v) {
					$this->_options[$k]=$v;	
				}
			} else {
				$this->DetectDefaultOptions();
				add_option(SIG_OPTIONS_NAME,$this->_options,'Options for the SiteInfo plugin');  //First time use, store default values
			}
		}
		
		/**
		* Returns the path to the blog directory
		* 
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @return string The full path to the blog directory
		*/
		function GetHomePath() {
			$res='';
			//Check if we are in the admin area -> get_home_path() is avaiable
			if(function_exists('get_home_path')) {
				$res = get_home_path();	
			} else {
				$home = get_settings('home');
				$home_path='';
				if ( $home != '' && $home != get_settings('siteurl') ) {
					$home_path = parse_url($home);
					$home_path = $home_path['path'];
					$root = str_replace($_SERVER['PHP_SELF'], '', $_SERVER['SCRIPT_FILENAME']);
					$home_path = trailingslashit($root . $home_path);
				} else {
					$home_path = ABSPATH;
				}
				$res = $home_path;
			}
			return $res;
		}
		
		/**
		* Tries to detect the best settings for this configuration
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		*/
		function DetectDefaultOptions() {
			if(!$this->IsBlogInRoot() || !$this->IsRewriteSupported()) {
				$this->SetOption('use_rewrite',false);
				$rootInfo = $this->DetectDomainRoot();	
				
				$this->SetOption('file_path',$rootInfo['path']);
				$this->SetOption('file_url',$rootInfo['url']);
			} else {
				$this->SetOption('use_rewrite',true);	
				$blogPath = trailingslashit($this->GetHomePath());	
				$blogURL = trailingslashit(get_bloginfo('home'));
				
				$this->SetOption('file_path',$blogPath);
				$this->SetOption('file_url',$blogURL);
			}
		}
		
		/**
		* Schedules the rebuild process with wp_cron if available
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		*/
		function ScheduleFileBuilder() {
			if(function_exists('wp_clear_scheduled_hook')) {
				wp_clear_scheduled_hook('sig_CallFromCron');
				
				if(!$this->IsPathValid() || $this->GetOption('use_rewrite')===true) return;
				
				wp_schedule_event(time()-(60*60*24)-10,'daily','sig_CallFromCron');
			}
		}
		
		/**
		* Tries to detect the root path of this domain
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @return array with the keys url (the root URL), path (the root file path) and att (needs attention)
		*/
		function DetectDomainRoot() {
			$blogPath = trailingslashit($this->GetHomePath());	
			$blogPath = str_replace("\\",'/',$blogPath);
			
			$blogURL = trailingslashit(get_bloginfo('home'));
			
			$filePath = '';
			$fileURL = '';
			$needsAttention = true;
			
			if(!$this->IsBlogInRoot()) {
				$urlInfo = parse_url($blogURL);
				if(substr($blogPath,strlen($blogPath)-strlen($urlInfo['path']))==$urlInfo['path']) {
					$newPath = substr($blogPath,0,strlen($blogPath)-strlen($urlInfo['path']));
					$newPath = @realpath($newPath);
					if(!empty($newPath)) {
						$newPath = trailingslashit($newPath);
						$filePath = $newPath;
						$fileURL = trailingslashit(substr($blogURL,0,strlen($blogURL)-strlen($urlInfo['path'])));
					}
				}
			} else {
				$filePath = $blogPath;
				$fileURL = $blogURL;
				$needsAttention = false;			
			}
			
			return array('url'=>$fileURL, 'path'=>$filePath, 'att'=>$needsAttention);
		}
		
		/**
		* Checks if this blog is installed in the root of this domain
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @return bool True if in root
		*/
		function IsBlogInRoot() {
			$blogURL = get_bloginfo('home');
			$urlInfo = parse_url($blogURL);
			return (empty($urlInfo['path']) || $urlInfo['path']=='/');
		}
		
		/**
		* Checks if mod_rewrite is supported
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @return bool True if supported
		*/
		function IsRewriteSupported() {
			global $wp_rewrite;
			//#type $wp_rewrite WP_Rewrite
			return $wp_rewrite->using_mod_rewrite_permalinks();			
		}
		
		/**
		* Saves the options back to the database
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @return bool true on success
		*/
		function SaveOptions() {
			return update_option(SIG_OPTIONS_NAME,$this->_options);		
		}
		
		/**
		* Returns the option value for the given key
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @param $key string The Configuration Key
		* @return mixed The value
		*/
		function GetOption($key) {
			if(array_key_exists($key,$this->_options)) {
				return $this->_options[$key];	
			} else return null;
		}
		
		/**
		* Sets an option to a new value
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @param $key string The configuration key
		* @param $value mixed The new object
		*/
		function SetOption($key,$value) {		
			$this->_options[$key]=$value;	
		}
		#endregion
		
		#region Contructor / Init / Instance
		/**
		* Initializes a new SiteInfo Generator
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		*/
		function SiteInfoGenerator() {
			//Private constructor...
		}
		
		/**
		* Enables the SiteInfo Generator and registers the WordPress hooks
		*
		* @since 1.0
		* @access public
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		*/
		function Enable() {
			
			if(!isset($GLOBALS[SIG_SINGLETON_NAME])) {			

				$GLOBALS[SIG_SINGLETON_NAME]=new SiteInfoGenerator();

				//Register the siteinfo generator to wordpress...
				add_action('admin_menu', array(&$GLOBALS[SIG_SINGLETON_NAME], 'RegisterAdminPage'));
				
				add_filter('query_vars', array(&$GLOBALS[SIG_SINGLETON_NAME], 'RewriteAddQueryVars'));
				
				add_filter('rewrite_rules_array', array(&$GLOBALS[SIG_SINGLETON_NAME], 'RewriteAddRule'));
				
				add_action('template_redirect', array(&$GLOBALS[SIG_SINGLETON_NAME], 'ExecuteRequest'));
				
				if(!function_exists('wp_clear_scheduled_hook')) {
					add_action('publish_post', array(&$GLOBALS[SIG_SINGLETON_NAME], 'CallFromCron'));
				} else {
					add_action('sig_CallFromCron', array(&$GLOBALS[SIG_SINGLETON_NAME], 'CallFromCron'));	
				}
				
			}
		}
		
		/**
		* Loads up the configuration and language files
		*
		* This method is only called if the SiteInfo file needs to be build or the admin page is displayed.
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		*/
		function Initate() {
			if(!$this->_initiated) {
				
				$this->LoadLanguage();
				
				
				$this->LoadOptions();

				$this->_initiated = true;
			}
		}
		
		/**
		* Returns the instance of the SiteInfo Generator
		*
		* @since 1.0
		* @access public
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @return SiteInfoGenerator The object as a reference
		*/
		function &GetInstance() {
			return $GLOBALS[SIG_SINGLETON_NAME]; 		
		}
		#endregion
		
		#region mod_rewrite Stuff
		/**
		* Registers our query vars so we can redirect to the siteinfo.xml permalink.
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @param array $vars The existing array of query vars
		* @return array The modified array of query vars with our additions.
		*/
		function RewriteAddQueryVars( $vars ) {
			$vars[] = 'request_siteinfo';
			return $vars;
		}
		
		/**
		* Adds our rewrite rules for siteinfo.xml
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @param array $rules The existing array of rewrite rules we're filtering
		* @return array The modified rewrite rules with our additions.
		*/
		function RewriteAddRule( $rules ) {
			global $wp_rewrite;
			$rules['^siteinfo.xml$'] = 'index.php?request_siteinfo=true';
			return $rules;
		}
		
		/**
		* Executes the request with mod_rewrite
		*
		* @since 1.0
		* @access public
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		*/
		function ExecuteRequest() {
			global $wp;
			$wp->parse_request();
			if( $wp->query_vars['request_siteinfo'] ) {
				
				$this->Initate();
				if($this->GetOption('use_rewrite')===true) {			
					header('content-type: text/xml; charset=utf-8');
					echo $this->GenerateContent();
					exit;
				} else return;
			} else return;
		}
		#endregion
		
		#region Version / URL / Path helper functions
		/**
		* Returns the version of the generator
		*
		* @since 1.0
		* @access public
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @return int The version
		*/
		function GetVersion() {
			return $this->_version;
		}
		
		/**
		* Returns the URL of the SiteInfo file
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @param bool $forceAuto Force the return value to the autodetected value.
		* @return The URL of the SiteInfo file
		*/
		function GetSiteInfoUrl() {
			if($this->GetOption('use_rewrite')===true) {
				return trailingslashit(get_bloginfo('home')) . SIG_FILE_NAME;	
			} else {
				return trailingslashit($this->GetOption('file_url')) . SIG_FILE_NAME;
			}
		}
		
		/**
		* Returns the file system path to the SiteInfo file
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @param bool $forceAuto Force the return value to the autodetected value.
		* @return The file system path;
		*/
		function GetSiteInfoPath() {
			if($this->GetOption('use_rewrite')===true) {
				return trailingslashit($this->GetHomePath()) . SIG_FILE_NAME;
			} else {
				return trailingslashit($this->GetOption('file_path')) . SIG_FILE_NAME;	
			}
		}
		
		/**
		* Validates the configured path and returns the status
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @param string $path The given path
		* @return string A status text
		*/
		function ValidatePath($path,$returnMode='text') {
			
			$return ='';
			
			$textMode = ($returnMode=='text'?true:false);
			
			$path = @realpath($path);
			if(empty($path)) {
				if($textMode) $return = __('<b>Error</b>: The path to your SiteInfo file was not found.','siteinfo');
				else $return = false;
			}
			else {
				$path = trailingslashit($path);
				if(file_exists($path . SIG_FILE_NAME)) {
					if(is_writable($path . 'siteinfo.xml')) {
						if($textMode) $return = __('OK, path found, siteinfo.xml exists and is writable.','siteinfo');
						else $return = true;
					} else {
						if($textMode) $return = __('<b>Error</b>, path found, siteinfo.xml exists but is not writable. Please make it writable using CHMOD 777.','siteinfo');
						else $return = false;
					}
				} else {
					if(is_writable($path)) {
						if($textMode) $return = __('OK, path found, siteinfo.xml doesn\'t exist but the directory is writable so WordPress is able to create a new one.','siteinfo');	
						else $return = true;
					} else {
						if($textMode) $return = __('<b>Error</b>, path found, siteinfo.xml doesn\'t exist and the directory is not writable so WordPress is NOT able to create a new one. Please crate a file named siteinfo.xml and make it writable using CHMOD 777.','siteinfo');			
						else $return = false;
					}
				}
			}	
			return $return;
		}	
		
		function IsPathValid() {
			return $this->ValidatePath(dirname($this->GetSiteInfoPath()),'bool');	
		}
			
		#endregion
		
		#region Cron / FileBuilder functions
		/**
		* Rebuilds the SiteInfo file
		*
		* @since 1.0
		* @access public
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @return int The version
		*/
		function CallFromCron() {
			$this->Initate();
			
			if(!$this->IsPathValid() || $this->GetOption('use_rewrite')===true) return;

			$fileName = $this->GetSiteInfoPath();
			$s = $this->GenerateContent();
			$f=fopen($fileName,'w');
			if($f) {
				fwrite($f,$s);
				fclose($f);
			}	
		}
		#endregion
		
		#region SiteInfo Generator
		/**
		* Converts text to an valid xml value
		*
		* @since 1.2
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @param $text string The text
		* @return string The encoded value
		*/
		function EncodeText($text) {
			return ent2ncr(convert_chars(strip_tags($text)));
		}
		
		/**
		* Generates the SiteInfo content
		*
		* @since 1.0
		* @access public
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @return string The content
		*/
		function GenerateContent() {
			global $wpdb;

			$s = '<?xml version="1.0"?>';
			$s .= '<siteinfo xmlns="http://a9.com/-/spec/siteinfo/1.0/">'. "\n";
			
			$s .='<!-- generator="wordpress/' . get_bloginfo_rss('version') . '" -->' . "\n";
			$s .='<!-- generator-url="http://www.arnebrachhold.de" siteinfo-generator-version="' . $this->GetVersion() . '" -->' . "\n";
			$s .='<!-- generated-on="' . $this->EncodeText(date(get_option('date_format') . ' ' . get_option('time_format'))) . '"-->' . "\n";
			
				
			$s .='<webmenu>';
			$s .='<version>1</version>';
			$s .='<tooltip>' .  get_bloginfo_rss('description') .'</tooltip>';
			$s .='<name>' . get_bloginfo_rss('name') . '</name>';
			$s .='<menu>';
			
			//Homepage
			if($this->GetOption('include_home')) {
				$s .='<item>';
				$s .='<text>' . $this->EncodeText(__('Home','siteinfo')) . '</text>';
				$s .='<url>' . get_bloginfo_rss('home') . '</url>';
				$s .='</item>';
			}
			
			//Search
			if($this->GetOption('include_search')) {
				$s .='<item>';
				$s .='<text>' . $this->EncodeText(__('Search','siteinfo')) . '</text>';
				$s .='<textSearch>' . $this->EncodeText(__('Search for [[:SEARCHTERMS:]]','siteinfo')) . '</textSearch>';
				$s .='<urlSearch>' . get_bloginfo_rss('home') . '?s=[[:SEARCHTERMS:]]</urlSearch>';
				$s .='</item>';
			}

			//Pages
			if($this->GetOption('include_pages')) {
				$pages = &get_pages('sort_order=ASC&sort_column=post_title&hierarchical=0');
				if(count($pages)>0) {
					$s.='<separator />';
					for($i=0; $i<count($pages); $i++) {
						$page = &$pages[$i];
						$title = $this->EncodeText($page->post_title);
						if(empty($title)) continue;
						$s .='<item>';
						$s .='<text>' . $title  .'</text>';
						$s .='<url>' . get_page_link($page->ID) . '</url>';
						$s .='</item>';	
					}
				}
			}
			
			
			//Archives
			$now = current_time('mysql');
			$arcresults = null;
			if($this->GetOption('include_archives')) $arcresults = $wpdb->get_results('SELECT DISTINCT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, MAX(post_date) as last_mod, count(ID) as posts FROM ' . $wpdb->posts . ' WHERE post_date < \'' . $now . '\' AND post_status = \'publish\' GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC');
			$catsRes = null;
			if($this->GetOption('include_categories')) $catsRes=$wpdb->get_results('SELECT cat_ID AS ID, MAX(post_modified) AS last_mod, cat_name FROM `' . $wpdb->posts . '` p LEFT JOIN `' . $wpdb->post2cat . '` pc ON p.ID = pc.post_id LEFT JOIN `' . $wpdb->categories . '` c ON pc.category_id = c.cat_ID WHERE post_status = \'publish\' GROUP BY cat_ID ORDER BY cat_name');
			
			if ($arcresults || $catsRes || $this->GetOption('include_recentposts')) {
				$s.='<separator />';
			}
			
			if($this->GetOption('include_recentposts')) {
				query_posts('showposts=10'); 
				$s.='<item>';
       			$s.='<text>' . $this->EncodeText(__('Recent Posts','siteinfo')) . '</text>';
				$s.='<menu>';
				global $post;
				while (have_posts()) {
					the_post();
					$title = $this->EncodeText($post->post_title);
					
					if(empty($title)) continue;
					$s .='<item>';
					$s .='<text>' . $title  .'</text>';
					$s .='<url>' . get_permalink($post->ID) . '</url>';
					$s .='</item>';									
				}
				$s.='</menu>';
				$s.='</item>';	
			}
			
			if($catsRes) {
				
				$s.='<item>';
       			$s.='<text>' . $this->EncodeText(__('Categories','siteinfo')) . '</text>';
				$s.='<menu>';
				foreach($catsRes as $cat) {
					if($cat && $cat->ID && $cat->ID>0) {
						$s .='<item>';
						$s .='<text>' . $this->EncodeText($cat->cat_name)  .'</text>';
						$s .='<url>' . get_category_link($cat->ID) . '</url>';
						$s .='</item>';
					}
				}
				$s.='</menu>';
				$s.='</item>';	
			}
			
			if ($arcresults) {
				global $month;
				
				$s.='<item>';
       			$s.='<text>' . $this->EncodeText(__('Archives','siteinfo')) . '</text>';
				$s.='<menu>';
				$cYear='';
				$count = count($arcresults);
				for($i=0; $i<$count; $i++) {
					$arcresult = $arcresults[$i];
					if($i==0 || $arcresult->year != $arcresults[$i-1]->year) {
						$s .='<item>';
						$s .='<text>' . $this->EncodeText($arcresult->year)  .'</text>';
						$s.='<menu>';						
					}					
					$url  = get_month_link($arcresult->year,   $arcresult->month);
					$s .='<item>';
					$s .='<text>' . $this->EncodeText(sprintf('%s %d', $month[zeroise($arcresult->month,2)], $arcresult->year))  .'</text>';
					$s .='<url>' . $url . '</url>';
					$s .='</item>';
					
					if($i==($count-1) || $arcresult->year != $arcresults[$i+1]->year) {
						$s.='</menu>';	
						$s .='</item>';
					}
				}
				$s.='</menu>';
				$s.='</item>';
			}
			
			$s .='</menu>';
			$s .= '</webmenu>';
			$s .= '</siteinfo>';
			return $s;		
		}
		#endregion
		
		#region Configuration Page functions
		/**
		* Adds the options page in the admin menu
		*
		* @since 1.0
		* @access public
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		*/
		function RegisterAdminPage() {
			if (function_exists('add_options_page')) {
				add_options_page(__('SiteInfo Generator','siteinfo'), __('SiteInfo','siteinfo'), 8, basename(__FILE__), array(&$this,'HtmlShowOptionsPage'));	
			}
		}
		
		/**
		* Returns a HTML CheckBox
		*
		* @since 1.0
		* @access private
		* @param $inputName string The name of the input field
		* @param $isChecked bool True if this CheckBox is checked
		* @param $labelText string The label text of this CheckBox
		* @param $isDisabled bool True if this CheckBox is disabled
		* @param $outerHTML string HTML after the CheckBox and Label
		* @param $helpText string A small text which will be displayed as an abbr. question mark
		* @param $innerHTML string HTML in the CheckBox
		* @return string The HTML code
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		*/
		function HtmlGetCheckBox($inputName,$isChecked,$labelText,$isDisabled=false,$outerHTML='',$helpText='',$innerHTML='') {
			return '<li><label for="' . $inputName . '"><input type="checkbox" id="' . $inputName . '" name="' . $inputName . '" ' . ($isChecked?'checked="checked"':'') . ' ' . ($isDisabled?'disabled="disabled"':'') . ' ' . $innerHTML . ' /> ' . $labelText .  (!empty($helpText)?' <abbr onclick="alert(this.title);" title="' . $helpText . '">[?]</abbr>':'') . '</label>' . $outerHTML . '</li>';			
		}
		
		/**
		* Returns a HTML Text Input Field
		*
		* @since 1.0
		* @access private
		* @param $inputName string The name of the input TextField
		* @param $value string The value
		* @param $labelText string The label text of this TextField
		* @param $isDisabled bool True if this TextField is disabled
		* @param $outerHTML string HTML after the TextField and Label
		* @param $helpText string A small text which will be displayed as an abbr. question mark
		* @param $innerHTML string HTML in the TextField
		* @return string The HTML code
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		*/
		function HtmlGetTextBox($inputName,$value,$labelText,$isDisabled=false,$outerHTML='',$helpText='',$innerHTML='') {
			return '<li><label for="' . $inputName . '">' . $labelText .  (!empty($helpText)?' <abbr onclick="alert(this.title);" title="' . $helpText . '">[?]</abbr>':'') . '<br /><input type="text" id="' . $inputName . '" name="' . $inputName . '" value="' . $value. '" ' . ($isDisabled?'disabled="disabled"':'') . ' ' . $innerHTML . ' /></label>' . $outerHTML . '</li>';			
		}
		
		
		/**
		* Returns the URL of an specified resource
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @param string $resourceID The resource ID
		* @return The URL to the resource
		*/
		function GetResourceLinkAdmin($resourceID) {
			return trailingslashit(get_bloginfo('siteurl')) . 'wp-admin/?res=' . $resourceID;
		}
		
		/**
		* Returns the URL of an specified resource (admin mode)
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @param string $resourceID The resource ID
		* @return The URL to the resource
		*/
		function GetResourceLink($resourceID) {
			return trailingslashit(get_bloginfo('siteurl')) . '?res=' . $resourceID;
		}
		
		/**
		* Load the language files of this plugin
		*
		* @since 1.0
		* @access private
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		*/
		function LoadLanguage() {
			//Load language files
			$sep = (strpos(__FILE__,"\\")===false?'/':"\\");
			$pluginDir = dirname(substr(__FILE__,strpos(__FILE__,'wp-content' . $sep . 'plugins')));
			load_plugin_textdomain('siteinfo',$pluginDir);	
		}
				
		/**
		* Displays the configuration page
		*
		* @since 1.0
		* @access public
		* @author Arne Brachhold <himself [at] arnebrachhold [dot] de>
		* @param string $resourceID The resource ID
		* @return string The HTML
		*/
		function HtmlShowOptionsPage() {
			global $wp_version;
			$this->Initate();
			
		
			if(isset($_GET['sig_hidewelcome']) && $_GET['sig_hidewelcome']==true) {
				$this->SetOption('welcome',false);		
				$this->SaveOptions();
			}
			
			
			if($this->GetOption('welcome')===true) {
				$msg=''; 
				if(!$this->IsBlogInRoot()) {
					$msg = __('Your blog is not installed in the top level of your domain. Please verify the path to your domain root below and enable the plugin.','siteinfo');
				} else {
					if(!$this->IsRewriteSupported()) {
						$msg = __('It looks like your server doesn\'t support mod_rewrite, so we need to create a static file. Please verify the path to your blog directory below.','siteinfo');					
					} else {
						$msg = str_replace('%s','options-permalink.php',__('Please <a href="%s">update your permalink structure</a> after activating this plugin!','siteinfo'));
					}
				}			
			?>
				<div class="updated">
					<strong><p><?php echo $msg; ?> <a href="<?php echo $_SERVER['PHP_SELF'] . "?page=" . basename(__FILE__) . "&sig_hidewelcome=true"; ?>"><small style="font-weight:normal;"><?php _e('Hide this notice', 'siteinfo'); ?></small></a></p></strong>
				</div>
			<?php 
			}
			
			if(isset($_GET['sig_donated'])) {
				$this->SetOption('donated',true);
				$this->SaveOptions();	
			}
			if(isset($_GET['sig_hidedonate'])) {
				$this->SetOption('hide_donated',true);
				$this->SaveOptions();	
			}
			
			if(isset($_GET['sig_hidedonors'])) {
				$this->SetOption('hide_donors',true);
				$this->SaveOptions();	
			}
			
			
			if(isset($_GET['sig_donated']) || ($this->GetOption('donated')===true && $this->GetOption('hide_donated')!==true)) {
				?>
				<div class="updated">
					<strong><p><?php _e('Thank you very much for the donation. You help me to continue support and development of this plugin and other free software!','siteinfo'); ?> <a href="<?php echo $_SERVER['PHP_SELF'] . "?page=" . basename(__FILE__) . "&sig_hidedonate=true"; ?>"><small style="font-weight:normal;"><?php _e('Hide this notice', 'siteinfo'); ?></small></a></p></strong>
				</div>
				<?php	
			}
			
			if(isset($_POST['sig_update_settings'])) {
				$msg = '';
				$used_rewrite = $this->GetOption('use_rewrite');
				$use_rewrite = !empty($_POST['sig_rewrite']);
				
				$file_path = $_POST['sig_file_path'];
				if(strpos($file_path,"\\")!==false){
					$file_path=stripslashes($file_path);
				}
					
				$file_url = $_POST['sig_file_url'];
				
				$this->SetOption('use_rewrite',$use_rewrite);
				
				if(!empty($file_path)) $this->SetOption('file_path',$file_path);
				if(!empty($file_url)) $this->SetOption('file_url',$file_url);
				
				$incl_home = !empty($_POST['sig_include_home']);
				$incl_search = !empty($_POST['sig_include_search']);
				$incl_pages = !empty($_POST['sig_include_pages']);
				$incl_recentposts= !empty($_POST['sig_include_recentposts']);
				$incl_categories = !empty($_POST['sig_include_categories']);
				$incl_archives = !empty($_POST['sig_include_archives']);
				
				$this->SetOption('include_home',$incl_home);
				$this->SetOption('include_search',$incl_search);
				$this->SetOption('include_pages',$incl_pages);
				$this->SetOption('include_recentposts',$incl_recentposts);
				$this->SetOption('include_categories',$incl_categories);
				$this->SetOption('include_archives',$incl_archives);
				
				
				$this->SaveOptions();
				
				$this->ScheduleFileBuilder();
				$this->CallFromCron();
				
				if($used_rewrite==false && $use_rewrite) {
					$msg = str_replace('%s','options-permalink.php',__('Please <a href="%s">update your permalink structure</a> after activating mod_rewite!','siteinfo'));	
				}
				
				if(!empty($msg)) {
				?>
				<div class="updated">
					<strong><p><?php echo $msg; ?></p></strong>
				</div>
				<?php
				}
				
			}
			
			?>
			
			<div class="wrap" id="sig_div">
				<h2><?php _e('SiteInfo for WordPress', 'siteinfo'); echo " " . $this->GetVersion() ?> </h2>
								
				<script type="text/javascript" src="../wp-includes/js/tw-sack.js"></script>
				<script type="text/javascript" src="../wp-includes/js/dbx.js"></script>
				<script type="text/javascript" src="<?php echo $this->GetResourceLinkAdmin('{A21975BC-8A52-4b85-BAEE-57DDA501E0C0}'); ?>"></script>
				
				<form method="post" action="<?php echo $_SERVER['PHP_SELF'] . "?page=" . basename(__FILE__); ?>">

					<div id="poststuff">
						<div id="moremeta">
							<div id="grabit" class="dbx-group">
								<fieldset id="sig_pluginfo" class="dbx-box">
									<h3 class="dbx-handle"><?php _e('Plugin Info','siteinfo'); ?></h3>
									<div class="dbx-content">
										<a href="http://www.arnebrachhold.de/"><?php _e('Author Website','siteinfo'); ?></a><br />
										<a href="http://www.arnebrachhold.de/redir/siteinfo-home/"><?php _e('Plugin Homepage','siteinfo'); ?></a><br />
										<a href="http://www.arnebrachhold.de/redir/siteinfo-support/"><?php _e('Support Forum','siteinfo'); ?></a><br />
										<a href="http://www.arnebrachhold.de/redir/siteinfo-bugs/"><?php _e('Report Bug','siteinfo'); ?></a><br />
									</div>
								</fieldset>
								<fieldset id="sig_sires" class="dbx-box">
									<h3 class="dbx-handle"><?php _e('SiteInfo Resources:','siteinfo'); ?></h3>
									<div class="dbx-content">
										<a href="http://www.arnebrachhold.de/redir/siteinfo-a9blog"><?php _e('A9 Developer Blog','siteinfo'); ?></a><br />
										<a href="http://www.arnebrachhold.de/redir/siteinfo-a9web"><?php _e('A9 SiteInfo Website','siteinfo'); ?></a><br />
										<br />
										<a href="http://www.arnebrachhold.de/redir/siteinfo-ffaddon"><?php _e('SiteInfo for Firefox','siteinfo'); ?></a><br />
										<a href="http://www.arnebrachhold.de/redir/siteinfo-a9tool"><?php _e('Full A9 Toolbar','siteinfo'); ?></a>
									</div>
								</fieldset>
								<fieldset id="sig_pluginfo" class="dbx-box">
									<h3 class="dbx-handle"><?php _e('Donations:','siteinfo'); ?></h3>
									<div class="dbx-content">
										<?php if($this->GetOption('hide_donors')!==true) { ?>
										<iframe border="0" frameborder="0" scrolling="no" allowtransparency="yes" style="width:100%; height:60px;" src="http://www.arnebrachhold.de/redir/siteinfo-donorlist">
											List of the donors
										</iframe><br />
										<a href="<?php echo $_SERVER['PHP_SELF'] . "?page=" . basename(__FILE__) . "&sig_hidedonors=true"; ?>"><small><?php _e('Hide this list','siteinfo'); ?></small></a><br /><br />
										<?php } ?>
										<a href="javascript:document.getElementById('sig_donate_form').submit();" style="text-decoration:none; border:0;"><img style="border:0; padding:0; margin:0;" src="<?php echo $this->GetResourceLink('{6E89EFD4-A853-4321-B5CF-3E36C60B268D}'); ?>" border="0" alt="Make payments with PayPal - it's fast, free and secure!" /></a>
									</div>
								</fieldset>
							</div>
						</div>

						<div id="advancedstuff" class="dbx-group" >
							<fieldset id="sig_status" class="dbx-box">
								<h3 class="dbx-handle"><?php _e('Status', 'siteinfo') ?></h3>
								<div class="dbx-content">
									<ul>
									<?php
										
										if($this->GetOption('use_rewrite')===true) echo "<li>" . __('The plugin is using <b>mod_rewrite</b>','siteinfo') . "</li>";
										else echo "<li>" . __('The plugin is using <b>a static file</b>','siteinfo') . "</li>";
										
										echo "<li>" . str_replace("%s",'<a href="' .  $this->GetSiteInfoUrl() . '">' . $this->GetSiteInfoUrl() . '</a>',__('The URL of your SiteInfo file is %s','siteinfo')) . "</li>";
										
										if($this->GetOption('use_rewrite')!==true) {
											echo "<li>" . str_replace("%s",$this->GetSiteInfoPath(),__('The path to your SiteInfo file is %s','siteinfo')) . "</li>";
											if(file_exists($this->GetSiteInfoPath())) {
												$mtime = filemtime($this->GetSiteInfoPath());
												$date = '';
												
												//Workaround for http://trac.wordpress.org/ticket/2774
												if(version_compare(PHP_VERSION, '4.3', '<')===true && $wp_version=='2.0.3') $date = date(get_option('date_format'),$mtime);
												else $date = date_i18n(get_option('date_format'),$mtime);
												
												echo "<li>" . str_replace("%s",'<b>' . $date . '</b>',__('Your SiteInfo was rebuilt on %s','siteinfo')) . "</li>";	
											} 
											echo "<li>" . __('File status:','siteinfo') . ' ' . $this->ValidatePath(dirname($this->GetSiteInfoPath())) . "</li>";
										} else {
											if(file_exists($this->GetSiteInfoPath())) {
												echo "<li>" . str_replace("%s",$this->GetSiteInfoPath(),__('Please delete the <abbr title="%s">' . SIG_FILE_NAME . '</abbr> file if you want to use mod_rewrite.','siteinfo')) . "</li>";	
											}	
										}

									?>
									</ul>
								</div>
							</fieldset>
							
							<fieldset id="sig_basic_options" class="dbx-box">
								<h3 class="dbx-handle"><?php _e('Basic Options', 'siteinfo') ?></h3>
								<div class="dbx-content">
								<?php
										
									$formPath = '<ul id="sig_file_section">'
									. $this->HtmlGetTextBox('sig_file_path',$this->GetOption('file_path'),__('Full path to the domain root', 'siteinfo'),'',' <a href="javascript:sig_checkPath();">' . __('Verify path','siteinfo') . '</a> <a href="javascript:sig_detectPath();">' . __('Autodetect','siteinfo') . '</a> <br /><span id="sig_path_check" xclass="fade"></span>','',' style="width:50%"')
									. $this->HtmlGetTextBox('sig_file_url',$this->GetOption('file_url'),__('Full URL to the domain root', 'siteinfo'),'','','','style="width:50%"')
									. '</ul>';
										
									if(!$this->IsRewriteSupported() || !$this->IsBlogInRoot()) {
										
										if(!$this->IsRewriteSupported()) {
											echo '<p>' . __('Your server does not support mod_rewrite so this plugin will generate a static file every time you publish a post. Please specify the path to your domain root below.','siteinfo') . '</p>';
										} elseif (!$this->IsBlogInRoot()) {
											echo '<p>' . __('Your blog is not located in your domain root so this plugin will generate a static file every time you publish a post. Please specify the path to your domain root below.','siteinfo') . '</p>';
										}
										echo $formPath;																		
									} else {
										echo '<ul>';
										echo $this->HtmlGetCheckBox('sig_rewrite',$this->GetOption('use_rewrite'),__('Use mod_rewrite to generate the SiteInfo file', 'siteinfo'),(!$this->IsBlogInRoot() || !$this->IsRewriteSupported()),'','','onclick="sig_enablePath(this.checked);"'); 
										echo '</ul>';
										echo '<div id="sig_path_options" ' . ($this->GetOption('use_rewrite')?'style="display:none;"':'') . '>';
										echo $formPath;
										echo '</div>';
									}
								
								?>
								</div>
							</fieldset>
					
							<fieldset id="sig_content_options" class="dbx-box">
								<h3 class="dbx-handle"><?php _e('WebMenu Content', 'siteinfo') ?></h3>
								<div class="dbx-content">
									
									<p><?php _e('Please choose what you want to include in your WebMenu. <b>Note that you need to close and reopen your browser to see the changes because the A9 Toolbar reads the file only once per session.</b>', 'siteinfo') ?></p>
									<ul>
										<?php 
										echo $this->HtmlGetCheckBox('sig_include_home',$this->GetOption('include_home'),__('Home', 'siteinfo'));
										echo $this->HtmlGetCheckBox('sig_include_search',$this->GetOption('include_search'),__('Search', 'siteinfo'));
										echo $this->HtmlGetCheckBox('sig_include_pages',$this->GetOption('include_pages'),__('Pages', 'siteinfo'));
										echo $this->HtmlGetCheckBox('sig_include_recentposts',$this->GetOption('include_recentposts'),__('Recent Posts', 'siteinfo'));
										echo $this->HtmlGetCheckBox('sig_include_categories',$this->GetOption('include_categories'),__('Categories', 'siteinfo'));
										echo $this->HtmlGetCheckBox('sig_include_archives',$this->GetOption('include_archives'),__('Archives', 'siteinfo'));
									?></ul>

									</div>
							</fieldset>
						</div> 
						<p class="submit">
							<input type="submit" name="sig_update_settings" value="<?php _e('Update options', 'siteinfo'); ?>" />
						</p>
					</div>
				</form>
			</div>
			<form style="padding:0; margin-top:6px;"  action="https://www.paypal.com/cgi-bin/webscr" method="post" id="sig_donate_form">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="donate@arnebrachhold.de">
				<input type="hidden" name="item_name" value="SiteInfo Generator for WordPress">
				<input type="hidden" name="no_shipping" value="1">
				<input type="hidden" name="return" value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?page=" . basename(__FILE__); ?>&sig_donated=true">
				<input type="hidden" name="item_number" value="0001">
				<input type="hidden" name="currency_code" value="USD">
				<input type="hidden" name="bn" value="PP-BuyNowBF">
				<input type="hidden" name="rm" value="2">
				<input type="hidden" name="on0" value="Your Website" />
				<input type="hidden" name="os0" value="<?php echo get_bloginfo("home"); ?>"/>
			</form>
			<?php
			
		}
		#endregion
	}

	#region Embedded resources and scripts
	if((isset($_GET['res']) && !empty($_GET['res']) || isset($_POST['res']) && !empty($_POST['res']))) {
		
		
		//AJAX Directory Check
		if(!empty($_POST['res']) && ($_POST['res']=='{2FECC637-A2FE-4a1e-8967-ABC331223EA6}' || $_POST['res']=='{56238D44-EC70-4c46-BDCE-466D3D724ADF}')) {
			
			//2.0.3 introduced check_ajax_referer()
			if(version_compare($wp_version,'2.0.3','>=')!==true) {
				die('Please upgrade to WordPress 2.0.3 to use this feature. You are currently using $wp_version. Sorry!');	
			}
			
			
			/*
			 AJAX Security:
			 - You have to be logged in as an Admin
			 - You have to provide the WordPress cookies
			 - You have to provide a hashed key, based on the DB username
			*/
			
			
			//check_ajax_referer is in pluggable functions
			require_once('admin-functions.php');
			require_once('admin-db.php');
			require_once (ABSPATH . WPINC . '/pluggable-functions.php');
			
			if(!function_exists('check_ajax_referer') || !function_exists('current_user_can')) die('This feature is not available for this WordPress version.');
			
			//Check for WP cookie
			check_ajax_referer();
			
			//Secret key, based on DB username (hashed)
			if(!isset($_POST['key']) || $_POST['key']!=md5(DB_USER . "sig")) die('Security error: Invalid key.');
			
			if (current_user_can('administrator')!==true) die('You have no permission to perform this check.');
			
			if(!function_exists('sig_ExitNow')) {
				function sig_ExitNow() { exit; }
			}
			
			//Don't perform any other shutdown scripts
			add_action('shutdown', 'sig_ExitNow', -1);
			
			$return = '';
			
			if($_POST['res']=='{56238D44-EC70-4c46-BDCE-466D3D724ADF}') {
				SiteInfoGenerator::Enable();
				$inst = &SiteInfoGenerator::GetInstance();	
				$info = $inst->DetectDomainRoot();
				$return = $info['path'] . ";;;" . $info['url'];
				
			} else {
				
				SiteInfoGenerator::LoadLanguage();
								
				$path = rawurldecode($_POST['path']);
				
				$return = SiteInfoGenerator::ValidatePath($path);
			}
			
			die( (string) $return );
		}
		
		//Paypal Image
		if($_GET['res']=='{6E89EFD4-A853-4321-B5CF-3E36C60B268D}') {
			header('Content-Type: image/gif');
			echo base64_decode('R0lGODlhPgAfAMQAADJXgRVBcMHN2k5uktHV2oiguLTE08jT3tfh6QAhWPb4+kRli+fs8KS3yp2wxOPx93CMqeHn'
			. '7VZ2mHyWsef1+u7x9GiDoiRIc7u7u+np6QY0ZiI2TZSnvQAAAOr3/P///yH5BAAAAAAALAAAAAA+AB8AAAX/INdwZGmeaKquLFlYQ+HNd'
			. 'G3feK7vNlB8wKBwSCwaj8jhxfK5PZ5Qng4a3T0K2AJn9sksiYKAZqwJLBxJIuNCHgMmFaNAAwAEAMAK4WubXAyABg4WGhYzFIg8BgEOgQ'
			. '0QdxQeiJIzEwAVCgMSM3pfQwtMQw2FC3UAMQOqAxAMEqp1RAgBm6cLHJpMCgAceXtMNQ8BDR8MCwMIHxUXARZZEmOsA4UBEBAXEkAWCwZ'
			. 'A085YFmYXvQgaAp2/QwcBBB8GCQloFQE/QgXyHwjxxBEBvQw0JIDgbcAQA2MOfHAQIIgnYDQ4XFCwcAnFA4UkXADg7sDADxE0LPCWIJkA'
			. 'VBE+/ygwY2FjNwYBAlCEQePhEBiyACTwYeAAgw8cEhhcJuBDgwQX4gipYAHphJ4/MRr84MOhuhoXfigQIOBRgAT2DnT7IGGnAgXE1ggFc'
			. 'kCAgQIXEkigGKFBHAdgQQYwUFNdkJBFMZY5NvHDhLW7FgT4qS3AhQnK2NyBluxogDhNuy1K6YvPjAaXgV44gMBDAzwfDjiIM4uhwg9zCg'
			. 'Qoas4AAgpzUkbgwJnZz0s1bAaBsICBq6mpNaxWcMBng9EaiO2yMAcBA7gOq2WKYIABRgDGFxC06tnDgjYTakDQcIENmQUSAFCcEADBejK'
			. 'caJBy3KZ/g+B+AUEAV1wpFQQBDjggQNZbB0yggTvmlEOgAJwFgUCCPU1IIEXkQSTFDbNkVQAAC1Ty4YnCpVEEB6eQmIyKMHbm4Yk01qhD'
			. 'ijHmqGMRNtno4499fYJEB0R2EESRQBiZ5JFEJlmkkU8m0eMOHdBQZZUzXInlllZ2mSWKAR6h5AdQHknmmUo+OeaaTUp5FZVdYunBlVly+'
			. 'eWdc4IppJhIOpmmmWcuKSiZfR4xJZCIngjEBUNl4OijkEYq6aSUVmppBggYsFGmGHTq6aeghirqqKSW2ikHG7S3waqsturqq7DGKuusro'
			. 'YAADs=');
			exit;
		}	
		
		if($_GET['res']=='{A21975BC-8A52-4b85-BAEE-57DDA501E0C0}') {
			?>
			function sig_enablePath(enabled) {
				document.getElementById('sig_path_options').style.display = (!enabled?'':'none');
				document.getElementById('sig_file_path').disabled = enabled;
				document.getElementById('sig_file_url').disabled = enabled;
			}
			
			function sig_checkPathLoading() {
				var p = document.getElementById('sig_path_check'); 
				p.innerHTML = '<?php echo addslashes(__('Sending Data...','siteinfo')); ?>';
			}

			function sig_checkPathLoaded() {
				var p = document.getElementById('sig_path_check'); 
				p.innerHTML = '<?php echo addslashes(__('Data Sent...','siteinfo')); ?>';
			}

			function sig_checkPathInteractive() {
				var p = document.getElementById('sig_path_check'); 
				p.innerHTML = '<?php echo addslashes(__('Processing Request...','siteinfo')); ?>';
			}
			
			function sig_checkPathComplete() {
				var p = document.getElementById('sig_path_check'); 
				p.innerHTML = ajaxPath.response;
				Fat.fade_element(p.id);
			}
			
			function sig_detectPathComplete() {
				var resp = ajaxPath.response;
				if(resp) {
					resp=resp.split(';;;');
					var path = resp[0];
					var url = resp[1];	
					
					document.getElementById('sig_file_path').value = (path?path:'');						
					document.getElementById('sig_file_url').value = (url?url:'');
				}
				
				var p = document.getElementById('sig_path_check'); 
				p.innerHTML = '<?php echo addslashes(__('Done.','siteinfo')); ?>';
				Fat.fade_element(p.id);
				
				
			}
			
			var ajaxPath = new sack();
			
			function sig_checkPath() {
				var path = document.getElementById('sig_file_path').value;
				var p = document.getElementById('sig_path_check'); 
				if(path=='') {
					p.innerHTML = '<?php echo addslashes(__('Empty directory','siteinfo')); ?>';
					return;
				}

				ajaxPath.requestFile = '<?php echo $_SERVER['PHP_SELF'] ?>';
				ajaxPath.method = 'POST';
				ajaxPath.onLoading = sig_checkPathLoading;
				ajaxPath.onLoaded = sig_checkPathLoaded;
				ajaxPath.onInteractive = sig_checkPathInteractive;
				ajaxPath.onCompletion = sig_checkPathComplete;
				ajaxPath.runAJAX('key=<?php echo md5(DB_USER . "sig"); ?>&cookie=' + encodeURIComponent(document.cookie) + '&path=' + encodeURIComponent(path) + '&res={2FECC637-A2FE-4a1e-8967-ABC331223EA6}&page='+ encodeURIComponent("<?php echo basename(__FILE__); ?>"));
			}
			
			function sig_detectPath() {
				var p = document.getElementById('sig_path_check'); 

				ajaxPath.requestFile = '<?php echo $_SERVER['PHP_SELF'] ?>';
				ajaxPath.method = 'POST';
				ajaxPath.onLoading = sig_checkPathLoading;
				ajaxPath.onLoaded = sig_checkPathLoaded;
				ajaxPath.onInteractive = sig_checkPathInteractive;
				ajaxPath.onCompletion = sig_detectPathComplete;
				ajaxPath.runAJAX('key=<?php echo md5(DB_USER . "sig"); ?>&cookie=' + encodeURIComponent(document.cookie) + '&res={56238D44-EC70-4c46-BDCE-466D3D724ADF}&page='+ encodeURIComponent("<?php echo basename(__FILE__); ?>"));
			}
			
			addLoadEvent( function() {
				var manager = new dbxManager('sig_meta');					
				var advanced = new dbxGroup(
					'advancedstuff', 		// container ID [/-_a-zA-Z0-9/]
					'vertical', 		// orientation ['vertical'|'horizontal']
					'10', 			// drag threshold ['n' pixels]
					'yes',			// restrict drag movement to container axis ['yes'|'no']
					'10', 			// animate re-ordering [frames per transition, or '0' for no effect]
					'yes', 			// include open/close toggle buttons ['yes'|'no']
					'open', 		// default state ['open'|'closed']
					'open', 		// word for "open", as in "open this box"
					'close', 		// word for "close", as in "close this box"
					'click-down and drag to move this box', // sentence for "move this box" by mouse
					'click to %toggle% this box', // pattern-match sentence for "(open|close) this box" by mouse
					'use the arrow keys to move this box', // sentence for "move this box" by keyboard
					', or press the enter key to %toggle% it',  // pattern-match sentence-fragment for "(open|close) this box" by keyboard
					'%mytitle%  [%dbxtitle%]' // pattern-match syntax for title-attribute conflicts
					);
					
				//create new docking boxes group
				var meta = new dbxGroup(
					'grabit', 		// container ID [/-_a-zA-Z0-9/]
					'vertical', 	// orientation ['vertical'|'horizontal']
					'10', 			// drag threshold ['n' pixels]
					'no',			// restrict drag movement to container axis ['yes'|'no']
					'10', 			// animate re-ordering [frames per transition, or '0' for no effect]
					'yes', 			// include open/close toggle buttons ['yes'|'no']
					'open', 		// default state ['open'|'closed']
					'open', 		// word for "open", as in "open this box"
					'close', 		// word for "close", as in "close this box"
					'click-down and drag to move this box', // sentence for "move this box" by mouse
					'click to %toggle% this box', // pattern-match sentence for "(open|close) this box" by mouse
					'use the arrow keys to move this box', // sentence for "move this box" by keyboard
					', or press the enter key to %toggle% it',  // pattern-match sentence-fragment for "(open|close) this box" by keyboard
					'%mytitle%  [%dbxtitle%]' // pattern-match syntax for title-attribute conflicts
					);
			});
			
			<?php	
			exit;
		}
	}
	#endregion
	
	#region SingleTon Activation Handling
	//Check if ABSPATH and WPINC is defined, this is done in wp-settings.php
	//If not defined, we can't guarante that all required functions are available.
	if(defined('ABSPATH') && defined('WPINC')) {
		SiteInfoGenerator::Enable();	
	}
	#endregion
	
}
?>