<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

//include Rest Controller library
require APPPATH . '/libraries/REST_Controller.php';

class User extends REST_Controller {

    private $auth;

    public function __construct() {
        parent::__construct();

        $this->auth = new stdClass();

        $this->load->model(array('user_model' => 'user', 'truck_model' => 'truck'));

        $headers = $this->input->request_headers();
//echo '<pre>'; print_r($headers); die;
        if (!isset($headers['Id']) || !isset($headers['Token']) || !isset($headers['Type'])) {
            $this->response([
                'ResponseCode' => "2",
                'ResponseMsg' => 'Authentication data required.'
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->auth->id = $headers['Id'];
        $this->auth->token = $headers['Token'];
        $this->auth->type = $headers['Type'];


        $session = $this->common->getUserSession($this->auth->id, $this->auth->token, $this->auth->type);

//echo $this->db->last_query(); die;
        if (!isset($session->token) || $session->token !== $this->auth->token) {
            $this->response([
                'ResponseCode' => "2",
                'ResponseMsg' => 'Authentication failed.'
                    ], REST_Controller::HTTP_UNAUTHORIZED);
        }

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

    public function index_get($user_id = 0) {

        if ($user_id == 0)
            $user_id = $this->auth->id;

        $data = $this->common->getUserById($user_id);

        if ($data) {
            $this->response([
                'ResponseCode' => "1",
                'ResponseMsg' => 'data fetched successfully.',
                'data' => $data
                    ], REST_Controller::HTTP_OK);
        }

        $this->response([
            'ResponseCode' => "2",
            'ResponseMsg' => 'No data found.',
            'Result' => 'False'
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

    public function all_get() {

        $data = $this->user->getAllUser();

        if ($data) {
            $this->response([
                'ResponseCode' => "1",
                'ResponseMsg' => 'data fetched successfully.',
                'data' => $data
                    ], REST_Controller::HTTP_OK);
        }

        $this->response([
            'ResponseCode' => "2",
            'ResponseMsg' => 'No data found.',
            'Result' => 'False'
                ], REST_Controller::HTTP_BAD_REQUEST);
    }

    public function logout_get($secret_log_id = 0) {

        $session = $this->common->getSessionInfo($secret_log_id);
//echo '<pre>'; print_r($session); die;
        if ($session) {
            if ($session->user_id != $this->auth->id) {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Secret log does not belongs to you.',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            }

            $this->common->logoutUser($secret_log_id);
            $this->response([
                'ResponseCode' => "1",
                'ResponseMsg' => 'Logout Successful.',
                'Result' => 'True'
                    ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'ResponseCode' => "2",
                'ResponseMsg' => 'Secret log not found.',
                'Result' => 'False'
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function edit_post() {

        $user_firstname = $this->post('user_firstname');
        $user_lastname = $this->post('user_lastname');
        $user_username = $this->post('user_username');
        $user_id = $this->post('user_id');


        if (empty($user_id)) {

            $this->response([
                'ResponseCode' => "2",
                'ResponseMsg' => 'Please enter user id.',
                'Result' => 'False'
                    ], REST_Controller::HTTP_BAD_REQUEST);
        } else {


            $userData = array();

            if (!empty($user_firstname)) {
                $userData['user_firstname'] = $user_firstname;
            }

            if (!empty($user_lastname)) {
                $userData['user_lastname'] = $user_lastname;
            }
            if (!empty($user_username)) {
                $userData['user_username'] = $user_username;
            }

            $result = $this->user->updateUserData($userData, $user_id);

            if ($result) {
                $image = array();
                if (!empty($_FILES["user_image"]["name"])) {
                    $get_image = $this->user->getUserById($user_id);

                    $image_name = $get_image['user_image'];
                    if (!empty($image_name)) {

                        $folder_name = "user";
                        $type = "user_image";
                        $image_name = $_FILES["user_image"]["name"];
                        $tmp_name = $_FILES["user_image"]["tmp_name"];
                        $path = $this->common->image_upload($image_name, $tmp_name, $folder_name, $type);

                        $image['user_image'] = $path;
                        $result = $this->user->updateUserData($image, $user_id);

                        if ($result) {
//                            unlink('./upload/user/' . $image_name);
                        }
                    } else {
                        $folder_name = "user";
                        $type = "user_image";
                        $image_name = $_FILES["user_image"]["name"];
                        $tmp_name = $_FILES["user_image"]["tmp_name"];
                        $path = $this->common->image_upload($image_name, $tmp_name, $folder_name, $type);

                        $image['user_image'] = $path;
                        $result = $this->user->updateUserData($image, $user_id);
                    }
                }
                $result = $this->user->getUserById($user_id);

                $data['user_id'] = $result['user_id'];
                $data['user_firstname'] = $result['user_firstname'];
                $data['user_lastname'] = $result['user_lastname'];
                $data['user_username'] = $result['user_username'];
                $data['user_emailid'] = $result['user_emailid'];
                $data['user_dateofbirth'] = $result['user_dateofbirth'];
                $get_image = $result['user_image'];
                if (empty($get_image)) {
                    $data['user_image'] = "";
                } else {
                    $data['user_image'] = USER_IMAGE_URL . $result['user_image'];
                }

                $this->response([
                    'ResponseCode' => "1",
                    'ResponseMsg' => 'Profile successfully updated.',
                    'Result' => 'True',
                    'data' => $data
                        ], REST_Controller::HTTP_OK);
            } else {

                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Oops!something went wrong',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            }
        }
    }

    public function remove_delete($user_id = 0) {

        if ($user_id == 0) {
            $this->r_data['ResponseMsg'] = 'Please enter user id first';
            $this->r_data['Result'] = 'False';
            $this->response($this->r_data, REST_Controller::HTTP_BAD_REQUEST);
        }

        $del = $this->user->deleteUser($user_id);

        if ($del) {
            $this->r_data['ResponseCode'] = "1";
            $this->r_data['ResponseMsg'] = 'User deleted successfully';
            $code = REST_Controller::HTTP_OK;
        } else {
            $this->r_data['ResponseCode'] = "2";
            $this->r_data['ResponseMsg'] = 'Oops! something went wrong';
            $this->r_data['Result'] = 'False';
            $code = REST_Controller::HTTP_BAD_REQUEST;
        }

        $this->response($this->r_data, $code);
    }


    public function logout_post() {
        $secret_log_id = $this->post('secret_log_id');
        $device_token = $this->post('device_token_id');
        $flag = $this->post('flag');

        if (empty($device_token)) {

            $this->response([
                'ResponseCode' => "2",
                'ResponseMsg' => 'Please enter device token.',
                'Result' => 'False'
                    ], REST_Controller::HTTP_BAD_REQUEST);
        } elseif (empty($flag)) {

            $this->response([
                'ResponseCode' => "2",
                'ResponseMsg' => 'Please enter flag.',
                'Result' => 'False'
                    ], REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $session = $this->common->getSessionInfo($secret_log_id);

            if ($session) {
                if ($session->user_id != $this->auth->id) {
                    $this->response([
                        'ResponseCode' => "2",
                        'ResponseMsg' => 'Secret log does not belongs to you.',
                        'Result' => 'False'
                            ], REST_Controller::HTTP_BAD_REQUEST);
                }

                if ($flag == 'user') {
                    $device_token = $this->user->reset_device_token($device_token, $flag);
                    $this->common->logoutUser($secret_log_id);
                    $this->response([
                        'ResponseCode' => "1",
                        'ResponseMsg' => 'Device Token reset success.',
                        'Result' => 'True',
                        'ServerTimeZone' => date('T')
                            ], REST_Controller::HTTP_OK);
                } elseif ($flag == 'truck') {
                    $device_token = $this->user->reset_device_token($device_token, $flag);
                    $this->common->logoutUser($secret_log_id);
                    $this->response([
                        'ResponseCode' => "1",
                        'ResponseMsg' => 'Device Token reset success.',
                        'Result' => 'True',
                        'ServerTimeZone' => date('T')
                            ], REST_Controller::HTTP_OK);
                } else {
                    $this->response([
                        'ResponseCode' => "2",
                        'ResponseMsg' => 'Invalid flag',
                        'Result' => 'False'
                            ], REST_Controller::HTTP_BAD_REQUEST);
                }
            } else {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Secret log not found.',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            }
        }
    }

    public function forgotPassword_post() {

        $email = $this->post('email');

        $Usertable = $this->common->getUserTable();
        $Trucktable = $this->common->getTruckTable();

        $isUserMail = $this->common->checkExist('user_emailid', $email, $Usertable);
        $isTruckMail = $this->common->checkExist('truck_emailid', $email, $Trucktable);

        if ($isUserMail) {

            $result = $this->user->getUserByEmail($email);
            $password = $this->common->password_generate();
            $id = $result['user_id'];
            $user_firstname = $result['user_firstname'];


            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

            // More headers
            $headers .= 'From: <yogbsavaliya@gmail.com>' . "\r\n";
            $headers .= 'Cc: yogbsavaliya@gmail.com' . "\r\n";

            $message = "
            
            <div dir='ltr'>
            <div class='adM'><br></div>
            <div class='gmail_quote'><br><br><br>
            <table width='98%' cellspacing='0' cellpadding='0' border='0'>
            <tbody>
            
            <tr style='background:#e6e6e6'>
            <td valign='top' align='left' style='font-family:verdana;font-size:16px;line-height:0em;text-align:left;'>
            <table width='100%'>
            <tbody>
            <tr style='background:#f5f5f5;border-radius:5px'>
            <td style='font-family:verdana;font-size:13px;line-height:1.3em;text-align:left;padding:15px'>
            
            <p style='font-size: 15px;'>Hi $user_firstname,</p>
            
            <p style='font-size: 15px;'>We've received a request to reset your password. If you didn't make the request, just ignore this email. Otherwise you may login to your account with following password:</p>
            
            <p style='font-size: 15px;'>Your password is:  <b>$password</b></p>
            
            <p style='font-size: 15px;'>If you have any questions or trouble logging on please contact an app administrator.</p>
            
            </td>
            </tr>
            </tbody>
            </table>
            </td>
            </tr>
            </tbody>
            </table>
            <div class='yj6qo'></div><div class='adL'></div></div><div class='adL'><br></div></div>";

            $flgchk = mail($email, "Reset your food app Password", $message, $headers);
            if ($flgchk) {
                $password = $this->common->encryptIt($password);
                $flag = 'user';
                $result = $this->user->editPassword($id, $password, $flag);
                if ($result) {
                    $this->response([
                        'ResponseCode' => "1",
                        'ResponseMsg' => "Password has been sent to $email.",
                        'Result' => 'True'
                            ], REST_Controller::HTTP_OK);
                } else {
                    $this->response([
                        'ResponseCode' => "2",
                        'ResponseMsg' => 'Fail to reset password.',
                        'Result' => 'False'
                            ], REST_Controller::HTTP_BAD_REQUEST);
                }
            } else {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Email has not been send successfully.',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            }
        } else if ($isTruckMail == true) {

            $result = $this->truck->getTruckByEmail($email);

            $id = $result['truck_id'];
            $truck_name = $result['truck_name'];
            $password = $this->common->password_generate();

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

            // More headers
            $headers .= 'From: <yogbsavaliya.com>' . "\r\n";
            $headers .= 'Cc: yogbsavaliya@gmail.com' . "\r\n";

            $message = "
            
            <div dir='ltr'>
            <div class='adM'><br></div>
            <div class='gmail_quote'><br><br><br>
            <table width='98%' cellspacing='0' cellpadding='0' border='0'>
            <tbody>
            
            <tr style='background:#e6e6e6'>
            <td valign='top' align='left' style='font-family:verdana;font-size:16px;line-height:0em;text-align:left;'>
            <table width='100%'>
            <tbody>
            <tr style='background:#f5f5f5;border-radius:5px'>
            <td style='font-family:verdana;font-size:13px;line-height:1.3em;text-align:left;padding:15px'>
            
            <p style='font-size: 15px;'>Hi $truck_name,</p>
            
            <p style='font-size: 15px;'>We've received a request to reset your password. If you didn't make the request, just ignore this email. Otherwise you may login to your account with following password:</p>
            
            <p style='font-size: 15px;'>Your password is:  <b>$password</b></p>
            
            <p style='font-size: 15px;'>If you have any questions or trouble logging on please contact an app administrator.</p>
            
            </td>
            </tr>
            </tbody>
            </table>
            </td>
            </tr>
            </tbody>
            </table>
            <div class='yj6qo'></div><div class='adL'></div></div><div class='adL'><br></div></div>";

            $flgchk = mail($email, "Reset your food app Password", $message, $headers);
            if ($flgchk) {
                $password = $this->common->encryptIt($password);
                $flag = 'truck';
                $result = $this->user->editPassword($id, $password, $flag);
                if ($result) {
                    $this->response([
                        'ResponseCode' => "1",
                        'ResponseMsg' => "Password has been sent to $email.",
                        'Result' => 'True'
                            ], REST_Controller::HTTP_OK);
                } else {
                    $this->response([
                        'ResponseCode' => "2",
                        'ResponseMsg' => 'Fail to reset password.',
                        'Result' => 'False'
                            ], REST_Controller::HTTP_BAD_REQUEST);
                }
            } else {
                $this->response([
                    'ResponseCode' => "2",
                    'ResponseMsg' => 'Email has not been send successfully.',
                    'Result' => 'False'
                        ], REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
            $this->response([
                'ResponseCode' => "2",
                'ResponseMsg' => 'Email does Not Exist.',
                'Result' => 'False'
                    ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }


}
