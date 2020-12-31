<?php

class WPvivid_Interface_MainWP
{
    public function __construct(){
        $this->load_wpvivid_mainwp_backup_filter();
        $this->load_wpvivid_mainwp_side_bar_filter();
        $this->load_wpvivid_mainwp_backup_list_filter();
        $this->load_wpvivid_mainwp_schedule_filter();
        $this->load_wpvivid_mainwp_setting_filter();
        $this->load_wpvivid_mainwp_remote_filter();
    }

    public function load_wpvivid_mainwp_backup_filter(){
        add_filter('wpvivid_get_status_mainwp', array($this, 'wpvivid_get_status_mainwp'));
        add_filter('wpvivid_get_backup_list_mainwp', array($this, 'wpvivid_get_backup_list_mainwp'));
        add_filter('wpvivid_get_backup_schedule_mainwp', array($this, 'wpvivid_get_backup_schedule_mainwp'));
        add_filter('wpvivid_get_default_remote_mainwp', array($this, 'wpvivid_get_default_remote_mainwp'));
        add_filter('wpvivid_prepare_backup_mainwp', array($this, 'wpvivid_prepare_backup_mainwp'));
        add_filter('wpvivid_backup_now_mainwp', array($this, 'wpvivid_backup_now_mainwp'));
        add_filter('wpvivid_view_backup_task_log_mainwp', array($this, 'wpvivid_view_backup_task_log_mainwp'));
        add_filter('wpvivid_backup_cancel_mainwp', array($this, 'wpvivid_backup_cancel_mainwp'));
    }

    public function load_wpvivid_mainwp_side_bar_filter(){
        add_filter('wpvivid_read_last_backup_log_mainwp', array($this, 'wpvivid_read_last_backup_log_mainwp'));
    }

    public function load_wpvivid_mainwp_backup_list_filter(){
        add_filter('wpvivid_set_security_lock_mainwp', array($this, 'wpvivid_set_security_lock_mainwp'));
        add_filter('wpvivid_view_log_mainwp', array($this, 'wpvivid_view_log_mainwp'));
        add_filter('wpvivid_init_download_page_mainwp', array($this, 'wpvivid_init_download_page_mainwp'));
        add_filter('wpvivid_prepare_download_backup_mainwp', array($this, 'wpvivid_prepare_download_backup_mainwp'));
        add_filter('wpvivid_get_download_task_mainwp', array($this, 'wpvivid_get_download_task_mainwp'));
        add_filter('wpvivid_download_backup_mainwp', array($this, 'wpvivid_download_backup_mainwp'));
        add_filter('wpvivid_delete_backup_mainwp', array($this, 'wpvivid_delete_backup_mainwp'));
        add_filter('wpvivid_delete_backup_array_mainwp', array($this, 'wpvivid_delete_backup_array_mainwp'));
    }

    public function load_wpvivid_mainwp_schedule_filter(){
        add_filter('wpvivid_set_schedule_mainwp', array($this, 'wpvivid_set_schedule_mainwp'));
    }

    public function load_wpvivid_mainwp_setting_filter(){
        add_filter('wpvivid_set_general_setting_mainwp', array($this, 'wpvivid_set_general_setting_mainwp'));
    }

    public function load_wpvivid_mainwp_remote_filter(){
        add_filter('wpvivid_set_remote_mainwp', array($this, 'wpvivid_set_remote_mainwp'));
    }

    public function wpvivid_get_status_mainwp($data){
        $ret['result']='success';
        $list_tasks=array();
        $tasks=WPvivid_Setting::get_tasks();
        foreach ($tasks as $task)
        {
            $backup = new WPvivid_Backup_Task($task['id']);
            $list_tasks[$task['id']]=$backup->get_backup_task_info($task['id']);
            if($list_tasks[$task['id']]['task_info']['need_update_last_task']===true){
                $task_msg = WPvivid_taskmanager::get_task($task['id']);
                WPvivid_Setting::update_option('wpvivid_last_msg',$task_msg);
                apply_filters('wpvivid_set_backup_report_addon_mainwp', $task_msg);
            }
        }
        $ret['wpvivid']['task']=$list_tasks;
        $backuplist=WPvivid_Backuplist::get_backuplist();
        $schedule=WPvivid_Schedule::get_schedule();
        $ret['wpvivid']['backup_list']=$backuplist;
        $ret['wpvivid']['schedule']=$schedule;
        $ret['wpvivid']['schedule']['last_message']=WPvivid_Setting::get_last_backup_message('wpvivid_last_msg');
        WPvivid_taskmanager::delete_marked_task();
        return $ret;
    }

    public function wpvivid_get_backup_list_mainwp($data){
        $backuplist=WPvivid_Backuplist::get_backuplist();
        $ret['result']='success';
        $ret['wpvivid']['backup_list']=$backuplist;
        return $ret;
    }

