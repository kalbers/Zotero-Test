<?php
/*
Plugin Name: Zotero Test
Plugin URI: http://zotero.org
Description: Integrate your Zotero account to create a research blog with WordPress
Version: 1.0
Author: The Moon
*/


class Zotero_Test {
        
    /**
     * Zotero Test constructor
     *
     * @since 1.0
     * @uses add_action()
     * @uses register_activation_hook()
     * @uses register_deactivation_hook()
     */
     public $collection = '';
     public $libraryType = '';
	 public $libraryID = '';
	 public $librarySlug ='';
	 
     public $zotero_test_db_version = '1.0';
     
	function zotero_test() {
	    global $wpdb;

        $table_name = $wpdb->prefix . 'zoterotest';

        $sql = "CREATE TABLE " . $table_name . " (
     	  libraryType enum('user','group'),
     	  libraryID int,
     	  librarySlug varchar(255)
         );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('zotero_test_db_version', $this->zotero_test_db_version);
        
        add_action('admin_menu', array( $this, 'admin_menu' ));
        add_action('admin_init', array( $this, 'admin_init' ));
        
        // When Zotero Test is loaded, get includes.
		add_action( 'plugins_loaded', array( $this, 'includes' ) );
 		// Activation sequence
		register_activation_hook( __FILE__, array( $this, 'install' ) );
	    
		// Add shortcode
		add_shortcode('zotero', array($this,'shortcode'));
		
		// When Researcher is loaded, get includes.
		add_action( 'zotero_test_loaded', array( $this, 'includes' ) );
		
        $library_settings = get_option('zotero_test_library_settings');
        $this->libraryType = $library_settings['library_type'];
		$this->libraryID = $library_settings['library_id'];
		$this->librarySlug = $library_settings['library_slug'];
		
	}
	
	// Add Vitaware tab to the admin menu 
	
	function admin_init () {
	    register_setting(
            'zotero_test_settings', 
            'zotero_test_library_settings');
        
        add_settings_section(
            'library',
            'Library Settings',
            array($this, 'section_settings'), 
                'zotero_test_settings');
                
        add_settings_field(
            'zotero_test_library_type', 
            'Library Type', 
            array($this, 'library_type_field'), 
            'zotero_test_settings', 'library');
        add_settings_field(
            'zotero_test_library_id', 
            'Library ID', 
            array($this, 'library_id_field'), 
            'zotero_test_settings', 'library');
        add_settings_field(
            'zotero_test_library_slug', 
            'Library Slug', 
            array($this, 'library_slug_field'), 
            'zotero_test_settings', 'library');
	}
		
    function admin_menu() {
        add_menu_page('Zotero Test', 'Zotero Test', 'manage_options', 'zotero-test', array($this, 'admin_display'));
    }
    
    // Add Zotero Test admin page. Edit admin-page.php to make changes.
    function admin_display() {      
        include('admin-page.php');     
    }
    
    function library_type_field() {
        $library_settings = get_option('zotero_test_library_settings');
        $type = $library_settings['library_type'];
        ?>
        <input type="radio" name="zotero_test_library_settings[library_type]" value="user" <?php if($type == 'user'): ?> checked="checked" <?php endif; ?> />User
        <input type="radio" name="zotero_test_library_settings[library_type]" value="group" <?php if($type == 'group'): ?> checked="checked" <?php endif; ?> />Group
        <?php
    }
	
	function library_id_field() {
	    $library_settings = get_option('zotero_test_library_settings');
	    ?>
	    <input id="library_id" name="zotero_test_library_settings[library_id]" type="text" value="<?php echo $library_settings['library_id']; ?>" />
	    <?php
	}
	
	function library_slug_field() {
	    $library_settings = get_option('zotero_test_library_settings');
	    ?>
        <input id="library_slug" name="zotero_test_library_settings[library_slug]" type="text" value="<?php echo $library_settings['library_slug']; ?>" />
        <?php
	}
	
	function section_settings() {	    
	}
	
	/**
     * Includes other necessary plugin files.
     */
	function includes() {
	    require_once(dirname( __FILE__ ).'/libZotero/build/libZoteroSingle.php');
	}
	
    function print_collection($collection) {
        $this->includes();
        $html = '';
        $library = new Zotero_Library($this->libraryType, $this->libraryID, $this->librarySlug);
        $library->setCacheTtl(84600);

        $subCollections = $library->fetchCollections(array('collectionKey'=>$collection));
        foreach($subCollections as $subCollection){
           $html .= '<h3 id="'. $subCollection->name . '">' . $subCollection->name . "</h3>";
           $subCollectionKey = $subCollection->collectionKey;
           $items = $library->fetchItemsTop(array('limit'=>100, 'collectionKey'=>$subCollectionKey, 'content'=>'json,bib', 'order'=>'creator'));
           foreach($items as $item){
               $html .= preg_replace('/\s(http:[\S]+?)(?=.<)/i', " <a target='new' href='$1'>$1</a>", $item->bibContent);
           }
        }
        return $html;
    }
    
    function shortcode($atts) {
        extract(shortcode_atts(array(
            'collection' => '',
        ), $atts));
        
        return $this->print_collection($atts['collection']);
    }
}

global $zoteroTest;
$zoteroTest = new Zotero_Test();

function zotero_test_get_zotero_custom_field() {
    global $post;
    return get_post_meta($post->ID, 'zotero', true);
}


function zotero_test_print_collection() {
    global $zoteroTest;
    $zoteroCollectionID = zotero_test_get_zotero_custom_field();
    if ($zoteroCollectionID){
        echo $zoteroTest->print_collection($zoteroCollectionID);
    }
    else{
        echo '<p>There are no additional resources at this time.</p>';
    }
}