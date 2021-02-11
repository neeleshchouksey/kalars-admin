<?php

namespace App\Controllers;

use App\Models\AdminModel;
use App\Models\CommonModel;

class Admin extends BaseController
{

    var $user_data = '';
    protected $commonmodel;
    protected $db;
    protected $session;
    protected $validation;
    protected $adminmodel;

    public function __construct()
    {

        $this->db = \Config\Database::connect();
        $this->commonmodel = new CommonModel($this->db);
        $this->adminmodel = new AdminModel($this->db);
        $this->session = session();
//        $this->user_data = $this->session->get('user_data');
        helper(array('url', 'form', 'image_helper', 'sms_helper'));
        $validation = \Config\Services::validation();


    }

    // default action taken if no action is defined by the request.
    public function index()
    {
        $klrdmin_id = $this->session->get('klrdmin_id');
        $email = $this->session->get('admin_email');
        echo view('includes/header', ['klrdmin_id' => $klrdmin_id, 'admin_email' => $email]);
        echo view('login');
        echo view('includes/footer', ['klrdmin_id' => $klrdmin_id, 'admin_email' => $email]);
    }

    // Created by Neelesh Chouksey on 2011.03.30
    // this public function is used to login and varify the Affiliate user.
    public function signin()
    {
        $email = $this->request->getPost('admin_email');
        $admin_password = md5($this->request->getPost('admin_password'));
        $admin = $this->commonmodel->getRecords('klrdmin', '', array("admin_email" => $email, "admin_password" => $admin_password), '', true);
//            echo '<pre>';print_r($admin);exit;
        if ($admin) {
            $this->session->set('admin_email', $email);
            $this->session->set('klrdmin_id', $admin['klrdmin_id']);
            return redirect()->to(base_url() . '/admin/welcome');
        } else {
            $klrdmin_id = $this->session->get('klrdmin_id');
            $email = $this->session->get('admin_email');
            echo view('includes/header', ['klrdmin_id' => $klrdmin_id, 'admin_email' => $email]);
            echo view('login');
            echo view('includes/footer', ['klrdmin_id' => $klrdmin_id, 'admin_email' => $email]);
        }

    }

