<?php

if (!defined('ABSPATH'))
    exit;

class IAA_Core
{

    /**
     * The single instance
     * @var    object
     * @access  private
     * @since    1.0.0
     */
    private static $_instance = null;

    /**
     * Settings class object
     * @var     object
     * @access  public
     * @since   1.0.0
     */
    public $settings = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;

    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_token;

    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;

    /**
     * Suffix for Javascripts.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $templates_url;

    /**
     * Suffix for Javascripts.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $script_suffix;

    /**
     * For menu instance
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $menu;

    /**
     * For template
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $plugin_slug;

    /*
     *  Current forms on page
     */
    public $currentForms;

    /*
     * Must load or not the js files ?
     */
    private $add_script;

    /**
     * Constructor function.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function __construct($file = '', $version = '1.6.0')
    {
        $this->_version = $version;
        $this->_token = 'iaa';
        $this->plugin_slug = 'iaa';
        $this->currentForms = array();

        $this->file = $file;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'), 10, 1);
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_styles'), 10, 1);
        add_action('wp_ajax_nopriv_iaa_sendDialogEmail', array($this, 'sendDialogEmail'));
        add_action('wp_ajax_iaa_sendDialogEmail', array($this, 'sendDialogEmail'));
        add_action('wp_head', array($this, 'apply_styles'));
        add_action('plugins_loaded', array($this, 'init_localization'));

    }

    /*
     * Plugin init localization
     */
    public function init_localization()
    {
        $moFiles = scandir(trailingslashit($this->dir) . 'languages/');
        foreach ($moFiles as $moFile) {
            if (strlen($moFile) > 3 && substr($moFile, -3) == '.mo' && strpos($moFile, get_locale()) > -1) {
                load_textdomain('iaa', trailingslashit($this->dir) . 'languages/' . $moFile);
            }
        }
    }

    public function frontend_enqueue_styles($hook = '')
    {
        $settings = $this->getSettings();
        if ($settings->enabled || (isset($_GET['iaa_action'])&& $_GET['iaa_action'] == 'preview')) {
            global $wp_styles;
            wp_register_style($this->_token . '-iaa-reset', esc_url($this->assets_url) . 'css/iaa_frontend-reset.min.css', array(), $this->_version);
            wp_enqueue_style($this->_token . '-iaa-reset');
            wp_register_style($this->_token . '-iaa-avatars', esc_url($this->assets_url) . 'css/iaa_avatars.min.css', array(), $this->_version);
            wp_enqueue_style($this->_token . '-iaa-avatars');
            wp_register_style($this->_token . '-frontend', esc_url($this->assets_url) . 'css/iaa_frontend.min.css', array(), $this->_version);
            wp_enqueue_style($this->_token . '-frontend');
        }
    }
    private function jsonRemoveUnicodeSequences($struct) {
        return json_encode($struct,JSON_UNESCAPED_UNICODE);
       // return preg_replace("/\\\\u([a-f0-9]{4})/e", "iconv('UCS-4LE','UTF-8',pack('V', hexdec('U$1')))", json_encode($struct));
    }

