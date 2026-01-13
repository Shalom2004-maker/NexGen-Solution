<?php
$conn = new mysqli("localhost","root","","nexgen_solutions");
if($conn->connect_error){
    die("DB Error");
}
?>