    // Created by Neelesh Chouksey on 2011.03.30
    // this public function displays welcome page for Affiliate user.
    public function welcome()
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            echo view('includes/header');
            echo view('welcome');
            echo view('includes/footer');
        } else {
            $this->session->destroy();
            return redirect()->to(base_url() . '/admin');
        }
    }

    // Created by Neelesh Chouksey on 2011.03.30
    // this public function is used to logout affiliate user.
    public function logout()
    {
        $this->session->sess_destroy();
        return redirect('admin');

    }

    // Created by Neelesh Chouksey on 2010.12.23
    // callback public function for validating login form.
    public function checklogin($str)
    {
        $admin_password = md5($this->request->getPost('admin_password'));
        $admin = $this->commonmodel->getRecords('klrdmin', '', array("admin_email" => $str, "admin_password" => $admin_password), '', true);
//		echo '<pre>';print_r($admin);exit;
        if ($admin) {
            $this->session->set('admin_email', $str);
            $this->session->set('klrdmin_id', $admin['klrdmin_id']);
//			echo "ASDFSADF";die;
            return true;
        } else {
            $this->validation->set('checklogin', 'Invalid email or password.');
            return FALSE;
        }
    }

    // Created by Neelesh Chouksey on 2010.12.23
    // public function for deleting users.
    public function deleteRecord($table, $id, $redirect)
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            // deleting records from database
            $this->commonmodel->deleteRecords($table, $table . "_id=$id");
            $this->session->set('msg', 'deleted');
            return redirect('admin/' . $redirect);
        } else {
            $this->session->sess_destroy();
            return redirect('admin');
        }
    }

    // public function for deleting users.
    public function deleteUser($table, $id, $redirect)
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $user = $this->commonmodel->getRecords($table, 'name', array($table . "_id" => $id), '', true);
            $userDir = IMG_PATH . $id . $user['name'];
            if (is_dir($userDir)) {
                array_map('unlink', glob($userDir . "/*"));
                rmdir($userDir);
            }

            // deleting records from database
            $this->commonmodel->deleteRecords('favorites', "user_id=$id");
            $this->commonmodel->deleteRecords('favorites', "favorite_user_id=$id");
            $this->commonmodel->deleteRecords('photo', "user_id=$id");
            $this->commonmodel->deleteRecords('report_abuse', "abused_user_id=$id");
            $this->commonmodel->deleteRecords($table, $table . "_id=$id");
            $this->session->set('msg', 'deleted');
            redirect('admin/' . $redirect . '/user_id');
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    // public function for deleting users.
    public function set_is_deleted($table, $id, $redirect)
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $this->commonmodel->addEditRecords('user', array('is_deleted' => 1), $id);
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    // Created by Neelesh Chouksey on 2010.12.23
    // public function for deleting users.
    public function deleteAbusedUser($table, $id, $redirect)
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            // deleting records from database
            $this->commonmodel->deleteRecords($table, "abused_user_id=$id");
            $this->session->set('msg', 'deleted');
            redirect('admin/' . $redirect);
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    // Created by Neelesh Chouksey on 2010.12.23
    // public function for changing status tooltip.
    public function callback_StatusToolTip($table, $id)
    {
        $field = $this->commonmodel->getTableFields($table);
        $field = $field[0]['Field'];

        $arr = array($field => $id);
        $status = $this->commonmodel->getRecords($table, 'is_active', $arr, '', true);
        if ($status['is_active']) {
            echo 'Currently enabled. Click to disable';
        } else {
            echo 'Currently disabled. Click to enable';
        }
        exit;
    }

    // Created by Neelesh Chouksey on 2010.12.23
    // public function for changing status.
    public function changeStatus()
    {
        $table = $this->request->getPost('table');
        $status = $this->request->getPost('status');
        $id = $this->request->getPost('id');
        $this->adminmodel->changeStatus($table, $status, $id);
        echo $status;
        exit;
    }

    // Created by Neelesh Chouksey on 2010.12.23
    // Modified by Neeelesh Chouksey on 2011.01.31
    // public function for changing admin password.
    public function changePassword()
    {
        $data['msg'] = $this->session->get('msg');
        $this->session->remove('msg');

        echo view('includes/header');
        echo view('change-password', $data);
        echo view('includes/footer');
    }

    // Created by Neelesh Chouksey on 2011.01.31
    // This public function saves new password of admin.
    public function savePassword()
    {
        echo library('form_validation');
        $this->form_validation->set_rules('admin_email', 'email', 'trim|required|valid_email|callback_checklogin');
        $this->form_validation->set_rules('admin_password', 'password', 'trim|required|md5');
        $this->form_validation->set_rules('new_password', 'new password', 'trim|required|matches[confirm_password]|md5');
        $this->form_validation->set_rules('confirm_password', 'confirm password', 'trim|required');
        $this->form_validation->set_message('matches', 'The new password field does not match the confirm password field.');

        if ($this->form_validation->run() == FALSE) {
            echo view('includes/header');
            echo view('change-password', $data);
            echo view('includes/footer');

            return false;
        } else {
            $this->commonmodel->addEditRecords('klrdmin', array('admin_password' => $this->request->getPost('new_password')), 1);
            $this->session->set('msg', 'Your admin password has been changed successfully.');
            redirect('admin/changePassword');
        }
    }

    // Created by Neelesh Chouksey on 2010.12.23
    // This public function is used to list all the affiliates.
    public function users($order_by = "name",$offset = 0)
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $this->session->set('redirect_url', uri_string());

            $where = array('is_active' => '1', 'is_deleted' => '0');
            $data['total_user'] = $this->commonmodel->getRecords('user', 'user_id', $where);

            $this->session->set('order_by', $order_by);
            $where_condition = array('is_active' => '1', 'is_deleted' => '0');
            $this->session->set('where_condition', $where_condition);

            $limit = 100;
            $data['count'] = $offset;
