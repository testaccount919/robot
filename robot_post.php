<?php
require 'robot_cleaner.php';

$input = file_get_contents('php://input');
$input = json_decode($input, true);

$robotCleaner = new Robot_Cleaner($input['map'], $input['start'], $input['commands'], $input['battery']);
echo $robotCleaner->run();