<?php
if (!defined('ABSPATH'))
    exit;

class IAA_admin
{

    /**
     * The single instance
     * @var    object
     * @access  private
     * @since    1.0.0
     */
    private static $_instance = null;

    /**
     * The main plugin object.
     * @var    object
     * @access  public
     * @since    1.0.0
     */
    public $parent = null;

    /**
     * Prefix for plugin settings.
     * @var     string
     * @access  publicexport
     * Delete
     * @since   1.0.0
     */
    public $base = '';

    /**
     * Available settings for plugin.
     * @var     array
     * @access  public
     * @since   1.0.0
     */
    public $settings = array();

    /**
     * Is WooCommerce activated ?
     * @var     array
     * @access  public
     * @since   1.5.0
     */
    public $isWooEnabled = false;

    public function __construct($parent)
    {
        /* Avatars slot numbers
         * You can edit it if you added new graphics
         */
        $this->avatar_slot_max = array();
        $this->avatar_slot_max['corpse'] = 18; // max corpse
        $this->avatar_slot_max['eyes'] = 3; // max eyes
        $this->avatar_slot_max['hair'] = 16; // max hairs
        $this->avatar_slot_max['head'] = 4; // max heads
       // $this->avatar_slot_max['mouth'] = 4; // max mouths
        //$this->avatar_slot_max['neck'] = 6; // max neck
       // $this->avatar_slot_max['hands'] = 2; // max hands


        /* Core code, don't modify */
        $this->parent = $parent;
        $this->base = 'wpt_';
        $this->dir = dirname($this->parent->file);
        add_action('admin_menu', array($this, 'add_menu_item'));
        add_action('admin_print_scripts', array($this, 'admin_scripts'));
        add_action('admin_print_styles', array($this, 'admin_styles'));
        add_action('wp_ajax_nopriv_iaa_saveStep', array($this, 'saveStep'));
        add_action('wp_ajax_iaa_saveStep', array($this, 'saveStep'));
        add_action('wp_ajax_nopriv_iaa_addStep', array($this, 'addStep'));
        add_action('wp_ajax_iaa_addStep', array($this, 'addStep'));
        add_action('wp_ajax_nopriv_iaa_loadStep', array($this, 'loadStep'));
        add_action('wp_ajax_iaa_loadStep', array($this, 'loadStep'));
        add_action('wp_ajax_nopriv_iaa_loadSteps', array($this, 'loadSteps'));
        add_action('wp_ajax_iaa_loadSteps', array($this, 'loadSteps'));
        add_action('wp_ajax_nopriv_iaa_removeStep', array($this, 'removeStep'));
        add_action('wp_ajax_iaa_removeStep', array($this, 'removeStep'));
        add_action('wp_ajax_nopriv_iaa_saveStepPosition', array($this, 'saveStepPosition'));
        add_action('wp_ajax_iaa_saveStepPosition', array($this, 'saveStepPosition'));
        add_action('wp_ajax_nopriv_iaa_newLink', array($this, 'newLink'));
        add_action('wp_ajax_iaa_newLink', array($this, 'newLink'));
        add_action('wp_ajax_nopriv_iaa_changePreviewHeight', array($this, 'changePreviewHeight'));
        add_action('wp_ajax_iaa_changePreviewHeight', array($this, 'changePreviewHeight'));
        add_action('wp_ajax_nopriv_iaa_saveLinks', array($this, 'saveLinks'));
        add_action('wp_ajax_iaa_saveLinks', array($this, 'saveLinks'));    
        add_action('wp_ajax_nopriv_iaa_checkLicense', array($this, 'checkLicense'));
        add_action('wp_ajax_iaa_checkLicense', array($this, 'checkLicense'));
        add_action('wp_ajax_nopriv_iaa_saveSettings', array($this, 'saveSettings'));
        add_action('wp_ajax_iaa_saveSettings', array($this, 'saveSettings'));
        add_action('wp_ajax_nopriv_iaa_loadSettings', array($this, 'loadSettings'));
        add_action('wp_ajax_iaa_loadSettings', array($this, 'loadSettings'));
        add_action('wp_ajax_nopriv_iaa_removeAllSteps', array($this, 'removeAllSteps'));
        add_action('wp_ajax_iaa_removeAllSteps', array($this, 'removeAllSteps'));

        add_action('admin_head', array($this->parent, 'apply_styles'));

        //

        if (isset($_GET['activateWebsite']) && !$this->isUpdated()) {
            $this->activateLicense();
        }
    }


    /**
     * Add menu to admin
     * @return void
     */
    public function add_menu_item()
    {
        add_menu_page(__('A.I Assistant', 'iaa'), __('A.I Assistant', 'iaa'), 'manage_options', 'iaa_menu', array($this, 'view_edit_iaa'), 'dashicons-businessman');
        $menuSlag = 'iaa_menu';
    }

