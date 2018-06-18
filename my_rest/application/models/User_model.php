<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {
    /*
     * get login user
     */

    public function getUserLogin($emailId, $Password) {
        $this->db->where('email', $emailId);
        $this->db->where('password', $Password);
        $this->db->where('status', 1);
        $this->db->where('is_deleted', 0);
        $data = $this->db->get($this->common->getUserTable())->row();

        if ($data) {
            $data->image_path = base_url('assets/images/default.png');
            if ($data->profilepic != '')
                $data->image_path = base_url() . PROFILE_PIC . $data->profilepic;
        }
        return $data;
    }

    /**
     * Add user
     */
    public function addUser($data) {
        $this->db->insert($this->common->getUserTable(), $data);
        return $this->db->insert_id();
    }

    public function register_user($user_firstname, $user_lastname, $user_username, $user_dateofbirth, $emailid, $enPassword, $entity_id, $user_devicetoken, $devicetype, $login_type, $user_created) {

        $this->db->set('user_firstname', $user_firstname);
        $this->db->set('user_lastname', $user_lastname);
        $this->db->set('user_username', $user_username);
        $this->db->set('user_dateofbirth', $user_dateofbirth);
        $this->db->set('user_emailid', $emailid);
        $this->db->set('user_password', $enPassword);
        $this->db->set('entity_id', $entity_id);
        $this->db->set('user_devicetoken', $user_devicetoken);
        $this->db->set('user_devicetype', $devicetype);
        $this->db->set('login_type', $login_type);
        $this->db->set('user_created', $user_created);

        $data = $this->db->insert($this->common->getUserTable());
        if ($data) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update User data by id
     */
    public function updateUserData($data, $id) {
        $this->db->where('user_id', $id);
        return $this->db->update($this->common->getUserTable(), $data);
    }

    /**
     * delete user
     */
    public function deleteUser($id) {
        $this->db->where('user_id', $id);

        $data['is_deleted'] = 1;
        $data['deleted_at'] = DATETIME;
        return $this->db->update($this->common->getUserTable(), $data);
    }

    /**
     * get All user data
     */
    public function getAllUser($order_by = '') {

        if ($order_by != '')
            $this->db->order_by('user_id', $order_by);

        //$this->db->where('is_deleted', 0);
        $data = $this->db->get($this->common->getUserTable())->result();

        foreach ($data as $key => $value) {
            $data[$key] = $value;
            $data[$key]->image_path = base_url('assets/images/default.png');
            if ($value->profilepic)
                $data[$key]->image_path = base_url() . PROFILE_PIC . $value->profilepic;
        }
        return $data;
    }

    public function getUserById($id) {
        $this->db->select('*');
        $this->db->where('user_id', $id);
        $data = $this->db->get($this->common->getUserTable())->row_array();

        if ($data) {
            if ($data['user_image'] != '') {
                $data['user_image'] = $data['user_image'];
            } else {
                $data['user_image'] = '';
            }
        }
        return $data;
    }

    /**
     * check if email existing
     */
    public function checkUserEmail($emailid) {
        $this->db->where('user_emailid', $emailid);
        $data = $this->db->get($this->common->getUserTable())->row_array();
        return $data;
    }

    /**
     * get user infomation using email
     */
    public function getUserByEmail($emailid) {
        $this->db->where('user_emailid', $emailid);
        $data = $this->db->get($this->common->getUserTable())->row_array();
        return $data;
    }
}
