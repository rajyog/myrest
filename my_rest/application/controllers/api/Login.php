<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

//include Rest Controller library
require APPPATH . '/libraries/REST_Controller.php';

class Login extends REST_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model(array('user_model' => 'user', 'truck_model' => 'truck'));
        $this->r_data['ResponseCode'] = "2";
        $this->r_data['ResponseMsg'] = '';
    }

    public function index_post() {

        $emailid = $this->post('emailid');
        $password = $this->post('password');
        $devicetoken = $this->post('devicetoken');
        $devicetype = $this->post('devicetype');

        $UserMailExist = $this->user->checkUserEmail($emailid);
        $TruckMailExist = $this->truck->checkTruckEmail($emailid);
        if ($UserMailExist) {

            $result = $this->user->getUserByEmail($emailid);
            $getPassword = $result['user_password'];
            $userIsDelete = $result['user_is_delete'];


            if ($userIsDelete == '1') {

                $enPassword = $this->common->encryptIt($password);
                if ($enPassword != $getPassword) {
                    $this->response([
                        'ResponseCode' => "2",
                        'ResponseMsg' => 'password invalid',
                        'Result' => 'False'
                            ], REST_Controller::HTTP_BAD_REQUEST);
                }
                $user_id = $result['user_id'];

                $flag = 'user';
                $editToken = $this->user->editDeviceToken($result['user_id'], $devicetoken, $devicetype, $flag);
                if ($editToken == TRUE) {
                    $res = $this->user->getUserById($user_id);

                    $data['user_id'] = $res['user_id'];
                    $data['user_firstname'] = $res['user_firstname'];
                    $data['user_lastname'] = $res['user_lastname'];
                    $data['user_password'] = $this->common->decryptIt($res['user_password']);
                    $data['user_emailid'] = $res['user_emailid'];
                    $data['user_dateofbirth'] = $res['user_dateofbirth'];
                    $data['user_username'] = $res['user_username'];
                    $data['login_type'] = $res['login_type'];
                    $data['user_devicetoken'] = $res['user_devicetoken'];
                    $data['user_devicetype'] = $res['user_devicetype'];
                    $data['customer_id'] = $res['customer_id'];
                    $data['card_available'] = $res['is_card_available'];
                    $data['user_vault_amount'] = $result['user_vault_amount'];


                    $get_image = $res['user_image'];

                    if (empty($get_image)) {
                        $data['user_image'] = "";
                    } else {
                        $image_path = $res['user_image'];
                        $data['user_image'] = $image_path;
                    }
                }
                // Add new user session
                $this->r_data['token'] = $this->common->getSecureKey();
                $user_session = array(
                    'user_id' => $user_id,
                    'token' => $this->r_data['token'],
                    'start_date' => DATETIME,
                    'user_type'=>1
                );
                $this->r_data['ResponseCode'] = 1;
                $this->r_data['ResponseMsg'] = 'Login Successful.';
                $this->r_data['secret_log_id'] = $this->common->insertUserSession($user_session);
                $this->r_data['data'] = $data;

                $this->response($this->r_data, REST_Controller::HTTP_OK);
            } else {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'You are block by Scavenger Behavior admin.',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            }
        } elseif ($TruckMailExist) {
            $result = $this->truck->getTruckByEmail($emailid);
            $get_password = $result['truck_password'];

            $enPassword = $this->common->encryptIt($password);
            if ($enPassword != $get_password) {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'password invalid'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            }

            $truck_id = $result['truck_id'];

            $flag = 'truck';
            $edit_token = $this->user->editDeviceToken($result['truck_id'], $devicetoken, $devicetype, $flag);
            if ($edit_token == TRUE) {
                $res = $this->truck->getTruckById($truck_id);

                $data['truck_id'] = $res['truck_id'];
                $data['truck_username'] = $res['truck_username'];
                $data['truck_name'] = $res['truck_name'];
                $data['truck_emailid'] = $res['truck_emailid'];
                $data['truck_password'] = $this->common->decryptIt($res['truck_password']);
                $data['truck_location'] = $res['truck_location'];
                $data['truck_latitude'] = $res['truck_latitude'];
                $data['truck_longitude'] = $res['truck_longitude'];
                $data['truck_devicetoken'] = $res['truck_devicetoken'];
                $data['truck_devicetype'] = $res['truck_devicetype'];
            }

            // Add new user session
            $this->r_data['token'] = $this->common->getSecureKey();
            $truck_session = array(
                'truck_id' => $res['truck_id'],
                'token' => $this->r_data['token'],
                'start_date' => DATETIME,
                'user_type'=>2
            );
            $this->r_data['ResponseCode'] = "1";
            $this->r_data['ResponseMsg'] = 'Login Successful.';
            $this->r_data['secret_log_id'] = $this->common->insertUserSession($truck_session);
            $this->r_data['data'] = $data;
            $this->response($this->r_data, REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'ResponseCode' => "2",
                'ResponseMsg' => 'Invalid credentials',
                'Result' => 'False'
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }
}