    public function frontend_enqueue_scripts($hook = '')
    {
        $settings = $this->getSettings();
        
        $chkIframe = false;
        if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'wp-admin') >0) {
            $chkIframe = true;
        }
        
        if ($settings->enabled || (isset($_GET['iaa_action'])&& $_GET['iaa_action'] == 'preview') || $chkIframe) {
            wp_register_script($this->_token . '-frontend', esc_url($this->assets_url) . 'js/iaa_frontend.min.js', array('jquery'), $this->_version);
            wp_enqueue_script($this->_token . '-frontend');

            global $wpdb;
            $table_name = $wpdb->prefix . "iaa_steps";
            $steps = $wpdb->get_results("SELECT * FROM $table_name");

             $wpmlLang = "";
             $wmplDef = strtolower(substr(get_locale(),0,2));

            if (function_exists('icl_object_id') && $settings->useWPML == 1) {
                if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
                    $wpmlLang = ICL_LANGUAGE_CODE;
                  }
                  
                foreach ($steps as $step) {
                    $step->content = json_decode(($step->content));
                    $step->content->text = icl_t('AIAssistant', $step->id.'_text', $step->content->text);
                    foreach($step->content->interactions as $interaction){
                        if($interaction->label && strlen($interaction->label)>0){
                            $interaction->label = icl_t('AIAssistant',$step->id.'_interaction_'.$interaction->elementID.'_label',$interaction->label);
                        }
                        if($interaction->type == 'select'){
                            foreach($interaction as $key=>$value){
                                if(substr($key,0,2)=='s_'){
                                    eval('$interaction->'.$key.' =  icl_t("AIAssistant",$step->id."_interaction_".$interaction->elementID."_".'.$key.',$interaction->'.$key.');');
                                }
                            }
                        }
                    }

                    $step->content =  $this->jsonRemoveUnicodeSequences($step->content);
                }
            }

            $table_name = $wpdb->prefix . "iaa_links";
            $links = $wpdb->get_results("SELECT * FROM $table_name");
            $settings->purchaseCode = "";
            $js_data[] = array(
                'assetsUrl' => esc_url($this->assets_url),
                'settings' => $settings,
                'steps' => $steps,
                'links' => $links,
                'txt_btnClicked' => __('Clicked button', 'iaa'),
                'ajaxurl' => admin_url('admin-ajax.php'),
                'siteUrl' => get_site_url(),
                'wpmlLang'=>$wpmlLang,
                'wmplDef'=>$wmplDef
            );
            wp_localize_script($this->_token . '-frontend', 'iaa_data', $js_data);
        }
    }

    public function sendDialogEmail()
    {
        $email = ($_POST['sendTo']);
        $msg = ($_POST['dialog']);
        $subject = ($_POST['subject']);

        $headers = "Return-Path: " . get_bloginfo('admin_email') . "\n";
        $headers .= "From:" . get_bloginfo('admin_email') . "\n";
        $headers .= "X-Mailer: PHP " . phpversion() . "\n";
        $headers .= "Reply-To: " . get_bloginfo('admin_email') . "\n";
        $headers .= "X-Priority: 3 (Normal)\n";
        $headers .= "Mime-Version: 1.0\n";
        $headers .= "Content-type: text/html; charset=utf-8\n";

        $content = '<p>' . nl2br(stripslashes($msg)) . '</p>';

        wp_mail($email, $subject, $content, $headers);
        die();
    }


    public function apply_styles()
    {
        $settings = $this->getSettings();
        $output = '';
        $output .= '#iaa_bootstraped.iaa_bootstraped #iaa_talkBubble, #iaa_avatarPreviewContainer #iaa_talkBubble {';
        $output .= ' background-color:' . $settings->colorBubble . '; ';
        $output .= ' color:' . $settings->colorText . '; ';
        $output .= '}';
        $output .= "\n";
        $output .= '#iaa_bootstraped.iaa_bootstraped #iaa_talkBubble, #iaa_bootstraped.iaa_bootstraped #iaa_talkBubble *, #iaa_avatarPreviewContainer #iaa_talkBubble, #iaa_avatarPreviewContainer #iaa_talkBubble *  {';
        $output .= ' color:' . $settings->colorText . '; ';
        $output .= '}';
        $output .= '#iaa_bootstraped.iaa_bootstraped #iaa_talkBubble .iaa_btn, #iaa_avatarPreviewContainer #iaa_talkBubble .iaa_btn {';
        $output .= ' background-color:' . $settings->colorButtons . '; ';
        $output .= ' color:' . $settings->colorButtonsText . '; ';
        $output .= '}';
        $output .= "\n";

        $output .= '#iaa_bootstraped.iaa_bootstraped #iaa_talkBubble:after, #iaa_avatarPreviewContainer #iaa_talkBubble:after {';
        $output .= ' border-color:' . $settings->colorBubble . ' transparent transparent transparent; ';
        $output .= '}';
        $output .= "\n";

        $output .= '#iaa_avatarPreviewContainer #iaa_talkBubble .iaa_talkInteraction .iaa_field input:focus,#iaa_avatarPreviewContainer #iaa_talkBubble .iaa_talkInteraction .iaa_field select:focus,#iaa_avatarPreviewContainer #iaa_talkBubble .iaa_talkInteraction .iaa_field textarea:focus{';
        $output .= ' border-color:' . $settings->colorButtons . '; ';
        $output .= '}';
        $output .= "\n";

        $output .= '.iaa_selectedDom {
    -moz-box-shadow: 0px 0px 40px 0px ' . $settings->colorShine . ';
    -webkit-box-shadow: 0px 0px 40px 0px ' . $settings->colorShine . ';
    -o-box-shadow: 0px 0px 40px 0px ' . $settings->colorShine . ';
    box-shadow: 0px 0px 40px 0px ' . $settings->colorShine . ';
    -webkit-animation: glow 1500ms infinite;
    -moz-animation: glow 1500ms infinite;
    -o-animation: glow 1500ms infinite;
    animation: glow 1500ms infinite;
}

@-o-keyframes glow {
    0% {
        -o-box-shadow: 0px 0px 10px 0px ' . $settings->colorShine . ';
    }
    50% {
        -o-box-shadow: 0px 0px 40px 0px ' . $settings->colorShine . ';
    }
    100% {
        -o-box-shadow: 0px 0px 10px 0px ' . $settings->colorShine . ';
    }
}

@-moz-keyframes glow {
    0% {
        -moz-box-shadow: 0px 0px 10px 0px ' . $settings->colorShine . ';
    }
    50% {
        -moz-box-shadow: 0px 0px 40px 0px ' . $settings->colorShine . ';
    }
    100% {
        -moz-box-shadow: 0px 0px 10px 0px ' . $settings->colorShine . ';
    }
}

@-webkit-keyframes glow {
    0% {
        -webkit-box-shadow: 0px 0px 10px 0px ' . $settings->colorShine . ';
    }
    50% {
        -webkit-box-shadow: 0px 0px 40px 0px ' . $settings->colorShine . ';
    }
    100% {
        -webkit-box-shadow: 0px 0px 10px 0px ' . $settings->colorShine . ';
    }
}

@keyframes glow {
    0% {
        box-shadow: 0px 0px 10px 0px ' . $settings->colorShine . ';
    }
    50% {
        box-shadow: 0px 0px 40px 0px ' . $settings->colorShine . ';
    }
    100% {
        box-shadow: 0px 0px 10px 0px ' . $settings->colorShine . ';
    }
}';
        $output .= "\n";


        if ($output != '') {
            $output = "\n<style >\n" . $output . "</style>\n";
            echo $output;
        }

    }

    /**
     * Main BSS_Core Instance
     *
     *
     * @since 1.0.0
     * @static
     * @see BSS_Core()
     * @return Main BSS_Core instance
     */
    public static function instance($file = '', $version = '1.0.0')
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
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
        //  _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

// End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        //  _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

// End __wakeup()

    /**
     * Return settings.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function getSettings()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "iaa_params";
        $settings = $wpdb->get_results("SELECT * FROM $table_name WHERE id=1 LIMIT 1");
        return $settings[0];
    }
    // End getSettings()


    /**
     * Log the plugin version number.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    private function _log_version_number()
    {
        update_option($this->_token . '_version', $this->_version);
    }

}
