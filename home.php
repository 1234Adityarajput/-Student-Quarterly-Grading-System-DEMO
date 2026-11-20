<h3>Welcome to Student Quarterly Grading System</h3>
<hr>
<div class="col-12">
    <div class="row gx-3 row-cols-4">
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="w-100 d-flex align-items-center">
                        <div class="col-auto pe-1">
                            <span class="fa fa-book-open fs-3 text-primary"></span>
                        </div>
                        <div class="col-auto flex-grow-1">
                            <div class="fs-5"><b>Subjects</b></div>
                            <div class="fs-6 text-end fw-bold">
                                <?php 
                                $subject = 0;
                                if ($conn && !($conn->connect_errno)) {
                                    $res = $conn->query("SELECT count(subject_id) as `count` FROM `subjects`");
                                    if ($res && ($res instanceof mysqli_result) && ($r = $res->fetch_array())) {
                                        $subject = (int)$r['count'];
                                    }
                                }
                                echo $subject > 0 ? format_num($subject) : 0;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="w-100 d-flex align-items-center">
                        <div class="col-auto pe-1">
                            <span class="fa fa-th-list fs-3 text-dark"></span>
                        </div>
                        <div class="col-auto flex-grow-1">
                            <div class="fs-5"><b>Class</b></div>
                            <div class="fs-6 text-end fw-bold">
                                <?php 
                                $class = 0;
                                if ($conn && !($conn->connect_errno)) {
                                    $res = $conn->query("SELECT count(class_id) as `count` FROM `class_list`");
                                    if ($res && ($res instanceof mysqli_result) && ($r = $res->fetch_array())) {
                                        $class = (int)$r['count'];
                                    }
                                }
                                echo $class > 0 ? format_num($class) : 0;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="w-100 d-flex align-items-center">
                        <div class="col-auto pe-1">
                            <span class="fa fa-users fs-3 text-warning"></span>
                        </div>
                        <div class="col-auto flex-grow-1">
                            <div class="fs-5"><b>Students</b></div>
                            <div class="fs-6 text-end fw-bold">
                                <?php 
                                $student = 0;
                                if ($conn && !($conn->connect_errno)) {
                                    $res = $conn->query("SELECT count(student_id) as `count` FROM `student_list`");
                                    if ($res && ($res instanceof mysqli_result) && ($r = $res->fetch_array())) {
                                        $student = (int)$r['count'];
                                    }
                                }
                                echo $student > 0 ? format_num($student) : 0;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="w-100 d-flex align-items-center">
                        <div class="col-auto pe-1">
                            <span class="fa fa-file-alt fs-3 text-info"></span>
                        </div>
                        <div class="col-auto flex-grow-1">
                            <div class="fs-5"><b>Assessments</b></div>
                            <div class="fs-6 text-end fw-bold">
                                <?php 
                                $assessment = 0;
                                if ($conn && !($conn->connect_errno)) {
                                    $res = $conn->query("SELECT count(assessment_id) as `count` FROM `assessment_list`");
                                    if ($res && ($res instanceof mysqli_result) && ($r = $res->fetch_array())) {
                                        $assessment = (int)$r['count'];
                                    }
                                }
                                echo $assessment > 0 ? format_num($assessment) : 0;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
