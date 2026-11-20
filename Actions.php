<?php 
session_start();
require_once('DBConnection.php');

Class Actions extends DBConnection{
    function __construct(){
        parent::__construct();
    }
    function __destruct(){
        parent::__destruct();
    }

    /**
     * Safe query count helper to prevent PHP 8.x fatal errors on boolean queries
     */
    protected function query_count($sql){
        if(!$this->db || $this->db->connect_errno) return 0;
        $qry = $this->db->query($sql);
        if($qry && ($qry instanceof mysqli_result) && ($row = $qry->fetch_array())){
            return isset($row['count']) ? (int)$row['count'] : (int)reset($row);
        }
        return 0;
    }

    function login(){
        extract($_POST);
        if(!$this->db || $this->db->connect_errno){
            $resp['status'] = "failed";
            $resp['msg'] = "Database connection error. Please verify database settings or run db_setup.php.";
            return json_encode($resp);
        }
        $username = isset($username) ? $this->db->real_escape_string($username) : '';
        $password = isset($password) ? md5($password) : '';
        
        $sql = "SELECT * FROM `admin_list` WHERE `username` = '{$username}' AND `password` = '{$password}' ";
        $qry = $this->db->query($sql);
        
        if(!$qry || !($qry instanceof mysqli_result) || $qry->num_rows <= 0){
            $resp['status'] = "failed";
            $resp['msg'] = "Invalid username or password.";
        }else{
            $row = $qry->fetch_array();
            $resp['status'] = "success";
            $resp['msg'] = "Login successfully.";
            foreach($row as $k => $v){
                if(!is_numeric($k))
                $_SESSION[$k] = $v;
            }
        }
        return json_encode($resp);
    }

    function logout(){
        session_destroy();
        header("Location: login.php");
        exit;
    }

    function save_user(){
        extract($_POST);
        $data = "";
        $cols = [];
        $values = [];
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id','type'))){
                $v = $this->db->real_escape_string($v);
                if(!empty($id)){
                    if(!empty($data)) $data .= ",";
                    $data .= " `{$k}` = '{$v}' ";
                }else{
                    $cols[] = "`{$k}`";
                    $values[] = "'{$v}'";
                }
            }
        }
        if(empty($id)){
            $cols[] = '`password`';
            $values[] = "'".md5($username)."'";
        }
        if(isset($cols) && isset($values) && count($cols) > 0){
            $data = "(".implode(',',$cols).") VALUES (".implode(',',$values).")";
        }
        
        $check = $this->query_count("SELECT COUNT(admin_id) as `count` FROM `admin_list` WHERE `username` = '{$username}' " . (!empty($id) && $id > 0 ? " AND admin_id != '{$id}' " : ""));
        if($check > 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "Username already exists.";
        }else{
            if(empty($id)){
                $sql = "INSERT INTO `admin_list` {$data}";
            }else{
                $sql = "UPDATE `admin_list` SET {$data} WHERE admin_id = '{$id}'";
            }
            $save = $this->db->query($sql);
            if($save){
                $resp['status'] = 'success';
                if(empty($id))
                    $resp['msg'] = 'New Admin User successfully saved.';
                else
                    $resp['msg'] = 'Admin User Details successfully updated.';
            }else{
                $resp['status'] = 'failed';
                $resp['msg'] = 'Saving Admin User Details Failed. Error: '.$this->db->error;
                $resp['sql'] = $sql;
            }
        }
        return json_encode($resp);
    }

    function delete_user(){
        extract($_POST);
        $id = isset($id) ? (int)$id : 0;
        $delete = $this->db->query("DELETE FROM `admin_list` WHERE admin_id = '{$id}'");
        if($delete){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Admin User successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->db->error;
        }
        return json_encode($resp);
    }

    function update_credentials(){
        extract($_POST);
        $data = "";
        if(empty($password)){
            unset($_POST['password']);
        }
        foreach($_POST as $k => $v){
            if(!in_array($k,array('id','old_password')) && !empty($v)){
                if(!empty($data)) $data .= ",";
                if($k == 'password') $v = md5($v);
                $v = $this->db->real_escape_string($v);
                $data .= " `{$k}` = '{$v}' ";
            }
        }
        if(md5($old_password) != $_SESSION['password']){
            $resp['status'] = 'failed';
            $resp['msg'] = "Old password is incorrect.";
        }else{
            $sql = "UPDATE `admin_list` SET {$data} WHERE admin_id = '{$_SESSION['admin_id']}'";
            $save = $this->db->query($sql);
            if($save){
                $resp['status'] = 'success';
                $_SESSION['flashdata']['type'] = 'success';
                $_SESSION['flashdata']['msg'] = 'Credential successfully updated.';
                foreach($_POST as $k => $v){
                    if(!in_array($k,array('id','old_password')) && !empty($v)){
                        if($k == 'password') $v = md5($v);
                        $_SESSION[$k] = $v;
                    }
                }
            }else{
                $resp['status'] = 'failed';
                $resp['msg'] = 'Updating Credentials Failed. Error: '.$this->db->error;
                $resp['sql'] = $sql;
            }
        }
        return json_encode($resp);
    }

    function save_subject(){
        extract($_POST);
        $name = isset($name) ? $this->db->real_escape_string($name) : '';
        if(empty($id))
            $sql = "INSERT INTO `subjects` (`name`) VALUES ('{$name}')";
        else{
            $data = "";
            foreach($_POST as $k => $v){
                if(!in_array($k,array('id'))){
                    if(!empty($data)) $data .= ", ";
                    $v = $this->db->real_escape_string($v);
                    $data .= " `{$k}` = '{$v}' ";
                }
            }
            $sql = "UPDATE `subjects` SET {$data} WHERE `subject_id` = '{$id}' ";
        }
        $check = $this->query_count("SELECT COUNT(subject_id) as count FROM `subjects` WHERE `name` = '{$name}' ".(!empty($id) && $id > 0 ? " AND subject_id != '{$id}'" : ""));
        if($check > 0){
            $resp['status'] ='failed';
            $resp['msg'] = 'Subject already exists.';
        }else{
            $save = $this->db->query($sql);
            if($save){
                $resp['status']="success";
                if(empty($id))
                    $resp['msg'] = "Subject successfully saved.";
                else
                    $resp['msg'] = "Subject successfully updated.";
            }else{
                $resp['status']="failed";
                if(empty($id))
                    $resp['msg'] = "Saving New Subject Failed.";
                else
                    $resp['msg'] = "Updating Subject Failed.";
                $resp['error']=$this->db->error;
            }
        }
        return json_encode($resp);
    }

    function delete_subject(){
        extract($_POST);
        $id = isset($id) ? (int)$id : 0;
        $update = $this->db->query("UPDATE `subjects` SET delete_flag = 1 WHERE subject_id = '{$id}'");
        if($update){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Subject successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->db->error;
        }
        return json_encode($resp);
    }

    function save_component(){
        extract($_POST);
        $name = isset($name) ? $this->db->real_escape_string($name) : '';
        if(empty($id))
            $sql = "INSERT INTO `grading_components` (`name`) VALUES ('{$name}')";
        else{
            $data = "";
            foreach($_POST as $k => $v){
                if(!in_array($k,array('id'))){
                    if(!empty($data)) $data .= ", ";
                    $v = $this->db->real_escape_string($v);
                    $data .= " `{$k}` = '{$v}' ";
                }
            }
            $sql = "UPDATE `grading_components` SET {$data} WHERE `component_id` = '{$id}' ";
        }
        $check = $this->query_count("SELECT COUNT(component_id) as count FROM `grading_components` WHERE `name` = '{$name}' ".(!empty($id) && $id > 0 ? " AND component_id != '{$id}'" : ""));
        if($check > 0){
            $resp['status'] ='failed';
            $resp['msg'] = 'Component already exists.';
        }else{
            $save = $this->db->query($sql);
            if($save){
                $resp['status']="success";
                if(empty($id))
                    $resp['msg'] = "Component successfully saved.";
                else
                    $resp['msg'] = "Component successfully updated.";
            }else{
                $resp['status']="failed";
                if(empty($id))
                    $resp['msg'] = "Saving New Component Failed.";
                else
                    $resp['msg'] = "Updating Component Failed.";
                $resp['error']=$this->db->error;
            }
        }
        return json_encode($resp);
    }

    function delete_component(){
        extract($_POST);
        $id = isset($id) ? (int)$id : 0;
        $update = $this->db->query("UPDATE `grading_components` SET delete_flag = 1 WHERE component_id = '{$id}'");
        if($update){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Component successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->db->error;
        }
        return json_encode($resp);
    }

    function save_percentage(){
        extract($_POST);
        $data = "";
        if(isset($component_id) && is_array($component_id)){
            foreach($component_id as $k => $v){
                if(!empty($data)) $data .= ", ";
                $p = isset($percentage[$k]) ? (float)$percentage[$k] : 0;
                $data .= "('{$id}','{$v}','{$p}')";
            }
        }
        if(!empty($data))
            $this->db->query("DELETE FROM `component_subject_percentage` WHERE `subject_id` = '{$id}'");
        $sql = "INSERT INTO `component_subject_percentage` (`subject_id`,`component_id`,`percentage`) VALUES {$data}";
        $insert = $this->db->query($sql);
        if($insert){
            $resp['status'] ='success';
            $resp['msg'] = "Data successfully saved";
        }else{
            $resp['status'] ='failed';
            $resp['msg'] = "Data fails to save. Error: ". $this->db->error;
            $resp['sql'] = $sql;
        }
        return json_encode($resp);
    }

    function save_class(){
        extract($_POST);
        $subject_id = isset($subject_id) ? (int)$subject_id : 0;
        $grade = isset($grade) ? $this->db->real_escape_string($grade) : '';
        $section = isset($section) ? $this->db->real_escape_string($section) : '';
        if(empty($id))
            $sql = "INSERT INTO `class_list` (`subject_id`,`grade`,`section`) VALUES ('{$subject_id}','{$grade}','{$section}')";
        else{
            $data = "";
            foreach($_POST as $k => $v){
                if(!in_array($k,array('id'))){
                    if(!empty($data)) $data .= ", ";
                    $v = $this->db->real_escape_string($v);
                    $data .= " `{$k}` = '{$v}' ";
                }
            }
            $sql = "UPDATE `class_list` SET {$data} WHERE `class_id` = '{$id}' ";
        }
        $check = $this->query_count("SELECT COUNT(class_id) as count FROM `class_list` WHERE `subject_id` = '{$subject_id}' AND `grade` ='{$grade}' AND `section` ='{$section}' ".(!empty($id) && $id > 0 ? " AND class_id != '{$id}'" : ""));
        if($check > 0){
            $resp['status'] ='failed';
            $resp['msg'] = 'Class already exists.';
        }else{
            $save = $this->db->query($sql);
            if($save){
                $resp['status']="success";
                if(empty($id))
                    $resp['msg'] = "Class successfully saved.";
                else
                    $resp['msg'] = "Class successfully updated.";
            }else{
                $resp['status']="failed";
                if(empty($id))
                    $resp['msg'] = "Saving New Class Failed.";
                else
                    $resp['msg'] = "Updating Class Failed.";
                $resp['error']=$this->db->error;
            }
        }
        return json_encode($resp);
    }

    function delete_class(){
        extract($_POST);
        $id = isset($id) ? (int)$id : 0;
        $update = $this->db->query("UPDATE `class_list` SET delete_flag = 1 WHERE class_id = '{$id}'");
        if($update){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Class successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->db->error;
        }
        return json_encode($resp);
    }

    function save_student(){
        extract($_POST);
        $class_id = isset($class_id) ? (int)$class_id : 0;
        $name = isset($name) ? $this->db->real_escape_string($name) : '';
        if(empty($id))
            $sql = "INSERT INTO `student_list` (`class_id`,`name`) VALUES ('{$class_id}','{$name}')";
        else{
            $data = "";
            foreach($_POST as $k => $v){
                if(!in_array($k,array('id'))){
                    if(!empty($data)) $data .= ", ";
                    $v = $this->db->real_escape_string($v);
                    $data .= " `{$k}` = '{$v}' ";
                }
            }
            $sql = "UPDATE `student_list` SET {$data} WHERE `student_id` = '{$id}' ";
        }
        $check = $this->query_count("SELECT COUNT(student_id) as count FROM `student_list` WHERE `class_id` = '{$class_id}' AND `name` ='{$name}' ".(!empty($id) && $id > 0 ? " AND student_id != '{$id}'" : ""));
        if($check > 0){
            $resp['status'] ='failed';
            $resp['msg'] = 'Student already exists.';
        }else{
            $save = $this->db->query($sql);
            if($save){
                $resp['status']="success";
                if(empty($id))
                    $resp['msg'] = "Student successfully saved.";
                else
                    $resp['msg'] = "Student successfully updated.";
            }else{
                $resp['status']="failed";
                if(empty($id))
                    $resp['msg'] = "Saving New Student Failed.";
                else
                    $resp['msg'] = "Updating Student Failed.";
                $resp['error']=$this->db->error;
            }
        }
        return json_encode($resp);
    }

    function delete_student(){
        extract($_POST);
        $id = isset($id) ? (int)$id : 0;
        $delete = $this->db->query("DELETE FROM `student_list` WHERE student_id = '{$id}'");
        if($delete){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Student successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->db->error;
        }
        return json_encode($resp);
    }

    function save_assessment(){
        extract($_POST);
        $class_id = isset($class_id) ? (int)$class_id : 0;
        $component_id = isset($component_id) ? (int)$component_id : 0;
        $quarter = isset($quarter) ? (int)$quarter : 0;
        $name = isset($name) ? $this->db->real_escape_string($name) : '';
        $hps = isset($hps) ? (float)$hps : 0;
        if(empty($id))
            $sql = "INSERT INTO `assessment_list` (`class_id`,`component_id`,`quarter`,`name`,`hps`) VALUES ('{$class_id}','{$component_id}','{$quarter}','{$name}','{$hps}')";
        else{
            $data = "";
            foreach($_POST as $k => $v){
                if(!in_array($k,array('id'))){
                    if(!empty($data)) $data .= ", ";
                    $v = $this->db->real_escape_string($v);
                    $data .= " `{$k}` = '{$v}' ";
                }
            }
            $sql = "UPDATE `assessment_list` SET {$data} WHERE `assessment_id` = '{$id}' ";
        }
        $check = $this->query_count("SELECT COUNT(assessment_id) as count FROM `assessment_list` WHERE `class_id` = '{$class_id}' AND `component_id` = '{$component_id}' AND `quarter` = '{$quarter}' AND `name` ='{$name}' ".(!empty($id) && $id > 0 ? " AND assessment_id != '{$id}'" : ""));
        if($check > 0){
            $resp['status'] ='failed';
            $resp['msg'] = 'Assessment already exists.';
        }else{
            $save = $this->db->query($sql);
            if($save){
                $resp['status']="success";
                if(empty($id))
                    $resp['msg'] = "Assessment successfully saved.";
                else
                    $resp['msg'] = "Assessment successfully updated.";
            }else{
                $resp['status']="failed";
                if(empty($id))
                    $resp['msg'] = "Saving New Assessment Failed.";
                else
                    $resp['msg'] = "Updating Assessment Failed.";
                $resp['error']=$this->db->error;
            }
        }
        return json_encode($resp);
    }

    function delete_assessment(){
        extract($_POST);
        $id = isset($id) ? (int)$id : 0;
        $delete = $this->db->query("DELETE FROM `assessment_list` WHERE assessment_id = '{$id}'");
        if($delete){
            $resp['status']='success';
            $_SESSION['flashdata']['type'] = 'success';
            $_SESSION['flashdata']['msg'] = 'Assessment successfully deleted.';
        }else{
            $resp['status']='failed';
            $resp['error']=$this->db->error;
        }
        return json_encode($resp);
    }

    function save_mark(){
        extract($_POST);
        $data = "";
        $assessment_id = isset($assessment_id) ? (int)$assessment_id : 0;
        if(isset($student_id) && is_array($student_id)){
            foreach($student_id as $k => $v){
                if(!empty($data)) $data .=", ";
                $m = isset($mark[$k]) ? (float)$mark[$k] : 0;
                $data .= "('{$assessment_id}','{$v}','{$m}')";
            }
        }
        if(!empty($data)){
            $this->db->query("DELETE FROM `mark_list` WHERE assessment_id = '{$assessment_id}'");
            $sql = "INSERT INTO `mark_list` (`assessment_id`,`student_id`,`mark`) VALUES {$data}";
            $save = $this->db->query($sql);
            if($save){
                $resp['status'] = 'success';
                $resp['msg'] = " Marks Successfully Saved";
            }else{
                $resp['status']='failed';
                $resp['msg'] = "Saving Data Failed. Error: ".$this->db->error;
                $resp['sql'] = $sql;
            }
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = "No Student Listed yet";
        }
        return json_encode($resp);
    }
}

$a = isset($_GET['a']) ? $_GET['a'] : '';
$action = new Actions();
switch($a){
    case 'login':
        echo $action->login();
    break;
    case 'logout':
        echo $action->logout();
    break;
    case 'save_user':
        echo $action->save_user();
    break;
    case 'delete_user':
        echo $action->delete_user();
    break;
    case 'update_credentials':
        echo $action->update_credentials();
    break;
    case 'save_subject':
        echo $action->save_subject();
    break;
    case 'delete_subject':
        echo $action->delete_subject();
    break;
    case 'save_component':
        echo $action->save_component();
    break;
    case 'delete_component':
        echo $action->delete_component();
    break;
    case 'save_percentage':
        echo $action->save_percentage();
    break;
    case 'save_class':
        echo $action->save_class();
    break;
    case 'delete_class':
        echo $action->delete_class();
    break;
    case 'save_student':
        echo $action->save_student();
    break;
    case 'delete_student':
        echo $action->delete_student();
    break;
    case 'save_assessment':
        echo $action->save_assessment();
    break;
    case 'delete_assessment':
        echo $action->delete_assessment();
    break;
    case 'save_mark':
        echo $action->save_mark();
    break;
    default:
        // default action here
    break;
}