    public function getSettings()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "iaa_params";
        $settings = $wpdb->get_results("SELECT * FROM $table_name WHERE id=1 LIMIT 1");
        $settings = $settings[0];
        return $settings;
    }


    /*
     * Edit assistant
     */
    public function view_edit_iaa()
    {
        global $wpdb;
        $settings = $this->getSettings();
        wp_enqueue_style('thickbox');
        wp_enqueue_script('thickbox');

        // backup & export data
        if (!is_dir(plugin_dir_path(__FILE__) . '../tmp')) {
            mkdir(plugin_dir_path(__FILE__) . '../tmp');
            chmod(plugin_dir_path(__FILE__) . '../tmp', 0747);
        }

        $destination = plugin_dir_path(__FILE__) . '../tmp/export_AIAssistant.json';
        if (file_exists($destination)) {
            unlink($destination);
        }
        $jsonExport = array();
        $dispS = '';
        if(strlen($settings->purchaseCode)<3){
        //  $dispS = 'true';
        }
        $settingsA = clone $settings;
        $settingsA->purchaseCode = "";
        $jsonExport['settings'] = array();
        $jsonExport['settings'][] = $settingsA;
        $table_name = $wpdb->prefix . "iaa_steps";
        $steps = array();
        foreach ($wpdb->get_results("SELECT * FROM $table_name") as $key => $row) {
            $steps[] = $row;
        }
        $jsonExport['steps'] = $steps;
        $table_name = $wpdb->prefix . "iaa_links";
        $links = array();
        foreach ($wpdb->get_results("SELECT * FROM $table_name") as $key => $row) {
            $links[] = $row;
        }
        $jsonExport['links'] = $links;
        $fp = fopen(plugin_dir_path(__FILE__) . '../tmp/export_AIAssistant.json', 'w');
        fwrite($fp, $this->jsonRemoveUnicodeSequences($jsonExport));
        fclose($fp);
        // eof export

        /* Import */
        $alert = '';
        if (isset($_GET['import']) && isset($_FILES['importFile'])) {
            $error = false;
            if (!is_dir(plugin_dir_path(__FILE__) . '../tmp')) {
                mkdir(plugin_dir_path(__FILE__) . '../tmp');
                chmod(plugin_dir_path(__FILE__) . '../tmp', 0747);
            }
            $target_path = plugin_dir_path(__FILE__) . '../tmp/export_AIAssistant.json';
            if (@move_uploaded_file($_FILES['importFile']['tmp_name'], $target_path)) {
                $file = file_get_contents($target_path);
                $dataJson = json_decode($file, true);

                $table_name = $wpdb->prefix . "iaa_params";
                $wpdb->query("TRUNCATE TABLE $table_name");
                if (array_key_exists('settings', $dataJson)) {
                    foreach ($dataJson['settings'] as $key => $value) {
                        echo $wpdb->insert($table_name, $value);
                        echo $wpdb->last_error;
                    }
                }

                $table_name = $wpdb->prefix . "iaa_steps";
                $wpdb->query("TRUNCATE TABLE $table_name");
                if (array_key_exists('steps', $dataJson)) {
                    foreach ($dataJson['steps'] as $key => $value) {
                        $wpdb->insert($table_name, $value);
                    }
                }
                $table_name = $wpdb->prefix . "iaa_links";
                $wpdb->query("TRUNCATE TABLE $table_name");
                if (array_key_exists('links', $dataJson)) {
                    foreach ($dataJson['links'] as $key => $value) {
                        $wpdb->insert($table_name, $value);
                    }
                }

                $files = glob(plugin_dir_path(__FILE__) . '../tmp/*');
                foreach ($files as $file) {
                    if (is_file($file))
                        unlink($file);
                }

                $alert = '<div class="alert alert-success" role="alert" style="position: relative;top: 10px;z-index: -10;">' . __('Data has been imported', 'iaa') . '</div>';


            } else {
                $alert = '<div class="alert alert-danger" role="alert" style="position: relative;top: 10px;z-index: -10;">' . __('There was an error during transfer', 'iaa') . '</div>';
            }
        }
        // eof import

        echo '<div id="iaa_bootstraped" class="iaa_bootstraped iaa_panel">';
        
         echo '<div id="iaa_winActivation" class="modal fade " data-show="'.$dispS.'" >
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h4 class="modal-title">The license must be verified</h4>
                        </div>
                        <div class="modal-body">
                          <div id="iaa_iconLock"></div>
                          <p style="margin-bottom: 14px;">
                                  The license of this plugin isn\'t verified.<br/>Please fill the field below with your purchase code :
                          </p>
                          <div class="form-group">
                                  <input type="text" class="form-control" style="display:inline-block; width: 312px; margin-bottom: 4px" name="purchaseCode" placeholder="Enter your puchase code here"/>
                                  <a href="javascript:" onclick="iaa_checkLicense();" class="btn btn-primary"><span class="glyphicon glyphicon-check"></span>Verify</a>
                                  <br/>
                                  <span style="font-size:12px;"><a href="'.$this->parent->assets_url.'images/purchaseCode.gif" target="_blank">Where I can find my purchase code ?</a></span>
                          </div>
                          <div class="alert alert-danger" style="font-size:12px;  margin-bottom: 0px;" >
                                  <span class="glyphicon glyphicon-warning-sign" style="margin-right: 28px;float: left;font-size: 22px;margin-top: 10px;margin-bottom: 10px;"></span>
                            Each website using this plugin needs a legal license (1 license = 1 website). <br/>
                            You can find more information on envato licenses <a href="https://codecanyon.net/licenses/standard" target="_blank">clicking here</a>.<br/>
                               If you need to buy a new license of this plugin, <a href="https://codecanyon.net/item/wp-ai-assistant/10070762?ref=loopus" target="_blank">click here</a>.
                          </div>
                        </div>
                        <div class="modal-footer" style="text-align: center;">
                                                                  <a href="javascript:"  id="iaa_closeWinActivationBtn" class="btn btn-default disabled"><span class="glyphicon glyphicon-remove"></span><span class="iaa_text">Close</span></a>
                                                            </div><!-- /.modal-footer -->
                      </div><!-- /.modal-content -->
                    </div><!-- /.modal-dialog -->
                  </div><!-- /.modal -->';
        

        echo '<div id="iaa_formWrapper" >';

        echo '<div class="iaa_winHeader col-md-12 palette palette-turquoise">
               <span class="glyphicon glyphicon-user"></span>' . __('A.I Assistant', 'iaa') . '';
        echo '<div class="btn-toolbar">';
        echo '<div class="btn-group">';
        echo '<a class="btn btn-primary" href="javascript:" onclick="iaa_closeSettings();"><span class="glyphicon glyphicon-list"></span></a>';
        echo '<a class="btn btn-primary" href="javascript:" onclick="iaa_openSettings();"><span class="glyphicon glyphicon-cog"></span></a>';
        echo '</div>';
        echo '</div>'; // eof toolbar
        echo '</div>'; // eof iaa_winHeader
        echo $alert;


        echo '<div id="iaa_panelSettings">';
        echo '<div class="container-fluid" id="iaa_stepContainer">';
        echo '<div class="col-md-12">';
        echo '<div id="iaa_avatarPreview">';
        echo '<div id="iaa_avatarPreviewContainer" class="iaa_avatarContainer">';
        echo '<div id="iaa_talkBubble" class="iaa_talkBubble"><div id="iaa_bubbleContent">Lorem ipsum dolor sit amet, consectetur adipiscing elit. </div><div class="iaa_talkInteraction"><p class="iaa_btns"><a href="javascript:" class="iaa_btn">Lorem Ipsum</a></p></div></div>';

        echo '<div class="iaa_avatar_corpse" data-num="1" data-type="corpse" data-max="' . $this->avatar_slot_max['corpse'] . '"></div>';
        echo '<div class="iaa_avatar_head" data-num="1" data-type="head" data-max="' . $this->avatar_slot_max['head'] . '"></div>';
        echo '<div class="iaa_avatar_mouth" data-num="1" data-type="mouth" data-max="1"></div>';
        echo '<div class="iaa_avatar_eyes" data-num="1" data-type="eyes" data-max="' . $this->avatar_slot_max['eyes'] . '"></div>';
        echo '<div class="iaa_avatar_hair" data-num="1" data-type="hair" data-max="' . $this->avatar_slot_max['hair'] . '"></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';


        echo '<div class="col-md-6">';
        echo '<h3>' . __('Assistant look', 'iaa') . '</h3>';

        echo '<div class="form-group">';
        echo '<label for="iaa_settings_avatarType">' . __('Use default avatar system', 'iaa') . ' : </label>';
        echo '<select id="iaa_settings_avatarType">';
        $sel1 = '';
        if ($settings->avatarType == 1) {
            $sel1 = 'selected';
        }
        echo '<option value="0">' . __('Yes', 'iaa') . '</option>';
        echo '<option value="1" ' . $sel1 . '>' . __('No', 'iaa') . '</option>';
        echo '</select>';
        echo '</div>';

        echo '<div id="iaa_avatarDefault">';
        echo '<div class="iaa_avatarSettingsField" data-part="head" data-num="1">';
        echo '<a href="javascript:"><span class="glyphicon glyphicon-chevron-left"></span></a><strong>' . __('Head', 'iaa') . '</strong><a href="javascript:"><span class="glyphicon glyphicon-chevron-right"></span></a>';
        echo '</div>'; // eof iaa_avatarSettingsField
        echo '<div class="iaa_avatarSettingsField" data-part="corpse" data-num="1">';
        echo '<a href="javascript:"><span class="glyphicon glyphicon-chevron-left"></span></a><strong>' . __('Corpse', 'iaa') . '</strong><a href="javascript:"><span class="glyphicon glyphicon-chevron-right"></span></a>';
        echo '</div>'; // eof iaa_avatarSettingsField        
        echo '<div class="iaa_avatarSettingsField" data-part="hair" data-num="1">';
        echo '<a href="javascript:"><span class="glyphicon glyphicon-chevron-left"></span></a><strong>' . __('Hair', 'iaa') . '</strong><a href="javascript:"><span class="glyphicon glyphicon-chevron-right"></span></a>';
        echo '</div>'; // eof iaa_avatarSettingsField
        echo '<div class="iaa_avatarSettingsField" data-part="eyes" data-num="1">';
        echo '<a href="javascript:"><span class="glyphicon glyphicon-chevron-left"></span></a><strong>' . __('Eyes', 'iaa') . '</strong><a href="javascript:"><span class="glyphicon glyphicon-chevron-right"></span></a>';
        echo '</div>'; // eof iaa_avatarSettingsField        
        echo '</div>';

        echo '<div id="iaa_avatarCustom">';
        echo '<div class="form-group"><label>' . __('Avatar Picture (128*128px max)', 'iaa') . ' : </label><input id="iaa_settings_avatarImg" type="text" name="iaa_settings_avatarImg" value="' . $settings->avatarImg . '" size="50"/>
                <input class="imageBtn  btn btn-default" type="button" value="' . __('Upload Image', 'iaa') . '"/></div>';

        echo '<div class="form-group"><label>' . __('Speaking mouth Picture', 'iaa') . ' : </label><input id="iaa_settings_avatarTalkImg" type="text" name="iaa_settings_avatarTalkImg" value="' . $settings->avatarTalkImg . '" size="50"/>
                <input class="imageBtn  btn btn-default" type="button" value="' . __('Upload Image', 'iaa') . '"/></div>';

        $bgImg = '';
        $bgImgTalk = '';
        echo '<div class="form-group">' . __('Drag the mouth to reposition it', 'iaa') . ' : </div>';
        echo '<div id="iaa_avatarCustomPreview"><div id="iaa_avatarCustomPreviewContent">';
        if ($settings->avatarImg != "") {
            echo '<img src="' . $settings->avatarImg . '" alt=""  />';
            if ($settings->avatarTalkImg != "") {
                echo '<img src="' . $settings->avatarTalkImg . '" alt="" id="iaa_avatarCustomPreview_mouth"  />';
            }
        }
        echo '</div></div>'; // eof iaa_avatarCustomPreview

        echo '</div>'; // eof iaa_avatarCustom
        echo '</div>'; // eof col-md-6


        echo '<div class="col-md-6">';
        echo '<h3>' . __('Design', 'iaa') . '</h3>';
        echo '<div class="form-group">';
        echo '<label>' . __('Bubble color', 'iaa') . ' : </label>';
        echo '<input  type="text" id="iaa_settings_colorBubble" name="colorBubble" class="colorpick" placeholder="' . __('Enter the color hex', 'iaa') . '" value="' . $settings->colorBubble . '">';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label>' . __('Text color', 'iaa') . ' : </label>';
        echo '<input type="text" id="iaa_settings_colorText" name="colorText" class="colorpick" placeholder="' . __('Enter the color hex', 'iaa') . '" value="' . $settings->colorText . '">';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label>' . __('Buttons color', 'iaa') . ' : </label>';
        echo '<input type="text" id="iaa_settings_colorButtons" name="colorButtons" class="colorpick" placeholder="' . __('Enter the color hex', 'iaa') . '" value="' . $settings->colorButtons . '">';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label>' . __('Buttons text color', 'iaa') . ' : </label>';
        echo '<input type="text" id="iaa_settings_colorButtonsText" name="colorButtonsText" class="colorpick" placeholder="' . __('Enter the color hex', 'iaa') . '" value="' . $settings->colorButtonsText . '">';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label>' . __('Shining color', 'iaa') . ' : </label>';
        echo '<input type="text" id="iaa_settings_colorShine" name="colorShine" class="colorpick" placeholder="' . __('Enter the color hex', 'iaa') . '" value="' . $settings->colorShine . '">';
        echo '</div>';


        echo '</div>'; // eof col-md-6

        echo '<div class="col-md-6" style="clear: both;">';
        echo '<h3>' . __('Settings', 'iaa') . '</h3>';

        echo '<div class="form-group">';
        echo '<label for="iaa_settings_enable">' . __('Assistant Enabled', 'iaa') . ' : </label>';
        echo '<select id="iaa_settings_enable">';
        echo '<option value="0">' . __('No', 'iaa') . '</option>';
        echo '<option value="1">' . __('Yes', 'iaa') . '</option>';
        echo '</select>';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label for="iaa_settings_position">' . __('Position', 'iaa') . ' : </label>';
        echo '<select id="iaa_settings_position">';
        echo '<option value="0">' . __('Left', 'iaa') . '</option>';
        echo '<option value="1">' . __('Right', 'iaa') . '</option>';
        echo '</select>';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label for="iaa_settings_position">' . __('Initial message', 'iaa') . ' : </label>';
        echo '<input id="iaa_settings_initialText" type="text" value="" />';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label for="iaa_settings_hideOnClose">' . __('Hide assistant on close', 'iaa') . ' : </label>';
        echo '<select id="iaa_settings_hideOnClose">';
        echo '<option value="1">' . __('Yes', 'iaa') . '</option>';
        echo '<option value="0">' . __('No', 'iaa') . '</option>';
        echo '</select>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label for="iaa_settings_disableIntro">' . __('Disable initial text', 'iaa') . ' : </label>';
        echo '<select id="iaa_settings_disableIntro">';
        echo '<option value="0">' . __('No', 'iaa') . '</option>';
        echo '<option value="1">' . __('Yes', 'iaa') . '</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="iaa_settings_disableMobile">' . __('Disable on mobiles', 'iaa') . ' : </label>';
        echo '<select id="iaa_settings_disableMobile">';
        echo '<option value="0">' . __('No', 'iaa') . '</option>';
        echo '<option value="1">' . __('Yes', 'iaa') . '</option>';
        echo '</select>';
        echo '</div>';        
        
        echo '<div class="form-group">';
        echo '<label>' . __('Purchase code', 'iaa') . ' : </label>';
        echo '<input type="text" id="iaa_settings_purchaseCode" name="purchaseCode" placeholder="' . __('Fill your purchase code', 'iaa') . '" value="' . $settings->purchaseCode . '">';
        echo '<br/><span class="description" style="font-size: 12px;margin-top: -10px;display: inline-block;position: relative;top: -12px;"><a href="'.$this->parent->assets_url.'img/purchaseCode.gif" target="_blank">'. __('How to find my purchase code ?', 'iaa').'</a></span>';
        echo '</div>';
                

        $displWPML = 'none';
        if (function_exists('icl_object_id')) {
            $displWPML = 'block';
        }
        echo '<div class="form-group" style="display: '.$displWPML.'">';
        echo '<label for="iaa_settings_useWPML">' . __('Use with WPML ?', 'iaa') . '  </label>';
        echo '<select id="iaa_settings_useWPML">';
        echo '<option value="0">' . __('No', 'iaa') . '</option>';
        echo '<option value="1">' . __('Yes', 'iaa') . '</option>';
        echo '</select>';
        echo '</div>';



        echo '</div>'; // eof col-md-6

        echo '<div class="col-md-6">';
        echo '<h3>' . __('Import / Export', 'iaa') . '</h3>';
        echo '<p><a href="' . esc_url(trailingslashit(plugins_url('/tmp/', $this->parent->file))) . 'export_AIAssistant.json"  download  class="btn btn-default">' . __('Export data', 'iaa') . '</a> </p>';
        echo '<form id="iaa_importForm" action="admin.php?page=iaa_menu&import=1" method="post" enctype="multipart/form-data" ><p>
             <label for="iaa_settings_importFile">' . __('Select the exported .json file', 'iaa') . ': </label>
              <input type="file" class="form-control" style="display: inline-block;" name="importFile" id="iaa_settings_importFile" />&nbsp;
              <a href="javascript:" class="btn btn-warning" onclick="jQuery(\'#iaa_importForm\').submit();">Import data</a><br/>
              <span style="color: red; font-style: italic;">* ' . __('Be carreful : all existing data will be erased', 'iaa') . '</span>
              </p>';
        echo '</div>'; // eof col-md-6


        echo '<div class="clearfix"></div>';
        echo '<div class="col-md-12"><p style="margin-top: 28px; text-align: center;"><a href="javascript:" onclick="iaa_saveSettings();" class="btn btn-lg btn-default"><span class="glyphicon glyphicon-floppy-disk"></span>' . __("Save", 'iaa') . '</a> </p></div>';

        echo '</div>'; // eof container
        echo '</div>'; // eof iaa_panelSettings

        echo '<div id="iaa_panelPreview">';
        echo '<div class="clearfix"></div>';
        echo '<div class=" text-right" style="max-width: 90%;margin: 0 auto;margin-top: 18px;">
        <p>
        <a href="javascript:" style="margin-right: 12px;" onclick="iaa_addStep( \'' . __('My Step', 'iaa') . '\');" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span>' . __("Add a step", 'iaa') . '</a>
        <a href="' . get_home_url() . '?iaa_action=preview" target="_blank" style="margin-right: 12px;"  class="btn btn-default"><span class="glyphicon glyphicon-eye-open"></span>' . __("Preview", 'iaa') . '</a>
        <a href="javascript:" data-toggle="modal" data-target="#modal_removeAllSteps" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span>' . __("Remove all steps", 'iaa') . '</a>
        </p>
        </div>';

        echo '
        <!-- Modal -->
        <div class="modal fade" id="modal_removeAllSteps" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-body">
                ' . __('Are you sure you want to delete all steps ?', 'iaa') . '
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal"  onclick="iaa_removeAllSteps();" >' . __('Yes', 'iaa') . '</button>
                <button type="button" class="btn btn-default" data-dismiss="modal" >' . __('No', 'iaa') . '</button>
              </div>
            </div>
          </div>
        </div>';

        echo '<div id="iaa_stepsOverflow">';
        echo '<div id="iaa_stepsContainer">';
        echo '<canvas id="iaa_stepsCanvas"></canvas>';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-12 text-center"><p> </p></div>';
        echo '</div>';

        echo '</div>';

        echo '<div id="iaa_winStep" class="iaa_window container-fluid">';
        echo '<div class="iaa_winHeader col-md-12 palette palette-turquoise"><span class="glyphicon glyphicon-pencil"></span>' . __('Edit a step', 'iaa');

        echo '<div class="btn-toolbar">';
        echo '<div class="btn-group">';
        echo '<a class="btn btn-primary" href="javascript:"><span class="glyphicon glyphicon-remove iaa_btnWinClose"></span></a>';
        echo '</div>';
        echo '</div>'; // eof toolbar
        echo '</div>'; // eof header
        echo '<div class="container-fluid" id="iaa_stepContainer">';

        echo '<div class="row">';

        echo '<div class="col-md-5">';
        echo '<h4>' . __('Actions', 'iaa') . '</h4>';
        echo '<div id="iaa_actionsWrapper">';
        echo '<a href="javascript:" onclick="iaa_openWinAction(jQuery(this));" class="iaa_stepIconPlus"><span class="glyphicon glyphicon-plus"></span></a>';
        echo '</div>';
        echo '</div>'; // eof col-md-5

        echo '<div class="col-md-2"></div>';
        echo '<div class="col-md-5">';
        echo '<h4>' . __('Step Title', 'iaa') . '</h4>';
        echo '<div class="form-group"><input id="iaa_stepTitle" class="form-control" placeholder="' . __('Step Title', 'iaa') . '" /></textarea></div>';
        echo '</div>';

        echo '</div>';
        echo '<div class="clear"></div>';

        echo '<div class="row">';
        echo '<div class="col-md-5">';
        echo '<h4>' . __('Interactions', 'iaa') . '</h4>';
        echo '<div id="iaa_interactionsWrapper">';
        echo '<a href="javascript:" onclick="iaa_openWinInteraction(jQuery(this));" class="iaa_stepIconPlus"><span class="glyphicon glyphicon-plus"></span></a>';
        echo '</div>';
        echo '</div>'; // eof col-md-5

        echo '<div class="col-md-2"></div>';

        echo '<div class="col-md-5">';
        echo '<h4>' . __('Text', 'iaa') . '</h4>';
        echo '<div class="form-group"><textarea id="iaa_stepText" class="form-control" placeholder="' . __('Enter the dialog text here', 'iaa') . '" ></textarea></div>';
        echo '<div class="field_description">';
        echo '<p><strong>[b]your text[/b]</strong> : ' . __('text in bold', 'iaa') . '</p>';
        echo '<p><strong>[u]your text[/u]</strong> : ' . __('text underlined', 'iaa') . '</p>';
        echo '<p><strong>[interaction id="your-interaction-id"]</strong> : ' . __('recover the value of a previous interaction', 'iaa') . '</p>';
        echo '</div>';
        echo '</div>'; // eof col-md-5
        echo '<div class="clear"></div>';


        echo '<div class="row">';
        echo '<div class="col-md-12">';
        echo '<h4>' . __('Preview', 'iaa') . '</h4>';
        echo '</div>'; // eof col-md-12
        echo '</div>'; // eof row
        echo '<div class="clear"></div>';

        echo '<div id="iaa_stepPreview" class="container-fluid">';
        echo '<div id="iaa_avatarPreviewContainer" class="iaa_avatarContainer">';

        echo '<div class="iaa_avatar_corpse" data-num="1" data-type="corpse" data-max="' . $this->avatar_slot_max['corpse'] . '"></div>';
        echo '<div class="iaa_avatar_head" data-num="1" data-type="head" data-max="' . $this->avatar_slot_max['head'] . '"></div>';
        echo '<div class="iaa_avatar_mouth" data-num="1" data-type="mouth" data-max="1"></div>';
        echo '<div class="iaa_avatar_eyes" data-num="1" data-type="eyes" data-max="' . $this->avatar_slot_max['eyes'] . '"></div>';
        echo '<div class="iaa_avatar_hair" data-num="1" data-type="hair" data-max="' . $this->avatar_slot_max['hair'] . '"></div>';
        echo '</div>';
        echo '</div>'; // eof iaa_stepPreview


        echo '<p style="margin-left: -14px;"><a href="javascript:" class="btn btn-primary text-center" onclick="iaa_saveStep();"><span class="glyphicon glyphicon-floppy-disk"></span>' . __('Save', 'iaa') . '</a></p>';

        echo '</div>'; // eof container

        echo '</div>';

        echo '</div>'; // eof window step


        echo '<div id="iaa_winLink" class="iaa_window container-fluid">';
        echo '<div class="iaa_winHeader col-md-12 palette palette-turquoise"><span class="glyphicon glyphicon-pencil"></span>' . __('Edit a link', 'iaa');

        echo '<div class="btn-toolbar">';
        echo '<div class="btn-group">';
        echo '<a class="btn btn-primary" href="javascript:"><span class="glyphicon glyphicon-remove iaa_btnWinClose"></span></a>';
        echo '</div>';
        echo '</div>'; // eof toolbar
        echo '</div>'; // eof header

        echo '<div class="container-fluid" id="iaa_stepContainer">';
        echo '<div class="row">';
        //echo '<div class="col-md-12" style="margin-top: 28px;"><p id="iaa_linkNoInteraction">' . __('Please add interactions to the origin step') . '</p></div>';
        echo '<div id="iaa_linkInteractions">';
        echo '<p>' . __('Origin Step', 'iaa') . ' : <b id="iaa_linkOriginTitle"></b></p>';
        echo '<p>' . __('Destination Step', 'iaa') . ' : <b id="iaa_linkDestinationTitle"></b></p>';
        echo '<a href="javascript:" class="btn btn-warning" onclick="iaa_addLinkInteraction();"><span class="glyphicon glyphicon-plus"></span>' . __('Add a condition', 'iaa') . '</a>';
        echo '</div>';

        echo '</div>'; // eof row
        echo '<div class="row"><div class="col-md-12"><p><a href="javascript:" onclick="iaa_linkSave();" class="btn btn-primary" style="margin-top: 24px;"><span class="glyphicon glyphicon-ok"></span>' . __('Save', 'iaa') . '</a>
              <a href="javascript:" onclick="iaa_linkDel();" class="btn btn-danger" style="margin-top: 24px;"><span class="glyphicon glyphicon-trash"></span>' . __('Delete', 'iaa') . '</a></p></div></div>';


        echo '</div>'; // eof container

        echo '</div>'; // eof iaa_winLink


        echo '<div id="iaa_linkBubble">';
        echo '<div class="">';
        echo '<p id="iaa_linkNoInteraction">' . __('Please add interactions to the origin step') . '</p>';
        echo '<div id="iaa_linkInteractions">';
        echo '<a href="javascript:" class="btn btn-warning" onclick="iaa_addLinkInteraction();"><span class="glyphicon glyphicon-plus"></span>' . __('Add a condition', 'iaa') . '</a>';
        echo '</div>';
        echo '<p><a href="javascript:" onclick="iaa_interactionSave();" class="btn btn-primary" style="margin-top: 24px;"><span class="glyphicon glyphicon-ok"></span>' . __('Save', 'iaa') . '</a>
              <a href="javascript:" onclick="iaa_interactionDel();" class="btn btn-danger" style="margin-top: 24px;"><span class="glyphicon glyphicon-trash"></span>' . __('Delete', 'iaa') . '</a></p>';
        echo '</div>'; // eof iaa_itemWindowPanel
        echo '</div>'; // eof iaa_linkBubble

        echo '<div id="iaa_actionBubble">';

        echo '<div class="iaa_itemWindowPanel">';

        echo '<div class="">
            <select id="iaa_actionSelect" class="form-control select select-primary select-block mbl" data-toggle="select">
            <option value="">' . __('Nothing', 'iaa') . '</option>
            <option value="changeUrl">' . __('Redirect to a page', 'iaa') . '</option>
            <option value="executeJS">' . __('Execute JS code', 'iaa') . '</option>
            <option value="showElement">' . __('Show an element', 'iaa') . '</option>
            <option value="sendEmail">' . __('Send an email of the dialog', 'iaa') . '</option>
            <option value="sendInteractions">' . __('Send past interactions as post variables to a page', 'iaa') . '</option>
            </select>
        </div>';

        echo '<div id="iaa_actionContent">';
        echo '<div data-type="changeUrl">';
        echo '<div class=""><input type="text" class="form-control" name="url" placeholder="' . __('Enter the url here : http://...', 'iaa') . '"/> </div>';
        echo '</div>'; // eof changeUrl
        echo '<div data-type="executeJS">';
        echo '<div class=""><textarea class="form-control" name="executeJS" placeholder="' . __('Enter your Javascript code here', 'iaa') . '"></textarea></div>';
        echo '</div>'; // eof executeJS
        echo '<div data-type="showElement">';
        echo '<a href="javascript:" class="btn btn-default" onclick="iaa_startSelectElement();" ><span class="glyphicon glyphicon-search"></span>' . __('Select an element', 'iaa') . ' </a>'
                . '  <input type="text"  class="form-control" placeholder="#myElementID" name="element" style="margin-top:12px;"/><input class="form-control" type="text" name="url" placeholder="http://..."/><br/>';
        echo '<div id="iaa_actionElementSelected"><span class="glyphicon glyphicon-ok-circle"></span>' . "&nbsp;" . __('Element selected', 'iaa') . '</div>';
        echo '</div>'; // eof showElement
        echo '<div data-type="sendEmail">';
        echo '<div class=""><input type="text" class="form-control" name="email" placeholder="' . __('Enter the receipt email', 'iaa') . '"/> </div>';
        echo '<div class=""><input type="text" class="form-control" name="subject" placeholder="' . __('Enter the subject', 'iaa') . '"/> </div>';
        echo '</div>'; // eof sendEmail
        echo '<div data-type="sendInteractions">';
        echo '<div class=""><input type="text" class="form-control" name="url" placeholder="' . __('Enter the php page url here : http://...', 'iaa') . '"/> </div>';
        echo '</div>'; // eof sendInteractions
        echo '</div>'; // eof iaa_actionContent
        echo '<p><a href="javascript:" onclick="iaa_actionSave();" class="btn btn-primary" style="margin-top: 24px;"><span class="glyphicon glyphicon-ok"></span>' . __('Save', 'iaa') . '</a>
               <a href="javascript:" onclick="iaa_actionDel();" class="btn btn-danger" style="margin-top: 24px;"><span class="glyphicon glyphicon-trash"></span>' . __('Delete', 'iaa') . '</a></p>';
        echo '</div>'; // eof iaa_itemWindowPanel
        // echo '</div>';
        echo '</div>'; // eof iaa_actionBubble


        echo '<div id="iaa_interactionBubble">';
        echo '<div class="form-group"><label>' . __('Unique ID', 'iaa') . '</label><input type="text" placeholder="' . __('Enter a unique ID', 'iaa') . '" class="form-control" name="itemID" /></div>';
        echo '<div class="">
            <select id="iaa_interactionSelect" class="form-control select select-primary select-block mbl" data-toggle="select">
            <option value="">' . __('Nothing', 'iaa') . '</option>
            <option value="textfield">' . __('Text field', 'iaa') . '</option>
            <option value="numberfield">' . __('Number field', 'iaa') . '</option>
            <option value="select">' . __('Select', 'iaa') . '</option>
            <option value="button">' . __('Button', 'iaa') . '</option>
            </select>
        </div>';
        echo '<div id="iaa_interactionContent">';
        echo '<div data-type="textfield">';
        echo '<div class="form-group"><label>' . __('Label', 'iaa') . '</label><input type="text" placeholder="' . __('Label', 'iaa') . '" class="form-control" name="label" /></div>';
        echo '<div class="form-group"><label>' . __('Validation', 'iaa') . '</label><select id="iaa_interactionValidationSelect" name="validation" class="form-control">';
        echo '<option value="">' . __('Nothing', 'iaa') . '</option>';
        echo '<option value="fill">' . __('Must be filled', 'iaa') . '</option>';
        echo '<option value="email">' . __('Email', 'iaa') . '</option>';
        echo '</select></div>';
        echo '</div>'; // eof textfield
        echo '<div data-type="numberfield">';
        echo '<div class="form-group"><label>' . __('Label', 'iaa') . '</label><input type="text" placeholder="' . __('Label', 'iaa') . '" name="label"  class="form-control" /></div>';
        echo '<div class="form-group"><label>' . __('Use decimals', 'iaa') . '</label><select name="decimals" class="form-control select multiselect-primary select-block mbl" data-toggle="select">';
        echo '<option value="0">' . __('No', 'iaa') . '</option>';
        echo '<option value="1">' . __('Yes', 'iaa') . '</option>';
        echo '</select></div>';
        echo '<div class="form-group"><label>' . __('Minimum', 'iaa') . '</label><input type="number" step="any" class="form-control"  name="min" /></div>';
        echo '<div class="form-group"><label>' . __('Maximum', 'iaa') . '</label><input type="number" step="any"  class="form-control" name="max" /></div>';
        echo '<div class="form-group"><label>' . __('Validation', 'iaa') . '</label><select id="iaa_interactionValidationSelectNum" name="validation" class="form-control" >';
        echo '<option value="">' . __('Nothing', 'iaa') . '</option>';
        echo '<option value="fill">' . __('Must be filled', 'iaa') . '</option>';
        echo '</select></div>';
        echo '</div>'; // eof numberfield
        echo '<div data-type="select">';
        echo '<div class="form-group default"><label>' . __('Label', 'iaa') . '</label><input type="text" placeholder="' . __('Label', 'iaa') . '" class="form-control" name="label" /></div>';
        echo '</div>'; // eof select

        echo '<div data-type="button">';
        echo '<div class="form-group"><label>' . __('Label', 'iaa') . '</label><input type="text" placeholder="' . __('Label', 'iaa') . '" class="form-control" name="label" /></div>';
        echo '</div>'; // eof button

        echo '<p><a href="javascript:" onclick="iaa_interactionSave();" class="btn btn-primary" style="margin-top: 24px;"><span class="glyphicon glyphicon-ok"></span>' . __('Save', 'iaa') . '</a>
              <a href="javascript:" onclick="iaa_interactionDel();" class="btn btn-danger" style="margin-top: 24px;"><span class="glyphicon glyphicon-trash"></span>' . __('Delete', 'iaa') . '</a></p>';
        echo '</div>'; // eof iaa_interactionContent
        echo '</div>'; // eof iaa_interactionBubble


    }

    /*
    * Load admin styles
    */
    function admin_styles()
    {
        if (isset($_GET['page']) && strpos($_GET['page'], 'iaa') !== false) {
            wp_register_style($this->parent->_token . '-reset', esc_url($this->parent->assets_url) . 'css/reset.min.css', array(), $this->parent->_version);
            wp_enqueue_style('jquery-ui-datepicker-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css');
            wp_register_style($this->parent->_token . '-bootstrap', esc_url($this->parent->assets_url) . 'css/bootstrap.min.css', array(), $this->parent->_version);
            //   wp_register_style($this->parent->_token . '-bootstrap-timepicker', esc_url($this->parent->assets_url) . 'css/bootstrap-datetimepicker.min.css', array(), $this->_version);
            wp_register_style($this->parent->_token . '-flat-ui', esc_url($this->parent->assets_url) . 'css/flat-ui.min.css', array(), $this->parent->_version);
            wp_register_style($this->parent->_token . '-colpick', esc_url($this->parent->assets_url) . 'css/colpick.min.css', array(), $this->parent->_version);
            wp_register_style($this->parent->_token . '-iaa-admin', esc_url($this->parent->assets_url) . 'css/iaa_admin.min.css', array(), $this->parent->_version);
            wp_register_style($this->parent->_token . '-iaa-avatars', esc_url($this->parent->assets_url) . 'css/iaa_avatars.min.css', array(), $this->parent->_version);
            wp_enqueue_style($this->parent->_token . '-reset');
            wp_enqueue_style($this->parent->_token . '-bootstrap');
            wp_enqueue_style($this->parent->_token . '-flat-ui');
            // wp_enqueue_style($this->parent->_token . '-bootstrap-timepicker');
            wp_enqueue_style($this->parent->_token . '-colpick');
            wp_enqueue_style($this->parent->_token . '-iaa-admin');
            wp_enqueue_style($this->parent->_token . '-iaa-avatars');
        }
    }

    /*
     * Load admin scripts
     */
    function admin_scripts()
    {
        if (isset($_GET['page']) && strpos($_GET['page'], 'iaa') !== false) {
            wp_register_script($this->parent->_token . '-flat-ui', esc_url($this->parent->assets_url) . 'js/flat-ui.min.js', array('jquery', "jquery-ui-core"), $this->parent->_version);
            wp_enqueue_script($this->parent->_token . '-flat-ui');
            wp_register_script($this->parent->_token . '-bootstrap-switch', esc_url($this->parent->assets_url) . 'js/bootstrap-switch.js', array('jquery', "jquery-ui-core"), $this->parent->_version);
            wp_enqueue_script($this->parent->_token . '-bootstrap-switch');
            wp_register_script($this->parent->_token . '-colpick', esc_url($this->parent->assets_url) . 'js/colpick.js', array('jquery'), $this->parent->_version);
            wp_enqueue_script($this->parent->_token . '-colpick');
            wp_register_script($this->parent->_token . '-iaa-admin', esc_url($this->parent->assets_url) . 'js/iaa_admin.min.js', array("jquery-ui-draggable", "jquery-ui-droppable", "jquery-ui-resizable", "jquery-ui-sortable", "jquery-ui-datepicker"), $this->parent->_version);
            wp_enqueue_script($this->parent->_token . '-iaa-admin');


            $langs = array();
            if (function_exists('icl_object_id')) {
                $langs = icl_get_languages('skip_missing=0&orderby=KEY&order=DIR&link_empty_to=str');
            }

            $js_data[] = array(
                'assetsUrl' => esc_url($this->parent->assets_url),
                'websiteUrl' => esc_url(get_home_url()),
                'txt_selectStart' => __('Navigate to the desired page and click on the button "Select an element"', 'iaa'),
                'txt_selectSelection' => __('Click on the desired element', 'iaa'),
                'txt_selectConfirm' => __('Will you show the element that shines ?', 'iaa'),
                'txt_selectBtn' => __('Select an element', 'iaa'),
                'txt_yes' => __('Yes', 'iaa'),
                'txt_no' => __('No', 'iaa'),
                'txt_cancel' => __('Cancel', 'iaa'),
                'txt_redirection' => __('Redirection', 'iaa'),
                'txt_showElement' => __('Element showing', 'iaa'),
                'txt_sendEmail' => __('Send email', 'iaa'),
                'txt_select' => __('Select menu', 'iaa'),
                'txt_textfield' => __('Text field', 'iaa'),
                'txt_numberfield' => __('Number Field', 'iaa'),
                'txt_clicked' => __('Is clicked', 'iaa'),
                'txt_filled' => __('Is filled', 'iaa'),
                'txt_equalTo' => __('Is equal to', 'iaa'),
                'txt_superiorTo' => __('Is superior to', 'iaa'),
                'txt_inferiorTo' => __('Is inferior to', 'iaa'),
                'txt_currentDate' => __('Current date', 'iaa'),
                'txt_currentPage' => __('Current page', 'iaa'),
                'ks' => __('Current page', 'iaa'),
                'txt_option' => __('Option', 'iaa'),
                'languages' => $langs
            );
            wp_localize_script($this->parent->_token . '-iaa-admin', 'iaa_data', $js_data);
        }
    }
    private function jsonRemoveUnicodeSequences($struct) {
        return json_encode($struct,JSON_UNESCAPED_UNICODE);
    }
    public function checkLicense(){
         global $wpdb;
         try {
             $url = 'http://www.loopus-plugins.com/updates/update.php?checkCode=10070762&code=' . $_POST['code'];
             $ch = curl_init($url);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             $rep = curl_exec($ch);
             if ($rep != '0410') {
               $table_name = $wpdb->prefix . "iaa_params";
               $wpdb->update($table_name, array('purchaseCode' => $_POST['code']), array('id' => 1));
             } else {
               $table_name = $wpdb->prefix . "iaa_params";
               $wpdb->update($table_name, array('purchaseCode' => ''), array('id' => 1));
               echo '1';
             }
         } catch (Exception $e) {
             $table_name = $wpdb->prefix . "iaa_params";
             $wpdb->update($table_name, array('purchaseCode' => $_POST['code']), array('id' => 1));
         }
         die();
       }

    public function saveSettings()
    {
        global $wpdb;
                
        $table_name = $wpdb->prefix . "iaa_params";
        $settings = $wpdb->get_results("SELECT * FROM $table_name WHERE id=1 LIMIT 1");
        $settings = $settings[0];
        $previousPurchaseCode = $settings->purchaseCode;

        $corpse_num = $_POST['corpse_num'];
        $head_num = $_POST['head_num'];
        $hair_num = $_POST['hair_num'];
        $eyes_num = $_POST['eyes_num'];
        $mouth_num = 1;
        $position = $_POST['position'];
        $colorBubble = $_POST['colorBubble'];
        $colorText = $_POST['colorText'];
        $colorButtons = $_POST['colorButtons'];
        $colorButtonsText = $_POST['colorButtonsText'];
        $colorShine = $_POST['colorShine'];
        $initialText = stripslashes($_POST['initialText']);
        $hideOnClose = $_POST['hideOnClose'];
        $avatarType = $_POST['avatarType'];
        $avatarImg = $_POST['avatarImg'];
        $avatarTalkImg = $_POST['avatarTalkImg'];
        $enable = $_POST['enable'];
        $avatarMouthY = $_POST['avatarMouthY'];
        $avatarWidth = $_POST['avatarWidth'];
        $avatarHeight = $_POST['avatarHeight'];
        $avatarMouthWidth = $_POST['avatarMouthWidth'];
        $disableIntro = $_POST['disableIntro'];
        $useWPML = $_POST['useWPML'];
        $disableMobile = $_POST['disableMobile'];
        $purchaseCode = $_POST['code'];      
                
        if($avatarWidth == '' || $avatarWidth == 0){
            $avatarWidth = 64;
        }
        if($avatarHeight == '' || $avatarHeight == 0){
            $avatarHeight = 64;
        }

        $table_name = $wpdb->prefix . "iaa_params";
        $wpdb->update($table_name, array(
                'avatar_corpse' => $corpse_num,
                'avatar_head' => $head_num,
                'avatar_hair' => $hair_num,
                'avatar_eyes' => $eyes_num,
                'avatar_mouth' => 1,
                'positionScreen' => $position,
                'colorBubble' => $colorBubble,
                'colorText' => $colorText,
                'colorButtons' => $colorButtons,
                'colorButtonsText' => $colorButtonsText,
                'colorShine' => $colorShine,
                'enabled' => $enable,
                'initialText' => $initialText,
                'hideOnClose' => $hideOnClose,
                'avatarType' => $avatarType,
                'avatarImg' => $avatarImg,
                'avatarTalkImg' => $avatarTalkImg,
                'avatarMouthY' => $avatarMouthY,
                'avatarWidth' => $avatarWidth,
                'avatarHeight' => $avatarHeight,
                'avatarMouthWidth' => $avatarMouthWidth,
                'disableIntro' => $disableIntro,
                'useWPML'=>$useWPML,
                'disableMobile'=>$disableMobile,
                'purchaseCode'=>$purchaseCode
            )
            , array('id' => 1));
        
        
        if($purchaseCode != "" && $previousPurchaseCode != $purchaseCode){
            
            $this->checkLicense();
        }
        die();

    }

    public function loadSettings()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "iaa_params";
        $settings = $wpdb->get_results("SELECT * FROM $table_name WHERE id=1 LIMIT 1");
        $settings = $settings[0];
       // $settings->purchaseCode = '';
        echo json_encode($settings);
        die();
    }

    public function saveStepPosition()
    {
        global $wpdb;
        $stepID = $_POST['stepID'];
        $posX = $_POST['posX'];
        $posY = $_POST['posY'];
        $table_name = $wpdb->prefix . "iaa_steps";
        $step = $wpdb->get_results("SELECT * FROM $table_name WHERE id=" . $stepID . ' LIMIT 1');
        $step = $step[0];
        $content = json_decode($step->content);
        $content->previewPosX = $posX;
        $content->previewPosY = $posY;

        $wpdb->update($table_name, array('content' => stripslashes($this->jsonRemoveUnicodeSequences($content))), array('id' => $stepID));
        die();

    }

    public function newLink()
    {
        global $wpdb;
        $originID = $_POST['originStepID'];
        $destinationID = $_POST['destinationStepID'];
        $table_name = $wpdb->prefix . "iaa_links";
        $wpdb->insert($table_name, array('originID' => $originID, 'destinationID' => $destinationID, 'conditions' => '[]'));
        echo $wpdb->insert_id;
        die();
    }

    public function loadSteps()
    {
        global $wpdb;
        $rep = new stdClass();

        $table_name = $wpdb->prefix . "iaa_params";
        $params = $wpdb->get_results("SELECT * FROM $table_name");
        $rep->params = $params[0];

        $table_name = $wpdb->prefix . "iaa_steps";
        $steps = $wpdb->get_results("SELECT * FROM $table_name");
        $rep->steps = $steps;

        $table_name = $wpdb->prefix . "iaa_links";
        $links = $wpdb->get_results("SELECT * FROM $table_name");
        $rep->links = $links;

        echo($this->jsonRemoveUnicodeSequences($rep));
        die();
    }

    public function removeAllSteps()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "iaa_steps";
        $wpdb->delete($table_name, array('start' => 0));
        $wpdb->delete($table_name, array('start' => 1));
        die();
    }

    public function removeStep()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "iaa_steps";
        $step = $wpdb->get_results("SELECT * FROM $table_name WHERE id=" . $_POST['stepID']);
        $step = $step[0];
        $step = json_decode(stripslashes($step->content));

        // remove texts to wpml
        if (function_exists('icl_object_id')) {
            icl_unregister_string('AIAssistant', $_POST['stepID'] . '_text');
            foreach ($step->interactions as $interaction) {
                if ($interaction->type == 'select') {
                    icl_unregister_string('AIAssistant', $step->id . '_interaction_' . $interaction->elementID . '_label');
                    foreach ($interaction as $key => $value) {
                        if (substr($key, 0, 2) == 's_') {
                            icl_unregister_string('AIAssistant', $step->id . '_interaction_' . $interaction->elementID . '_' . $key);
                        }
                    }
                }
            }
        }

        $wpdb->delete($table_name, array('id' => $_POST['stepID']));
        $table_name = $wpdb->prefix . "iaa_links";
        $wpdb->delete($table_name, array('originID' => $_POST['stepID']));
        $wpdb->delete($table_name, array('destinationID' => $_POST['stepID']));

        die();
    }

    public function addStep()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "iaa_steps";

        $data = new stdClass();
        $data->start = $_POST['start'];
        $data->title = __('My Step', 'iaa');
        $data->text = __('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum ac posuere erat.', 'iaa');
        $data->previewPosX = $_POST['previewPosX'];
        $data->previewPosY = $_POST['previewPosY'];
        $data->actions = array();
        $data->interactions = array();


        $steps = $wpdb->get_results("SELECT * FROM $table_name");
        if (count($steps) == 0) {
            $data->start = 1;
        }

        $wpdb->insert($table_name, array('content' => $this->jsonRemoveUnicodeSequences($data)));
        $data->id = $wpdb->insert_id;
        $wpdb->update($table_name, array('content' => $this->jsonRemoveUnicodeSequences($data)), array('id' => $data->id));
        echo json_encode((array)$data);
        die();
    }

    public function loadStep()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "iaa_steps";
        $step = $wpdb->get_results("SELECT * FROM $table_name WHERE id='" . $_POST['stepID'] . "' LIMIT 1");
        $step = $step[0];
        echo $this->jsonRemoveUnicodeSequences((array)$step);
        die();
    }

    public function saveStep()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "iaa_steps";
        $step = json_decode(stripslashes($_POST['step']));
        $stepArray = array();


        foreach ($step as $key => $value) {
            $stepArray[$key] = $value;
        }
        if ($step->id > 0) {
            $wpdb->update($table_name, array('content' => stripcslashes($_POST['step'])), array('id' => $step->id));
        } else {
            $wpdb->insert($table_name, array('content' => stripcslashes($_POST['step'])));
            $step->id = $wpdb->insert_id;
        }
        // wpml
        if (function_exists('icl_object_id')) {
            foreach ($_POST['removedInter'] as $elementID) {
                icl_unregister_string('AIAssistant', $step->id . '_interaction_' . $elementID . '_label');
                for ($i = 0; $i < 20; $i++) {
                    icl_unregister_string('AIAssistant', $step->id . '_interaction_' . $elementID . '_s_' . $i . '_value');
                }
            }

            icl_register_string('AIAssistant', $step->id . '_text', $step->text);
            $i = 0;
            foreach ($step->interactions as $interation) {
                if ($interation->label && strlen($interation->label) > 0) {
                    icl_register_string('AIAssistant', $step->id . '_interaction_' . $interation->elementID . '_label', $interation->label);
                }
                if ($interation->type == 'select') {
                    foreach ($interation as $key => $value) {
                        if (substr($key, 0, 2) == 's_') {
                            icl_register_string('AIAssistant', $step->id . '_interaction_' . $interation->elementID . '_' . $key, $value);
                        }
                    }
                }
            }
            $i++;
        }

        echo $step->id;
        die();
    }

    public function changePreviewHeight()
    {
        global $wpdb;
        $height = $_POST['height'];
        $table_name = $wpdb->prefix . "iaa_params";
        $wpdb->update($table_name, array('previewHeight' => $height), array('id' => 1));
        die();
    }

    public function saveLinks()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "iaa_links";
        if(isset($_POST['links'])){
            
            if(substr(sanitize_text_field($_POST['links']),0,1)== '[' ){
                $links = json_decode(stripslashes($_POST['links']));
                if(count($links)>0 || count($wpdb->get_results("SELECT * FROM $table_name"))==1){
                    $wpdb->query("DELETE FROM $table_name WHERE id>0");
                    foreach ($links as $link) {
                        if($link->destinationID>0){
                            $wpdb->insert($table_name, array('originID' => $link->originID, 'destinationID' => $link->destinationID, 'conditions' => $this->jsonRemoveUnicodeSequences($link->conditions)));
                        }
                    }
                } 
            }
        }
        echo '1';
        die();

    }


    /**
     * Main Instance
     *
     *
     * @since 1.0.0
     * @static
     * @return Main instance
     */
    public static function instance($parent)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($parent);
        }
        return self::$_instance;
    }

    // End instance()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __(''), $this->parent->_version);
    }

// End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __(''), $this->parent->_version);
    }

// End __wakeup()
}
