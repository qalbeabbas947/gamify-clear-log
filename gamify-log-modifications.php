<?php
/**
 * Plugin Name: Gamify Log Modifications
 * Description: This add-on provides the gamify log modifications.
 * Version: 1.0
 * Author: Dalilk
 * Author URI: dalilk.academy
 * Text Domain: gm-log-mod
 */

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gamify_Log_Modifications
 */
class Gamify_Log_Modifications {

    const VERSION = '1.0';

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof Gamify_Log_Modifications ) ) {
            self::$instance = new self;

            self::$instance->hooks();
        }

        return self::$instance;
    }

    /**
     * Define Hooks
     */
    private function hooks() {
        add_filter( 'gamipress_general_settings_fields', [ $this, 'general_meta_boxes'], 99999, 1 );
        add_action( 'init', [ $this, 'process_log' ] );
        add_action('process_gamify_log_del', [ $this, 'process_gamify_log_del_callback']);
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_script'] );
        add_action( 'wp_ajax_gmlm_delete_load_with_ajax', [ $this,'gmlm_delete_load_with_ajax_callback'] );
        add_filter('cron_schedules',[ $this,'gmlm_cron_schedules']);
    }
    function gmlm_cron_schedules($schedules){
        if(!isset($schedules["5min"])){
            $schedules["5min"] = array(
                'interval' => 5*60,
                'display' => __('Once every 5 minutes'));
        }
        
        return $schedules;
    }
    
    function gmlm_delete_load_with_ajax_callback() {
        
        $is_allowed = get_option('gamipress_log_auto_clear_allowed');
        $this->start_process();
        update_option('gamipress_log_auto_clear_allowed', 'yes');
        echo $is_allowed;
        exit;
    }

    function start_process() {
        set_time_limit(0);
        global $wpdb; // this is how you get access to the database

        // $sub_query1 = "delete FROM `".$wpdb->prefix."gamipress_logs_meta` where log_id in (SELECT log_id FROM `".$wpdb->prefix."gamipress_logs` where `date`<'".date('Y-m-d', strtotime("-32 Days"))."')";
        // $wpdb->query($sub_query1);
        // $sub_query2 = "delete FROM `".$wpdb->prefix."gamipress_logs` where  `date`<'".date('Y-m-d', strtotime("-32 Days"))."'";
        // $wpdb->query($sub_query2);


        $query = 'SELECT * FROM `'.$wpdb->prefix."gamipress_logs` where `date`<'".date('Y-m-d', strtotime("-32 Days"))."' limit 50000";
        $results = $wpdb->get_results($query);
        
        foreach( $results as $res ) {
            $sub_query1 = "delete FROM `".$wpdb->prefix."gamipress_logs_meta` where log_id='".$res->log_id."'";
            $wpdb->query($sub_query1);
            $sub_query2 = "delete FROM `".$wpdb->prefix."gamipress_logs` where log_id='".$res->log_id."'";
            $wpdb->query($sub_query2);
        }
    }

    function admin_script( $hook ) {
        wp_enqueue_script( 'gamify_log_del', plugin_dir_url( __FILE__ ) . 'js/admin.js', array(), time() );
    }

    /**
     * Add Reset Course Progress submenu page under learndash menus
     */
    public function process_log() {
        $is_allowed = get_option('gamipress_log_auto_clear_allowed');
        if( $is_allowed == 'yes' ) {
            if( $this->is_clear_log() ) {
                if (! wp_next_scheduled ( 'process_gamify_log_del' )) {
                    wp_schedule_event(time(), 'daily', 'process_gamify_log_del');
                }
            }
        }
    }

    
 
    function process_gamify_log_del_callback() {
        $is_allowed = get_option('gamipress_log_auto_clear_allowed');
        if( $is_allowed == 'yes' ) {
            if( $this->is_clear_log() ) 
            {
                $this->start_process();
            }
        }
    }

    function is_clear_log() {
        if( function_exists('gamipress_get_option') )
            return (bool) gamipress_get_option( 'gmlm_clear_log', false );
        
         return false;
        
    }

    /**
     * Add new field on gamify settings
     */
    public function general_meta_boxes($meta) {
        if( array_key_exists( 'minimum_role', $meta ) ) {
            $is_allowed = get_option('gamipress_log_auto_clear_allowed');
            if( $is_allowed == 'yes' ) {
                $meta['gmlm_clear_log'] = array(
                    'name' => __( 'Auto Clear Old logs', 'gamipress' ),
                    'desc' => __( 'Allow the system to auto clear the log older than month.', 'gamipress' ),
                    'type' => 'checkbox',
                    'classes' => 'gamipress-switch',
                );
            }
            
            $meta['gmlm_clear_log_button'] = array(
                'type'  => 'button',
                'class' => 'cmb2-gmlm_clear_log_button-button button-secondary cmb2-gmlm_clear_log_button-list',
                'value' => __( 'Clear the Log', 'cmb2' ),
                'desc' => __( 'After clicking on the "Clear the Log" button below, maximum 50,000 records will be removed from the log at a time.', 'gamipress' ),
                'name'  => __( 'Clear the Log', 'cmb2' ),
                'id'    => 'cmb2-upload-button-switch2',
            );
        }
        
        return $meta;
    }
    
    
}

/**
 * @return bool
 */
function GamifyLM() {

    return Gamify_Log_Modifications::instance();
}

add_action( 'plugins_loaded', 'GamifyLM' );

require_once(trailingslashit ( plugin_dir_path ( __FILE__ ) . 'includes' ).'db.php');