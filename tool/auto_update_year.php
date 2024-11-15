<?php
include_once __DIR__.'/../core/init.php';
db_update('edu_attendance', array('class_year'=>2024), array());
db_update('edu_class', array('class_year'=>2024), array());
db_update('edu_class_user', array('class_year'=>2024), array());
db_update('edu_class_user_days', array('class_year'=>2024), array());
db_update('edu_result', array('class_year'=>2024), array());