    public function wpvivid_get_backup_schedule_mainwp($data){
        $schedule=WPvivid_Schedule::get_schedule();
        $ret['result']='success';
        $ret['wpvivid']['schedule']=$schedule;
        $ret['wpvivid']['schedule']['last_message']=WPvivid_Setting::get_last_backup_message('wpvivid_last_msg');
        return $ret;
    }

    public function wpvivid_get_default_remote_mainwp($data){
        global $wpvivid_plugin;
        $ret['result']='success';
        $ret['remote_storage_type']=$wpvivid_plugin->function_realize->_get_default_remote_storage();
        return $ret;
    }

    public function wpvivid_prepare_backup_mainwp($data){
        $backup_options = $data['backup'];
        global $wpvivid_plugin;
        if(isset($backup_options)&&!empty($backup_options)){
            if (is_null($backup_options)) {
                $ret['error']='Invalid parameter param:'.$backup_options;
                return $ret;
            }
            $backup_options = apply_filters('wpvivid_custom_backup_options', $backup_options);

            if(!isset($backup_options['type']))
            {
                $backup_options['type']='Manual';
                $backup_options['action']='backup';
            }
            $ret = $wpvivid_plugin->check_backup_option($backup_options, $backup_options['type']);
            if($ret['result']!='success') {
                return $ret;
            }
            $ret=$wpvivid_plugin->pre_backup($backup_options);
            if($ret['result']=='success') {
                //Check the website data to be backed up
                $ret['check']=$wpvivid_plugin->check_backup($ret['task_id'],$backup_options);
                if(isset($ret['check']['result']) && $ret['check']['result'] == 'failed') {
                    $ret['error']=$ret['check']['error'];
                    return $ret;
                }
            }
        }
        else{
            $ret['error']='Error occurred while parsing the request data. Please try to run backup again.';
            return $ret;
        }
        return $ret;
    }

    public function wpvivid_backup_now_mainwp($data){
        $task_id = $data['task_id'];
        global $wpvivid_plugin;
        if (!isset($task_id)||empty($task_id)||!is_string($task_id))
        {
            $ret['error']=__('Error occurred while parsing the request data. Please try to run backup again.', 'wpvivid-backuprestore');
            return $ret;
        }
        $task_id=sanitize_key($task_id);
        /*$ret['result']='success';
        $txt = '<mainwp>' . base64_encode( serialize( $ret ) ) . '</mainwp>';
        // Close browser connection so that it can resume AJAX polling
        if(!headers_sent()) {
            header('Content-Length: ' . ((!empty($txt)) ? strlen($txt) : '0'));
            header('Connection: close');
            header('Content-Encoding: none');
        }
        if ( session_id() ) {
            session_write_close();
        }
        echo $txt;
        // These two added - 19-Feb-15 - started being required on local dev machine, for unknown reason (probably some plugin that started an output buffer).
        $ob_level = ob_get_level();
        while ($ob_level > 0) {
            ob_end_flush();
            $ob_level--;
        }
        flush();
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();*/

        $wpvivid_plugin->flush($task_id, true);
        //Start backup site
        $wpvivid_plugin->backup($task_id);
        $ret['result']='success';
    }

    public function wpvivid_view_backup_task_log_mainwp($data){
        $backup_task_id = $data['id'];
        global $wpvivid_plugin;
        if (!isset($backup_task_id)||empty($backup_task_id)||!is_string($backup_task_id)){
            $ret['error']='Reading the log failed. Please try again.';
            return $ret;
        }
        $backup_task_id = sanitize_key($backup_task_id);
        $ret=$wpvivid_plugin->function_realize->_get_log_file('tasklog', $backup_task_id);
        if($ret['result'] == 'success') {
            $file = fopen($ret['log_file'], 'r');
            if (!$file) {
                $ret['result'] = 'failed';
                $ret['error'] = __('Unable to open the log file.', 'wpvivid-backuprestore');
                return $ret;
            }
            $buffer = '';
            while (!feof($file)) {
                $buffer .= fread($file, 1024);
            }
            fclose($file);
            $ret['result'] = 'success';
            $ret['data'] = $buffer;
        }
        else{
            $ret['error']='Unknown error';
        }
        return $ret;
    }

    public function wpvivid_backup_cancel_mainwp($data){
        global $wpvivid_plugin;
        $ret=$wpvivid_plugin->function_realize->_backup_cancel();
        return $ret;
    }

