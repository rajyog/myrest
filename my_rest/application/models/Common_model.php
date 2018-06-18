<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Common_model extends CI_Model {

    private $development = false;
    private $stripe_development = false;
    private $dev_apns_cert;
    private $Secret;

    public function __construct() {
        parent::__construct();
        if ($this->development == TRUE) {
            $this->dev_apns_cert = APPPATH . 'libraries\push_dev.pem';
        } else {
            $this->dev_apns_cert = APPPATH . 'libraries\push_prod.pem';
        }

    }

    private $user_session = 'user_session';
    private $tbl_user = 'tbl_user';
    private $tbl_user_vault = 'tbl_user_vault';

    public function getTruckTable() {
        return $this->tbl_truck;
    }

    public function getUserTable() {
        return $this->tbl_user;
    }


// fetching result from table
    public function select_result($table_name, $where = [], $limit = '', $offset = '') {
        return $this->db->get_where($table_name, $where, $limit = '', $offset = '')->result();
    }

// fetching row from table
    public function select_row($table_name, $where) {
        return $this->db->get_where($table_name, $where)->row();
    }

    /**
     * Insert into table
     */
    public function insert($table, $data) {
        $this->db->insert($table, $data);
        return $this->db->insert_id();
    }

    /**
     * Update 
     */
    public function update($field, $field_value, $table, $data) {
        $this->db->where($field, $field_value);

        $data['updated_at'] = DATETIME;
        return $this->db->update($table, $data);
    }

    /**
     * Delete
     */
    public function delete($field, $field_value, $table) {
        $this->db->where($field, $field_value);
        return $this->db->delete($table);
    }

    /**
     * check if existing
     */
    public function checkExist($field, $value, $table) {
        $this->db->where($field, $value);
        return $this->db->get($table)->row();
    }

    /**
     * Get secure key Token
     */
    public function getSecureKey() {
        $string = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $stamp = time();
        $secure_key = $pre = $post = '';
        for ($p = 0; $p <= 10; $p++) {
            $pre .= substr($string, rand(0, strlen($string) - 1), 1);
        }

        for ($i = 0; $i < strlen($stamp); $i++) {
            $key = substr($string, substr($stamp, $i, 1), 1);
            $secure_key .= (rand(0, 1) == 0 ? $key : (rand(0, 1) == 1 ? strtoupper($key) : rand(0, 9)));
        }

        for ($p = 0; $p <= 10; $p++) {
            $post .= substr($string, rand(0, strlen($string) - 1), 1);
        }
        return $pre . '-' . $secure_key . $post;
    }

    /**
     * Add user session data
     */
    public function insertUserSession($data1) {
        $this->db->insert($this->user_session, $data1);
        return $this->db->insert_id();
    }

    /**
     * Add user session data
     */
    public function insertTruckSession($data1) {
        $this->db->insert('truck_session', $data1);
        return $this->db->insert_id();
    }

    /**
     * Get user session data
     */
    public function getUserSession($user_id, $token, $type) {
        $this->db->where('is_active', 1);
        $this->db->where('user_id', $user_id);
        $this->db->where('token', $token);
        $this->db->where('user_type', $type);
        return $this->db->get($this->user_session)->row();
    }

    /**
     * Get current user session data
     */
    public function getSessionInfo($secret_log_id) {
        $this->db->where('is_active', 1);
        $this->db->where('session_id', $secret_log_id);
        return $this->db->get($this->user_session)->row();
    }

    /**
     * Logout session data
     */
    public function logoutUser($secret_log_id) {
        $data = array('is_active' => 0, 'end_date' => DATETIME);
        $this->db->where('session_id', $secret_log_id);
        return $this->db->update($this->user_session, $data);
    }

    /**
     * Delete users session data
     */
    public function deleteUserSession($user_id) {
        $this->db->where('user_id', $user_id);
        return $this->db->delete($this->user_session);
    }

    /**
     * Generate random alphanumeric string
     */
    public function generatePassword($length = 7) {
        $post = '';
        $string = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($p = 0; $p <= $length; $p++) {
            $post .= substr($string, rand(0, strlen($string) - 1), 1);
        }
        return $post;
    }

    public function encryptIt($q) {
        $cryptKey = 'password_key';
        $qEncoded = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($cryptKey), $q, MCRYPT_MODE_CBC, md5(md5($cryptKey))));
        return( $qEncoded );
    }

    public function decryptIt($q) {
        $cryptKey = 'password_key';
        $qDecoded = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($cryptKey), base64_decode($q), MCRYPT_MODE_CBC, md5(md5($cryptKey))), "\0");
        return( $qDecoded );
    }

    function image_upload($image_name, $tmp_name, $folder_name, $type) {

        $main_dir = "uploads";
        $tmp_arr = explode(".", $image_name);
        $img_extn = end($tmp_arr);
        $new_image_name = $type . '_' . uniqid() . '_' . date("YmdHis") . '.' . $img_extn;
        $flag = 0;
        if (!file_exists('../' . $main_dir)) {

            @mkdir($main_dir, 0777, true);
            if (!file_exists('../' . $main_dir . '/' . $folder_name)) {
                @mkdir($main_dir . '/' . $folder_name, 0777, true);
            }
        } elseif (!file_exists($main_dir . '/' . $folder_name)) {
            @mkdir($main_dir . '/' . $folder_name, 0777, true);
        }
        if (file_exists($main_dir . '/' . $folder_name . '/' . $new_image_name)) {
            return true;
        } else {

            move_uploaded_file($tmp_name, $main_dir . "/" . $folder_name . "/" . $new_image_name);
            $flag = 1;
            return $new_image_name;
        }
    }

    public function generateOrderCode() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length - 1 in cache
        for ($i = 0; $i < 6; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    function iOS_send_push_notification($device, $message, $badge, $push_type) {
        $total_badge = intval(@$badge);
        $sound = 'default';
        $payload = array();
        $payload['aps'] = array('alert' => $message, 'badge' => $total_badge,
            'sound' => $sound,
            'content-available' => 1,
            'message' => array('message' => $message, 'push_type' => $push_type)
        );
        $payload = json_encode($payload);

        $apns_url = NULL;
        $apns_cert = NULL;
        $apns_port = 2195;
        $development = $this->development;
        if ($development) {
            $apns_url = 'gateway.sandbox.push.apple.com';
//            $apns_cert = APPPATH . 'libraries/push/socialpush_dev.pem';
            $apns_cert = $this->dev_apns_cert;
        } else {
            $apns_url = 'gateway.push.apple.com';
            $apns_cert = $this->dis_apns_cert;
        }
        $stream_context = stream_context_create();
        stream_context_set_option($stream_context, 'ssl', 'local_cert', $apns_cert);
        $apns = stream_socket_client('ssl://' . $apns_url . ':' . $apns_port, $error, $error_string, 2, STREAM_CLIENT_CONNECT, $stream_context);

        $apns_message = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $device)) . chr(0) . chr(strlen($payload)) . $payload;

        $res = fwrite($apns, $apns_message);

        if (!$res) {
//return 'Message not delivered' . PHP_EOL;
            return 1;
        } else {
            return 0;
        }

        @socket_close($apns);
        @fclose($apns);
    }

    function iOS_send_push_notification_for_schedule($device, $message, $badge, $push_type, $truck_id) {
        $total_badge = intval(@$badge);
        $sound = 'default';
        $payload = array();
        $payload['aps'] = array('alert' => $message, 'badge' => $total_badge,
            'sound' => $sound,
            'content-available' => 1,
            'message' => array('message' => $message, 'push_type' => $push_type, 'truck_id' => $truck_id)
        );
        $payload = json_encode($payload);

        $apns_url = NULL;
        $apns_cert = NULL;
        $apns_port = 2195;
        $development = $this->development;
        if ($development) {
            $apns_url = 'gateway.sandbox.push.apple.com';
//            $apns_cert = APPPATH . 'libraries/push/socialpush_dev.pem';
            $apns_cert = $this->dev_apns_cert;
        } else {
            $apns_url = 'gateway.push.apple.com';
            $apns_cert = $this->dis_apns_cert;
        }
        $stream_context = stream_context_create();
        stream_context_set_option($stream_context, 'ssl', 'local_cert', $apns_cert);
        $apns = stream_socket_client('ssl://' . $apns_url . ':' . $apns_port, $error, $error_string, 2, STREAM_CLIENT_CONNECT, $stream_context);

        $apns_message = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $device)) . chr(0) . chr(strlen($payload)) . $payload;

        $res = fwrite($apns, $apns_message);

        if (!$res) {
            //return 'Message not delivered' . PHP_EOL;
            return 1;
        } else {
            return 0;
        }

        @socket_close($apns);
        @fclose($apns);
    }

}
