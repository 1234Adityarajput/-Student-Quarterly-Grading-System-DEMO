<?php
session_start();
if(isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0){
    header("Location: index.php");
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
    <title>LOGIN | Student Quarterly Grading System</title>
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <script src="./js/jquery-3.6.0.min.js"></script>
    <script src="./js/popper.min.js"></script>
    <script src="./js/bootstrap.min.js"></script>
    <script src="./js/script.js"></script>
    <style>
        html, body{
            height:100%;
        }
    </style>
</head>
<body class="bg-secondary bg-gradient">
   <div class="h-100 d-flex justify-content-center align-items-center">
       <div class='w-100'>
        <h1 class="py-4 text-center text-light">Student Quarterly Grading System</h1>
        <div class="card my-3 col-md-4 offset-md-4 rounded-0 shadow-lg border-0">
            <div class="card-body p-4">
                <form action="" id="login-form">
                    <div class="text-center mb-3">
                        <h4 class="fw-bold text-secondary">Administrator Login</h4>
                        <small class="text-muted">Please enter your credentials below</small>
                    </div>
                    <div class="form-group mb-3">
                        <label for="username" class="control-label fw-bold">Username</label>
                        <input type="text" id="username" autofocus name="username" class="form-control rounded-0" placeholder="admin" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="password" class="control-label fw-bold">Password</label>
                        <input type="password" id="password" name="password" class="form-control rounded-0" placeholder="admin123" required>
                    </div>
                    <div class="form-group d-flex w-100 justify-content-between align-items-center">
                        <a href="db_setup.php" class="text-decoration-none small text-muted">
                            <i class="fa fa-database"></i> DB Setup Tool
                        </a>
                        <button type="submit" class="btn btn-primary rounded-0 px-4">Login</button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-light py-2 text-center text-muted small">
                Default: <strong>admin</strong> / <strong>admin123</strong>
            </div>
        </div>
       </div>
   </div>
</body>
<script>
    $(function(){
        $('#login-form').submit(function(e){
            e.preventDefault();
            $('.pop_msg').remove();
            var _this = $(this);
            var _el = $('<div>');
            _el.addClass('pop_msg');
            _this.find('button').attr('disabled', true);
            _this.find('button[type="submit"]').text('Logging in...');
            $.ajax({
                url: 'Actions.php?a=login',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'JSON',
                error: function(err){
                    console.log(err);
                    _el.addClass('alert alert-danger');
                    _el.html("An error occurred connecting to server. <br><a href='db_setup.php' class='alert-link'>Check Database Health (db_setup.php)</a>");
                    _this.prepend(_el);
                    _el.show('slow');
                    _this.find('button').attr('disabled', false);
                    _this.find('button[type="submit"]').text('Login');
                },
                success: function(resp){
                    if(resp.status == 'success'){
                        _el.addClass('alert alert-success');
                        _el.text(resp.msg);
                        _this.prepend(_el);
                        _el.show('slow');
                        setTimeout(() => {
                            location.replace('index.php');
                        }, 1000);
                    }else{
                        _el.addClass('alert alert-danger');
                        _el.text(resp.msg);
                        _this.prepend(_el);
                        _el.show('slow');
                        _this.find('button').attr('disabled', false);
                        _this.find('button[type="submit"]').text('Login');
                    }
                }
            });
        });
    });
</script>
</html>