    public function wpvivid_read_last_backup_log_mainwp($data){
        $log_file_name = $data['log_file_name'];
        global $wpvivid_plugin;
        if(!isset($log_file_name)||empty($log_file_name)||!is_string($log_file_name))
        {
            $ret['result']='failed';
            $ret['error']=__('Reading the log failed. Please try again.', 'wpvivid-backuprestore');
            return $ret;
        }
        $log_file_name=sanitize_text_field($log_file_name);
        $ret=$wpvivid_plugin->function_realize->_get_log_file('lastlog', $log_file_name);
        if($ret['result'] == 'success') {
            $file = fopen($ret['log_file'], 'r');
            if (!$file) {
                $ret['result'] = 'failed';
                $ret['error'] = __('Unable to open the log file.', 'wpvivid-backuprestore');
                return $ret;
            }
            $buffer = '';
            while (!feof($file)) {
                $buffer .= fread($file, 1024);
            }
            fclose($file);
            $ret['result'] = 'success';
            $ret['data'] = $buffer;
        }
        else{
            $ret['error']='Unknown error';
        }
        return $ret;
    }

    public function wpvivid_set_security_lock_mainwp($data){
        $backup_id = $data['backup_id'];
        $lock = $data['lock'];
        if(!isset($backup_id)||empty($backup_id)||!is_string($backup_id)){
            $ret['error']='Backup id not found';
            return $ret;
        }
        if(!isset($lock)){
            $ret['error']='Invalid parameter param: lock';
            return $ret;
        }
        $backup_id=sanitize_key($backup_id);
        if($lock==0||$lock==1) {
        }
        else {
            $lock=0;
        }
        WPvivid_Backuplist::set_security_lock($backup_id,$lock);
        $backuplist=WPvivid_Backuplist::get_backuplist();
        $ret['wpvivid']['backup_list']=$backuplist;
        return $ret;
    }

    public function wpvivid_view_log_mainwp($data){
        $backup_id = $data['id'];
        global $wpvivid_plugin;
        if (!isset($backup_id)||empty($backup_id)||!is_string($backup_id)){
            $ret['error']='Backup id not found';
            return $ret;
        }
        $backup_id=sanitize_key($backup_id);
        $ret=$wpvivid_plugin->function_realize->_get_log_file('backuplist', $backup_id);
        if($ret['result'] == 'success') {
            $file = fopen($ret['log_file'], 'r');
            if (!$file) {
                $ret['result'] = 'failed';
                $ret['error'] = __('Unable to open the log file.', 'wpvivid-backuprestore');
                return $ret;
            }
            $buffer = '';
            while (!feof($file)) {
                $buffer .= fread($file, 1024);
            }
            fclose($file);
            $ret['data'] = $buffer;
        }
        else{
            $ret['error']='Unknown error';
        }
        return $ret;
    }

    public function wpvivid_init_download_page_mainwp($data){
        $backup_id = $data['backup_id'];
        global $wpvivid_plugin;
        if(!isset($backup_id)||empty($backup_id)||!is_string($backup_id)) {
            $ret['error']='Invalid parameter param:'.$backup_id;
            return $ret;
        }
        else {
            $backup_id=sanitize_key($backup_id);
            return $wpvivid_plugin->init_download($backup_id);
        }
    }

    public function wpvivid_prepare_download_backup_mainwp($data){
        $backup_id = $data['backup_id'];
        $file_name = $data['file_name'];
        if(!isset($backup_id)||empty($backup_id)||!is_string($backup_id))
        {
            $ret['error']='Invalid parameter param:'.$backup_id;
            return $ret;
        }
        if(!isset($file_name)||empty($file_name)||!is_string($file_name))
        {
            $ret['error']='Invalid parameter param:'.$file_name;
            return $ret;
        }
        $download_info=array();
        $download_info['backup_id']=sanitize_key($backup_id);
        $download_info['file_name'] = $file_name;

        @set_time_limit(600);
        if (session_id())
            session_write_close();
        try
        {
            $downloader=new WPvivid_downloader();
            $downloader->ready_download($download_info);
        }
        catch (Exception $e)
        {
            $message = 'A exception ('.get_class($e).') occurred '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().')';
            error_log($message);
            return array('error'=>$message);
        }
        catch (Error $e)
        {
            $message = 'A error ('.get_class($e).') has occurred: '.$e->getMessage().' (Code: '.$e->getCode().', line '.$e->getLine().' in '.$e->getFile().')';
            error_log($message);
            return array('error'=>$message);
        }

        $ret['result']='success';
        return $ret;
    }

    public function wpvivid_get_download_task_mainwp($data){
        $backup_id = $data['backup_id'];
        if(!isset($backup_id)||empty($backup_id)||!is_string($backup_id)) {
            $ret['error']='Invalid parameter param:'.$backup_id;
            return $ret;
        }
        else {
            $backup = WPvivid_Backuplist::get_backup_by_id($backup_id);
            if ($backup === false) {
                $ret['result'] = WPVIVID_FAILED;
                $ret['error'] = 'backup id not found';
                return $ret;
            }
            $backup_item = new WPvivid_Backup_Item($backup);
            $ret = $backup_item->update_download_page($backup_id);
            return $ret;
        }
    }