//
//            $data['users'] = $this->commonmodel->getRecords('user', '', $where, $order_by . ' desc', '');

            $data['msg'] = $this->session->get('msg');
            $data['menu'] = 'users';
            $this->session->remove('msg');
            echo view('includes/header', $data);
            echo view('users',$data);
            echo view('includes/footer');
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    // Created by Neelesh Chouksey on 2010.12.23
    // This public function is used to list all the affiliates.
    public function users_list($offset = 0)
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $this->session->set('redirect_url', uri_string());

            $limit = 100;
            $data['count'] = $offset;

            $order_by = $this->session->get('order_by');
            $where = $this->session->get('where_condition');

            $data['users'] = $this->commonmodel->getRecords('user', '', $where, $order_by . ' desc', '', $limit, $offset);
            $data['total'] = count($this->commonmodel->getRecords('user', '', $where, $order_by . ' desc', '', '', ''));
            $data['msg'] = $this->session->get('msg');
            $data['menu'] = 'users';
            $this->session->remove('msg');

            echo view('user_list', $data);

        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    public function search_bar()
    {

        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $this->session->set('redirect_url', uri_string());

            $search_item = $this->request->getPost('search_item');
            $where = $this->session->get('where_condition');
            $data['users'] = $this->adminmodel->get_searchbar_info($search_item, $where);
            $data['total'] = count($this->adminmodel->get_searchbar_info($search_item, $where));

            //echo '<pre>';print_r($data);exit;
            $data['count'] = 0;

            $data['msg'] = $this->session->get('msg');
            $data['menu'] = 'users';
            $this->session->remove('msg');
            echo view('user_list', $data);
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    // Created by Neelesh Chouksey on 2010.12.23
    // This public function is used to list all the affiliates.
    public function brides($order_by = "name",$offset=0)
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $this->session->set('redirect_url', uri_string());
            $where = array('gender' => 'Female', 'is_active' => '1', 'is_deleted' => '0');
            $data['total_user'] = $this->commonmodel->getRecords('user', 'user_id', $where);

            $this->session->set('order_by', $order_by);
            $where_condition = array('gender' => 'Female', 'is_active' => '1', 'is_deleted' => '0');
            $this->session->set('where_condition', $where_condition);
//            $limit = 100;
//            $data['count'] = $offset;
//            $data['users'] = $this->commonmodel->getRecords('user', '', $where, $order_by . ' desc', '');

            $data['msg'] = $this->session->get('msg');
            $data['menu'] = 'brides';
            $data['order_by'] = $order_by;
            $this->session->remove('msg');
            echo view('includes/header', $data);
            echo view('users');
            echo view('includes/footer');
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }



    // Created by Neelesh Chouksey on 2010.12.23
    // This public function is used to list all the affiliates.
    public function grooms($order_by = "name",$offset=0)
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $this->session->set('redirect_url', uri_string());

            $where = array('gender' => 'Male', 'is_active' => '1', 'is_deleted' => '0');
            $data['total_user'] = $this->commonmodel->getRecords('user', 'user_id', $where);

            $this->session->set('order_by', $order_by);
            $where_condition = array('gender' => 'Male', 'is_active' => '1', 'is_deleted' => '0');
            $this->session->set('where_condition', $where_condition);

//            $limit = 100;
//            $data['count'] = $offset;
//            $data['users'] = $this->commonmodel->getRecords('user', '', $where, $order_by . ' desc', '');

            $data['msg'] = $this->session->get('msg');
            $data['menu'] = 'grooms';
            $this->session->remove('msg');

            echo view('includes/header', $data);
            echo view('users');
            echo view('includes/footer');
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    public function inactive_users($order_by = "name")
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $this->session->set('redirect_url', uri_string());

            $where = array('is_active' => '0');
            $data['total_user'] = $this->commonmodel->getRecords('user', 'user_id', $where);

            $this->session->set('order_by', $order_by);
            $where_condition = array('is_active' => '0');
            $this->session->set('where_condition', $where_condition);

            $data['msg'] = $this->session->get('msg');
            $data['menu'] = 'inactive';
            $this->session->remove('msg');

            echo view('includes/header', $data);
            echo view('users');
            echo view('includes/footer');
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    public function deleted_users($order_by = "name")
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $this->session->set('redirect_url', uri_string());

            $where = array('is_deleted' => '1');
            $data['total_user'] = $this->commonmodel->getRecords('user', 'user_id', $where);

            $this->session->set('order_by', $order_by);
            $where_condition = array('is_deleted' => '1');
            $this->session->set('where_condition', $where_condition);

            $data['msg'] = $this->session->get('msg');
            $data['menu'] = 'deleted';
            $this->session->remove('msg');

            echo view('includes/header', $data);
            echo view('users');
            echo view('includes/footer');
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }


    // Created by Neelesh Chouksey on 2010.12.23
    // This public function is used to list all the affiliates.
    public function abusedusers()
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $this->session->set('redirect_url', uri_string());

            //$data['users'] = $this->commonmodel->getRecords('user','',array());
            $rs = $this->db->table("report_abuse as a")
                ->select('u.user_id, u.name, u.last_name, u.profile_pic, u.is_active')
                ->join('user u', 'a.abused_user_id = u.user_id');

            $data['users'] = $rs->get()->getResultArray();
            //echo '<pre>';print_r($data['users']);exit;
            $data['msg'] = $this->session->get('msg');
            $this->session->remove('msg');
            echo view('includes/header', $data);
            echo view('abusedusers');
            echo view('includes/footer');
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    // Created by Neelesh Chouksey on 2010.12.23
    // This public function is used to list all the affiliates.
    public function add_user()
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $data['menu'] = 'users';
            echo view('includes/header', $data);
            echo view('adduser');
            echo view('includes/footer');
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    // Created by Neelesh Chouksey on 2010.12.23
    // This public function is used to list all the affiliates.
    public function save_user()
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $post_data = $this->request->getPost();
            $user_data = $this->commonmodel->getRecords('user', 'user_id', array("phone_no" => $post_data['phone_no']), '', true);
            //echo '<pre>';print_r($post_data);exit;
            if ($user_data) {
                echo 'User already exist';
                exit;
            }
            $this->commonmodel->addEditRecords('user', $post_data);
            $user_id = $this->db->insertID();

            $data['menu'] = 'users';
            echo view('includes/header', $data);
            echo view('adduser');
            echo view('includes/footer');

