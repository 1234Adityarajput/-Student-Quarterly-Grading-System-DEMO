<?php
session_start();
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}
require_once('DBConnection.php');
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucwords(str_replace('_',' ',$page)) ?> | Student Quarterly Grading System</title>
    <link rel="stylesheet" href="./Font-Awesome-master/css/all.min.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./select2/css/select2.min.css">
    <script src="./js/jquery-3.6.0.min.js"></script>
    <script src="./js/popper.min.js"></script>
    <script src="./js/bootstrap.min.js"></script>
    <script src="./select2/js/select2.full.min.js"></script>
    <link rel="stylesheet" href="./DataTables/datatables.min.css">
    <script src="./DataTables/datatables.min.js"></script>
    <script src="./Font-Awesome-master/js/all.min.js"></script>
    <script src="./js/script.js"></script>
    <style>
        html,body{
            height:100%;
            width:100%;
        }
        main{
            height:100%;
            display:flex;
            flex-flow:column;
        }
        #page-container{
            flex: 1 1 auto; 
            overflow:auto;
        }
        #topNavBar{
            flex: 0 1 auto; 
        }
        .thumbnail-img{
            width:50px;
            height:50px;
            margin:2px
        }
        .truncate-1 {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
        }
        .truncate-3 {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        .modal-dialog.large {
            width: 80% !important;
            max-width: unset;
        }
        .modal-dialog.mid-large {
            width: 50% !important;
            max-width: unset;
        }
        @media (max-width:720px){
            .modal-dialog.large {
                width: 100% !important;
                max-width: unset;
            }
            .modal-dialog.mid-large {
                width: 100% !important;
                max-width: unset;
            }  
        }
        .display-select-image{
            width:60px;
            height:60px;
            margin:2px
        }
        img.display-image {
            width: 100%;
            height: 45vh;
            object-fit: cover;
            background: black;
        }
        /* width */
        ::-webkit-scrollbar {
            width: 5px;
        }
        /* Track */
        ::-webkit-scrollbar-track {
            background: #f1f1f1; 
        }
        /* Handle */
        ::-webkit-scrollbar-thumb {
            background: #888; 
        }
        /* Handle on hover */
        ::-webkit-scrollbar-thumb:hover {
            background: #555; 
        }
        .img-del-btn{
            right: 2px;
            top: -3px;
        }
        .img-del-btn>.btn{
            font-size: 10px;
            padding: 0px 2px !important;
        }
    </style>
</head>
<body>
    <main>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark bg-gradient" id="topNavBar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fa fa-graduation-cap text-warning"></i> SQGS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'home')? 'active' : '' ?>" aria-current="page" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'class')? 'active' : '' ?>" href="index.php?page=class">Class</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'students')? 'active' : '' ?>" aria-current="page" href="index.php?page=students">Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'assessments')? 'active' : '' ?>" aria-current="page" href="index.php?page=assessments">Assessments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'marks')? 'active' : '' ?>" aria-current="page" href="index.php?page=marks">Marks</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'reports')? 'active' : '' ?>" aria-current="page" href="index.php?page=reports">Report</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'users')? 'active' : '' ?>" aria-current="page" href="index.php?page=users">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page == 'maintenance')? 'active' : '' ?>" href="index.php?page=maintenance">Maintenance</a>
                    </li>
                </ul>
            </div>
            <div>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle bg-transparent text-light border-0" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-user-circle"></i> Hello <?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : 'User' ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton1">
                    <li><a class="dropdown-item" href="index.php?page=manage_account"><i class="fa fa-cog text-secondary"></i> Manage Account</a></li>
                    <li><a class="dropdown-item" href="db_setup.php"><i class="fa fa-database text-info"></i> DB Setup Tool</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="Actions.php?a=logout"><i class="fa fa-sign-out-alt text-danger"></i> Logout</a></li>
                </ul>
            </div>
            </div>
        </div>
    </nav>
    <div class="container py-3" id="page-container">
        <?php 
            if(isset($_SESSION['flashdata'])):
        ?>
        <div class="dynamic_alert alert alert-<?php echo $_SESSION['flashdata']['type'] ?>">
            <div class="float-end"><a href="javascript:void(0)" class="text-dark text-decoration-none" onclick="$(this).closest('.dynamic_alert').hide('slow').remove()">x</a></div>
            <?php echo $_SESSION['flashdata']['msg'] ?>
        </div>
        <?php unset($_SESSION['flashdata']); ?>
        <?php endif; ?>
        <?php
            $allowed_pages = [
                'home', 'class', 'students', 'assessments', 'marks', 
                'reports', 'users', 'maintenance', 'manage_account', 
                'manage_assessment', 'manage_class', 'manage_component', 
                'manage_percentage', 'manage_student', 'manage_subject', 
                'manage_user', 'transmutation_table'
            ];
            if(in_array($page, $allowed_pages) && file_exists($page.'.php')){
                include $page.'.php';
            }else{
                include 'home.php';
            }
        ?>
    </div>
    </main>
    <div class="modal fade" id="uni_modal" role='dialog' data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm rounded-0 btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Save</button>
                <button type="button" class="btn btn-sm rounded-0 btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
        </div>
    </div>
    <div class="modal fade" id="uni_modal_secondary" role='dialog' data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm rounded-0 btn-primary" id='submit' onclick="$('#uni_modal_secondary form').submit()">Save</button>
                <button type="button" class="btn btn-sm rounded-0 btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
        </div>
    </div>
    <div class="modal fade" id="confirm_modal" role='dialog'>
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content rounded-0">
            <div class="modal-header py-2">
                <h5 class="modal-title">Confirmation</h5>
            </div>
            <div class="modal-body">
                <div id="delete_content"></div>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-primary btn-sm rounded-0" id='confirm' onclick="">Continue</button>
                <button type="button" class="btn btn-secondary btn-sm rounded-0" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
        </div>
    </div>
</body>
</html>
