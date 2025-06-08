<?php
/*
 * Plugin Name: WP AI Assistant
 * Version: 2.8
 * Plugin URI: http://codecanyon.net/user/loopus/portfolio/
 * Description: Add a virtual assistant on your website, and easily program his A.I from backend, with an unique visual A.I system .
 * Author: Biscay Charly (loopus)
 * Author URI: http://codecanyon.net/user/loopus/
 * Tested up to: 5.3
 *
 * @package WordPress
 * @author Biscay Charly (loopus)
 * @since 1.0.0
 */

if (!defined('ABSPATH'))
    exit;

register_activation_hook(__FILE__, 'iaa_install');
register_uninstall_hook(__FILE__, 'iaa_uninstall');

global $jal_db_version;
$jal_db_version = "1.1";

// Include plugin class files
require_once('includes/iaa-core.php');
require_once('includes/iaa-admin.php');

function AIAssistant()
{
    $version = 2.800;
    iaa_checkDBUpdates($version);
    $instance = IAA_Core::instance(__FILE__, $version);
    if (is_null($instance->menu)) {
        $instance->menu = IAA_admin::instance($instance);
    }

    return $instance;
}

/**
 * Installation. Runs on activation.
 * @access  public
 * @since   1.0.0
 * @return  void
 */
function iaa_install()
{
    global $wpdb;
    global $jal_db_version;
    require_once(ABSPATH . '/wp-admin/includes/upgrade.php');

    add_option("jal_db_version", $jal_db_version);

    $db_table_name = $wpdb->prefix . "iaa_steps";
    if ($wpdb->get_var("SHOW TABLES LIKE '$db_table_name'") != $db_table_name) {
        if (!empty($wpdb->charset))
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if (!empty($wpdb->collate))
            $charset_collate .= " COLLATE $wpdb->collate";

        $sql = "CREATE TABLE $db_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		start BOOL  NOT NULL DEFAULT 0,
		title VARCHAR(120) NOT NULL,
		content TEXT NOT NULL,
		UNIQUE KEY id (id)
		) $charset_collate;";
        dbDelta($sql);
    }

    $db_table_name = $wpdb->prefix . "iaa_links";
    if ($wpdb->get_var("SHOW TABLES LIKE '$db_table_name'") != $db_table_name) {
        if (!empty($wpdb->charset))
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if (!empty($wpdb->collate))
            $charset_collate .= " COLLATE $wpdb->collate";

        $sql = "CREATE TABLE $db_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		originID INT(9) NOT NULL,
		destinationID INT(9) NOT NULL,
		conditions TEXT NOT NULL,
		UNIQUE KEY id (id)
		) $charset_collate;";
        dbDelta($sql);
    }

    $db_table_name = $wpdb->prefix . "iaa_params";
    if ($wpdb->get_var("SHOW TABLES LIKE '$db_table_name'") != $db_table_name) {
        if (!empty($wpdb->charset))
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if (!empty($wpdb->collate))
            $charset_collate .= " COLLATE $wpdb->collate";

        $sql = "CREATE TABLE $db_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		purchaseCode VARCHAR(250) NOT NULL,
		previewHeight SMALLINT(5) NOT NULL DEFAULT 1200,
		avatar_corpse TINYINT(2) NOT NULL DEFAULT 1,
                avatar_eyes TINYINT(2) NOT NULL DEFAULT 1,
                avatar_hair TINYINT(2) NOT NULL DEFAULT 1,
                avatar_hat TINYINT(2) NOT NULL DEFAULT 1,
                avatar_head TINYINT(2) NOT NULL DEFAULT 1,
                avatar_hands TINYINT(2) NOT NULL DEFAULT 1,
                avatar_mouth TINYINT(2) NOT NULL DEFAULT 1,
                avatar_neck TINYINT(2) NOT NULL DEFAULT 6,
                positionScreen BOOL NOT NULL DEFAULT 1,
                initialText TEXT NOT NULL,
                enabled BOOL NOT NULL DEFAULT 0,
                hideOnClose BOOL NOT NULL DEFAULT 0,
                colorBubble VARCHAR(8) DEFAULT '#ECF0F1',
                colorText VARCHAR(8) DEFAULT '#7f8c8d',
                colorButtons VARCHAR(8) DEFAULT '#1abc9c',
                colorButtonsText VARCHAR(8) DEFAULT '#FFFFFF',
                colorShine VARCHAR(8) DEFAULT '#1ABC9C',
                avatarType BOOL NOT NULL,
                avatarImg VARCHAR(250) NOT NULL,
                avatarTalkImg VARCHAR(250) NOT NULL,
                avatarMouthY INT(9) NOT NULL,
                avatarMouthWidth INT(9) NOT NULL,
                avatarWidth INT(9) NOT NULL,
                avatarHeight INT(9) NOT NULL,
                disableIntro BOOL NOT NULL,
                useWPML BOOL NOT NULL,
                disableMobile BOOL NOT NULL,
		    UNIQUE KEY id (id)
		) $charset_collate;";
        dbDelta($sql);
        $rows_affected = $wpdb->insert($db_table_name, array('previewHeight' => 300, 'initialText' => 'Need help ?','avatarWidth'=>128,'avatarHeight'=>250));
    }


    global $isInstalled;
    $isInstalled = true;
}

