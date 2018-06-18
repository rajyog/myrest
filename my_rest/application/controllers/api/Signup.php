<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

//include Rest Controller library
require APPPATH . '/libraries/REST_Controller.php';

class Signup extends REST_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model(array('user_model' => 'user', 'truck_model' => 'truck'));
        $this->r_data['ResponseCode'] = "2";
        $this->r_data['ResponseMsg'] = '';
    }

    function paramValidation($paramarray, $data) {
        $NovalueParam = array();
        foreach ($paramarray as $val) {
            if ($data[$val] == '') {
                $NovalueParam[] = $val;
            }
        }
        if (is_array($NovalueParam) && count($NovalueParam) > 0) {
            $this->r_data['ResponseMsg'] = 'Sorry, that is not valid input. You missed ' . implode(', ', $NovalueParam) . ' parameters';
        } else {
            $this->r_data['ResponseCode'] = "1";
        }
        return $this->r_data;
    }

    /**
     *  user_registration.php
     */
    public function user_post() {

        $user_firstname = $this->post('user_firstname') ? $this->post('user_firstname') : '';
        $user_lastname = $this->post('user_lastname') ? $this->post('user_lastname') : '';
        $user_username = $this->post('user_username') ? $this->post('user_username') : '';
        $user_dateofbirth = $this->post('user_dateofbirth') ? $this->post('user_dateofbirth') : '';
        $emailid = $this->post('user_emailid') ? $this->post('user_emailid') : '';
        $user_password = $this->post('user_password') ? $this->post('user_password') : '';
        $user_social_id = $this->post('user_social_id') ? $this->post('user_social_id') : '';
        $entity_id = $this->post('entity_id') ? $this->post('entity_id') : '';
        $user_devicetoken = $this->post('user_devicetoken') ? $this->post('user_devicetoken') : '';
        $devicetype = $this->post('devicetype') ? $this->post('devicetype') : '';
        $user_created = DATETIME;

        $Usertable = $this->common->getUserTable();
        $Trucktable = $this->common->getTruckTable();

        if (empty($user_social_id)) {
            if (empty($user_firstname)) {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Please enter first name',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            } elseif (empty($user_lastname)) {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Please enter last name',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            } elseif (empty($user_dateofbirth)) {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Email ID already exist',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
                return ResponseClass::ResponseMessage("2", "Please enter birth date", "False");
            } elseif (empty($user_username)) {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Please enter username',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            } elseif (empty($emailid)) {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Please enter email',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            } elseif (empty($user_password)) {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Please enter password',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            } elseif (empty($user_devicetoken)) {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Please enter device token',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            } elseif (empty($devicetype)) {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Please enter device type',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            } else {

                $isUserMail = $this->common->checkExist('user_emailid', $userData['user_emailid'], $Usertable);
                $isTruckMail = $this->common->checkExist('truck_emailid', $userData['user_emailid'], $Trucktable);
                if (!empty($isUserMail) || !empty($isTruckMail)) {
                    $this->response([
                        'ResponseCode' => "2",
                        'ResponseMsg' => 'Email ID already exist',
                        'Result' => 'False'
                            ], REST_Controller::HTTP_BAD_REQUEST);
                }
                $enPassword = $this->common->encryptIt($user_password);
                $login_type = '1';

                $result = $this->user->register_user($user_firstname, $user_lastname, $user_username, $user_dateofbirth, $emailid, $enPassword, $entity_id, $user_devicetoken, $devicetype, $login_type, $user_created);
                $user_id = $this->db->insert_id();
                if ($result == true) {

                    if (!empty($user_id) && !empty($emailid)) {
                        require_once('stripe/init.php');
                        \Stripe\Stripe::setApiKey($this->common->stripeKey());
                        $customer = \Stripe\Customer::create(array(
                                    "email" => $emailid,
                                    "metadata" => array("user_id" => $user_id)
                        ));
                        $customer_json = $customer->__toJSON();
                        $customer_json = json_decode($customer_json, TRUE);
                        $customer_id = $customer_json['id'];
                        $this->user->edit_user($user_id, $customer_id);
                    }


                    $result = $this->user->getUserById($user_id);
                    $data['user_id'] = $result['user_id'];
                    $data['user_firstname'] = $result['user_firstname'];
                    $data['user_lastname'] = $result['user_lastname'];
                    $data['user_username'] = $result['user_username'];
                    $data['user_password'] = $result['user_password'];
                    $data['user_dateofbirth'] = $result['user_dateofbirth'];
                    $data['user_emailid'] = $result['user_emailid'];
                    $enPassword = $dataObj->decryptIt($result['user_password']);
                    $data['user_password'] = $enPassword;
                    $data['login_type'] = $result['login_type'];
                    $data['user_devicetoken'] = $result['user_devicetoken'];
                    $data['user_devicetype'] = $result['user_devicetype'];
                    $data['customer_id'] = $result['customer_id'];
                    $data['card_available'] = $result['is_card_available'];
                    $data['user_vault_amount'] = $result['user_vault_amount'];
                    $data['user_image'] = USER_IMAGE_URL . $result['user_image'];
                    $data['is_new_user'] = 'no';

// Add new user session
                    $this->r_data['token'] = $this->common->getSecureKey();
                    $user_session = array(
                        'user_id' => $result['user_id'],
                        'token' => $this->r_data['token'],
                        'start_date' => DATETIME,
                        'user_type' => 1
                    );

                    $this->r_data['ResponseCode'] = "1";
                    $this->r_data['ResponseMsg'] = 'Registration is successfully.';
                    $this->r_data['Result'] = 'True';
                    $this->r_data['Data'] = $data;
                    $this->r_data['secret_log_id'] = $this->common->insertUserSession($user_session);
                    $this->response($this->r_data, REST_Controller::HTTP_OK);
                } else {

                    $this->response([
                        'ResponseCode' => "2",
                        'ResponseMsg' => 'Registration has been not completed successfully',
                        'Result' => 'False'
                            ], REST_Controller::HTTP_BAD_REQUEST);
                }
            }
        } else {
            if (empty($user_social_id)) {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Please enter facebook id',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            } else {
                $isSocialIdFound = $this->common->checkExist('user_social_id', $user_social_id, $Usertable);
                if ($isSocialIdFound == true) {

                    $result = $this->common->get_user_detail_on_social_id($user_social_id);

                    $data['user_id'] = $result['user_id'];
                    $data['user_firstname'] = $result['user_firstname'];
                    $data['user_lastname'] = $result['user_lastname'];
                    $data['user_password'] = $result['user_password'];
                    $data['user_username'] = $result['user_username'];
                    $data['user_emailid'] = $result['user_emailid'];
                    $data['user_devicetoken'] = $result['user_devicetoken'];
                    $data['user_devicetype'] = $result['user_devicetype'];
                    $data['customer_id'] = $result['customer_id'];
                    $data['card_available'] = $result['is_card_available'];
                    $data['user_vault_amount'] = $result['user_vault_amount'];

                    $get_image = $result['user_image'];

                    if (empty($get_image)) {
                        $data['user_image'] = "";
                    } else {
                        $image_path = USER_IMAGE_URL . $result['user_image'];
                        $data['user_image'] = $image_path;
                    }

                    $data['login_type'] = $result['login_type'];
                    $data['is_new_user'] = 'no';

                    $this->r_data['token'] = $this->common->getSecureKey();
                    $user_session = array(
                        'user_id' => $result['user_id'],
                        'token' => $this->r_data['token'],
                        'start_date' => DATETIME,
                        'user_type' => 1
                    );
                    $this->r_data['secret_log_id'] = $this->common->insertUserSession($user_session);

                    $this->r_data['ResponseCode'] = "1";
                    $this->r_data['ResponseMsg'] = 'Login successfully';
                    $this->r_data['Result'] = 'True';
                    $this->r_data['Data'] = $data;
                    $this->response($this->r_data, REST_Controller::HTTP_OK);
                } else {
                    $login_type = '2';
                    if (empty($emailid)) {
                        $result = $this->user->register_fb_user($user_firstname, $user_lastname, $user_username, $user_social_id, $entity_id, $user_devicetoken, $devicetype, $login_type, $user_created);
                        $user_id = $this->db->insert_id();
                    } else {
                        $result = $this->user->register_fb_user_with_email($user_firstname, $user_lastname, $user_username, $emailid, $user_social_id, $entity_id, $user_devicetoken, $devicetype, $login_type, $user_created);
                        $user_id = $this->db->insert_id();
                    }

                    if ($result == true) {

                        if (!empty($user_id) && !empty($emailid)) {
                            require_once('stripe/init.php');
                            \Stripe\Stripe::setApiKey($this->common->stripeKey());
                            $customer = \Stripe\Customer::create(array(
                                        "email" => $emailid,
                                        "metadata" => array("user_id" => $user_id)
                            ));
                            $customer_json = $customer->__toJSON();
                            $customer_json = json_decode($customer_json, TRUE);
                            $customer_id = $customer_json['id'];
                            $card_available = '1';
                            $this->user->edit_user($user_id, $customer_id);
                        }


                        if (!empty($_FILES["user_image"]["name"])) {
                            $imagePath = CommanClass::user_profile_image_upload();
                            $result = $dataObj->updateProfileImage($user_id, $imagePath);
                        }

                        $result = $this->user->getUserById($user_id);

                        $data['user_id'] = $result['user_id'];
                        $data['user_firstname'] = $result['user_firstname'];
                        $data['user_lastname'] = $result['user_lastname'];
                        $data['user_username'] = $result['user_username'];
                        $data['user_password'] = $result['user_password'];
                        $data['user_emailid'] = $result['user_emailid'];
                        $data['user_devicetoken'] = $result['user_devicetoken'];
                        $data['user_devicetype'] = $result['user_devicetype'];
                        $data['customer_id'] = $result['customer_id'];
                        $data['card_available'] = $result['is_card_available'];
                        $data['user_vault_amount'] = $result['user_vault_amount'];


                        $get_image = $result['user_image'];

                        if (empty($get_image)) {
                            $data['user_image'] = "";
                        } else {
                            $image_path = USER_IMAGE_URL . $result['user_image'];
                            $data['user_image'] = $image_path;
                        }

                        $data['login_type'] = $result['login_type'];
                        $data['is_new_user'] = 'yes';

                        $this->r_data['token'] = $this->common->getSecureKey();
                        $user_session = array(
                            'user_id' => $result['user_id'],
                            'token' => $this->r_data['token'],
                            'start_date' => DATETIME,
                            'user_type' => 1
                        );
                        $this->r_data['secret_log_id'] = $this->common->insertUserSession($user_session);


                        $this->r_data['ResponseCode'] = "1";
                        $this->r_data['ResponseMsg'] = 'Registration is successfully.';
                        $this->r_data['Result'] = 'True';
                        $this->r_data['data'] = $data;
                        $this->response($this->r_data, REST_Controller::HTTP_OK);
                    } else {

                        $this->response([
                            'ResponseCode' => "2",
                            'ResponseMsg' => 'Registration has been not completed successfully',
                            'Result' => 'False'
                                ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                }
            }
        }
    }

    /**
     *  truck_registration.php
     */
    public function truck_post() {
        $truckData = array();
        $truckData['truck_name'] = $this->post('truck_name');
        $truckData['truck_emailid'] = $this->post('truck_emailid');
        $truckData['truck_password'] = $this->post('truck_password');
        $truckData['truck_location'] = $this->post('truck_location');
        $truckData['truck_latitude'] = $this->post('truck_latitude');
        $truckData['truck_longitude'] = $this->post('truck_longitude');
        $truckData['entity_id'] = $this->post('entity_id');
        $truckData['truck_phone_country_code'] = $this->post('country_code');
        $truckData['truck_devicetoken'] = $this->post('truck_devicetoken');
        $truckData['truck_devicetype'] = $this->post('devicetype');
        $truckData['entity_id'] = $this->post('entity_id');

        $key = array('truck_name', 'truck_emailid', 'truck_password', 'truck_location', 'truck_latitude', 'truck_longitude', 'truck_devicetoken', 'truck_devicetype');
        $validation = $this->paramValidation($key, $truckData);
        if ($validation['ResponseCode'] == 0) {
            $this->response($this->r_data, REST_Controller::HTTP_BAD_REQUEST);
        }

        $Usertable = $this->common->getUserTable();
        $Trucktable = $this->common->getTruckTable();

        $isUserMail = $this->common->checkExist('user_emailid', $truckData['truck_emailid'], $Usertable);
        $isTruckMail = $this->common->checkExist('truck_emailid', $truckData['truck_emailid'], $Trucktable);
        if (!empty($isUserMail) || !empty($isTruckMail)) {
            $this->response([
                'ResponseCode' => "2",
                'ResponseMsg' => 'Email ID already exist',
                'Result' => 'False'
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $truckData['truck_password'] = $this->common->encryptIt($this->post('truck_password'));

        $signup = $this->truck->addTruck($truckData);
        if ($signup) {
            $result = $this->truck->getTruckById($signup);
            $data['truck_id'] = $result->truck_id;
            $data['truck_name'] = $result->truck_name;
            $data['truck_emailid'] = $result->truck_emailid;
            $data['truck_password'] = $this->common->decryptIt($result->truck_password);
            $data['truck_devicetoken'] = $result->truck_devicetoken;
            $data['truck_devicetype'] = $result->truck_devicetype;
            $data['truck_location'] = $result->truck_location;
            $data['truck_latitude'] = $result->truck_latitude;
            $data['truck_longitude'] = $result->truck_longitude;

            $this->r_data['ResponseCode'] = "1";
            $this->r_data['ResponseMsg'] = 'Registration is successfully.';
            $this->r_data['Result'] = 'True';
            $this->r_data['data'] = $data;
            $this->response($this->r_data, REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'ResponseCode' => "2",
                'ResponseMsg' => 'Oops! something went wrong',
                'Result' => 'False'
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }
}