    public function wpvivid_download_backup_mainwp($data){
        $backup_id = $data['backup_id'];
        $file_name = $data['file_name'];
        global $wpvivid_plugin;
        if(!isset($backup_id)||empty($backup_id)||!is_string($backup_id)) {
            $ret['error']='Invalid parameter param: backup_id';
            return $ret;
        }
        if(!isset($file_name)||empty($file_name)||!is_string($file_name)) {
            $ret['error']='Invalid parameter param: file_name';
            return $ret;
        }
        $backup_id=sanitize_key($backup_id);
        $cache=WPvivid_taskmanager::get_download_cache($backup_id);
        if($cache===false) {
            $wpvivid_plugin->init_download($backup_id);
            $cache=WPvivid_taskmanager::get_download_cache($backup_id);
        }
        $path=false;
        if(array_key_exists($file_name,$cache['files'])) {
            if($cache['files'][$file_name]['status']=='completed') {
                $path=$cache['files'][$file_name]['download_path'];
                $download_url = $cache['files'][$file_name]['download_url'];
            }
        }
        if($path!==false) {
            if (file_exists($path)) {
                $ret['download_url'] = $download_url;
                $ret['size'] = filesize($path);
            }
        }
        return $ret;
    }

    public function wpvivid_delete_backup_mainwp($data){
        $backup_id = $data['backup_id'];
        $force_del = $data['force'];
        global $wpvivid_plugin;
        if(!isset($backup_id)||empty($backup_id)||!is_string($backup_id)) {
            $ret['error']='Invalid parameter param: backup_id.';
            return $ret;
        }
        if(!isset($force_del)){
            $ret['error']='Invalid parameter param: force.';
            return $ret;
        }
        if($force_del==0||$force_del==1) {
        }
        else {
            $force_del=0;
        }
        $backup_id=sanitize_key($backup_id);
        $ret=$wpvivid_plugin->delete_backup_by_id($backup_id, $force_del);
        $backuplist=WPvivid_Backuplist::get_backuplist();
        $ret['wpvivid']['backup_list']=$backuplist;
        return $ret;
    }

    public function wpvivid_delete_backup_array_mainwp($data){
        $backup_id_array = $data['backup_id'];
        global $wpvivid_plugin;
        if(!isset($backup_id_array)||empty($backup_id_array)||!is_array($backup_id_array)) {
            $ret['error']='Invalid parameter param: backup_id';
            return $ret;
        }
        $ret=array();
        foreach($backup_id_array as $backup_id)
        {
            $backup_id=sanitize_key($backup_id);
            $ret=$wpvivid_plugin->delete_backup_by_id($backup_id);
        }
        $backuplist=WPvivid_Backuplist::get_backuplist();
        $ret['wpvivid']['backup_list']=$backuplist;
        return $ret;
    }

    public function wpvivid_set_schedule_mainwp($data){
        $schedule = $data['schedule'];
        $ret=array();
        try {
            if(isset($schedule)&&!empty($schedule)) {
                $json = $schedule;
                $json = stripslashes($json);
                $schedule = json_decode($json, true);
                if (is_null($schedule)) {
                    $ret['error']='bad parameter';
                    return $ret;
                }
                $ret=WPvivid_Schedule::set_schedule_ex($schedule);
                if($ret['result']!='success') {
                    return $ret;
                }
            }
            $ret['result']='success';
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            return array('error'=>$message);
        }
        return $ret;
    }

    public function wpvivid_set_general_setting_mainwp($data){
        $setting = $data['setting'];
        $ret=array();
        try {
            if(isset($setting)&&!empty($setting)) {
                $json_setting = $setting;
                $json_setting = stripslashes($json_setting);
                $setting = json_decode($json_setting, true);
                if (is_null($setting)) {
                    $ret['error']='bad parameter';
                    return $ret;
                }
                WPvivid_Setting::update_setting($setting);
            }

            $ret['result']='success';
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            return array('error'=>$message);
        }
        return $ret;
    }

    public function wpvivid_set_remote_mainwp($data){
        $remote = $data['remote'];
        global $wpvivid_plugin;
        $ret=array();
        try {
            if(isset($remote)&&!empty($remote)) {
                $json = $remote;
                $json = stripslashes($json);
                $remote = json_decode($json, true);
                if (is_null($remote)) {
                    $ret['error']='bad parameter';
                    return $ret;
                }
                $wpvivid_plugin->function_realize->_set_remote($remote);
            }
            $ret['result']='success';
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            return array('error'=>$message);
        }
        return $ret;
    }
}