// End install()

/**
 * Update database
 * @access  public
 * @since   2.0
 * @return  void
 */
function iaa_checkDBUpdates($version)
{
    global $wpdb;
    $installed_ver = get_option("iaa_version");
    if($installed_ver < 1.4){
        $table_name = $wpdb->prefix . "iaa_params";
        $sql = "ALTER TABLE " . $table_name . " ADD avatarType BOOL NOT NULL;";
        $wpdb->query($sql);
        $sql = "ALTER TABLE " . $table_name . " ADD avatarImg VARCHAR(250) NOT NULL;";
        $wpdb->query($sql);
        $sql = "ALTER TABLE " . $table_name . " ADD avatarTalkImg VARCHAR(250) NOT NULL;";
        $wpdb->query($sql);
        $sql = "ALTER TABLE " . $table_name . " ADD avatarMouthY INT(9) NOT NULL;";
        $wpdb->query($sql);
        $sql = "ALTER TABLE " . $table_name . " ADD avatarMouthWidth INT(9) NOT NULL;";
        $wpdb->query($sql);
        $sql = "ALTER TABLE " . $table_name . " ADD avatarWidth INT(9) NOT NULL;";
        $wpdb->query($sql);
        $sql = "ALTER TABLE " . $table_name . " ADD disableIntro BOOL NOT NULL;";
        $wpdb->query($sql);
    }
    if($installed_ver < 2.2){
        $table_name = $wpdb->prefix . "iaa_params";
        $sql = "ALTER TABLE " . $table_name . " ADD useWPML BOOL NOT NULL;";
        $wpdb->query($sql);
    }
    if($installed_ver < 2.571){
        $table_name = $wpdb->prefix . "iaa_params";
        $sql = "ALTER TABLE " . $table_name . " ADD disableMobile BOOL NOT NULL;";
        $wpdb->query($sql);
    }    

    update_option("iaa_version", $version);
}

/**
 * Uninstallation.
 * @access  public
 * @since   1.0.0
 * @return  void
 */
function iaa_uninstall()
{
    global $wpdb;
    global $jal_db_version;

    setcookie('pll_updateIA', '0');
    unset($_COOKIE['pll_updateIA']);
    setcookie('pll_updateIA', null, -1, '/');
    $table_name = $wpdb->prefix . "iaa_steps";
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    $table_name = $wpdb->prefix . "iaa_links";
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    $table_name = $wpdb->prefix . "iaa_params";
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// End uninstall()

AIAssistant();
