<?php
    session_start();

    function connect(){
        include 'database.php';
        // Create connection
        $con = mysqli_connect($host, $username, $password, $db);
        // Check connection
        if (!$con) {die("Connection failed: " . mysqli_connect_error());}
    
        //Angi UTF-8 som tegnsett
        $con->set_charset("utf8");
    
        return $con;
    }
    function login($user_id, $username, $name, $surname){
        $_SESSION["user_id"] = $user_id;
        $_SESSION["username"] = $username;
        $_SESSION["name"] = $name;
        $_SESSION["surname"] = $surname;
    }
    function is_logedin(){
        if (isset($_SESSION["user_id"]) && isset($_SESSION["username"])){
            return TRUE;
        } else{
            return FALSE;
        }
    }
    function rand_color() {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }
?>