//            echo "<script type=\"text/javascript\">window.open('../../home/app_launch/" . $user_id . "/shivadmin', '_blank')</script>";
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    // Created by Neelesh Chouksey on 2016.10.24
    public function notification()
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $data['menu'] = 'notification';
            echo view('includes/header', $data);
            echo view('notification');
            echo view('includes/footer');
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    // Created by Neelesh Chouksey on 2016.10.24
    public function send_notification($message = '')
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $post_data = $this->request->getPost();

            if ($post_data['notype'] == 'profile_incomplete') {
                $this->db->select('g.gcmRegistrationId')
                    ->from('user u')
                    ->join('gcm g', 'g.user_id = u.user_id');
                $rs = $this->db->get();
                $RegistrationIds = $rs->result_array();
            } else {
                $RegistrationIds = $this->commonmodel->getRecords('gcm', 'gcmRegistrationId');
            }

            //$RegistrationIds = $this->commonmodel->getRecords('gcm','gcmRegistrationId',array("user_id"=>'1'));

            foreach ($RegistrationIds as $RegistrationId) {
                $gcmRegistrationIds = $RegistrationId['gcmRegistrationId'];
            }
            //$gcmRegistrationIds = array('c-TgQjqmbXo:APA91bHhPTiHeUGzxjBRy5dtcF7kQ4IpJqbalX9n-1KO6zzF7HpaFAwN_cOksnx5dbeyBUqqoa04L2leLodo5-vXtHJ8mbW3R_EPEijLaDN__CgZE81XYYCRPoBiUHEJSk2HlJKo34jb');

            //echo $post_data['notext'];
            $message = $post_data['notext'];
            $image = $post_data['noimage'];
            $image = "https://lh3.googleusercontent.com/TVcKTTGGH9AAO5HrddZaiDDJVOqxKXqrxBdqu6Foxo1NgJAZg95EuRHsrrHmlBo6Iw=w300-rw";

            $message = array('message' => $message, 'image' => $image);
            echo '<pre>';
            print_r($gcmRegistrationIds);//exit;
            $this->commonmodel->sendFCM($message, $gcmRegistrationIds);
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    // Created by Neelesh Chouksey on 2016.10.24
    public function send_test_notification($message = '')
    {
        $RegistrationIds = $this->commonmodel->getRecords('gcm', 'gcmRegistrationId', array("gcm_id" => '7'));

        foreach ($RegistrationIds as $RegistrationId) {
            $gcmRegistrationIds[] = $RegistrationId['gcmRegistrationId'];
        }

        $image = "https://lh3.googleusercontent.com/TVcKTTGGH9AAO5HrddZaiDDJVOqxKXqrxBdqu6Foxo1NgJAZg95EuRHsrrHmlBo6Iw=w300-rw";

        $image = "";

        $message = array('message' => $message, 'image' => $image);
        echo '<pre>';
        print_r($gcmRegistrationIds);
        $this->commonmodel->sendAndroidPushNotification($message, $gcmRegistrationIds);

    }

    // Created by Neelesh Chouksey on 2016.10.24
    public function sms()
    {
        //echo sendsmsGET( '7771983222', 'this is test msg');echo 'in';exit;

        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $data['menu'] = 'sms';
            echo view('includes/header', $data);
            echo view('sms');
            echo view('includes/footer');
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }

    // Created by Neelesh Chouksey on 2016.10.24
    public function send_sms()
    {
        if ($this->session->get('klrdmin_id') != '' && $this->session->get('admin_email') != '') {
            $post_data = $this->request->getPost();
            //echo '<pre>';print_r($post_data);exit;
            $message = $post_data['smstext'];

            if ($post_data['smstype'] == 'profile_incomplete') {
                $this->db->select('u.phone_no')
                    ->from('user u')
                    ->join('gcm g', 'g.user_id = u.user_id');
                $rs = $this->db->get();
                $phone_nos = $rs->result_array();
            } else if ($post_data['smstype'] == 'download_app') {
                $query = "SELECT phone_no FROM user where user.user_id not in (select user_id from gcm)";
                $rs = $this->db->query($query);
                $phone_nos = $rs->result_array();
            } else if ($post_data['smstype'] == 'all_community') {
                $phone_nos = $this->commonmodel->getRecords('community_mobile_no ', 'phone_no');
            } else {
                $phone_nos = $this->commonmodel->getRecords('user', 'phone_no');
            }

            foreach ($phone_nos as $phone_no) {
                //echo $result = sendsmsGET( $phone_no, $message);
                echo $result = sendsmsGET($phone_no['phone_no'], $message);
                echo '<br />';
            }
        } else {
            $this->session->sess_destroy();
            redirect('admin');
        }
    }


}