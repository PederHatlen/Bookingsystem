<?php
    session_start();

    // Easter egg
    if (!defined("IS_INCLUDED")){
        header("location: https://youtu.be/dQw4w9WgXcQ");
    }

    function connect(){
        // Connectiondetails, example for this documents can be found in database_example.php, realfile is gitignore
        include 'database.php';

        // Create connection
        $con = mysqli_connect($host, $username, $password, $db);
        // Check connection
        if (!$con) {die("Connection failed: " . mysqli_connect_error());}
    
        // UTF-8 as charracter encoding for transactions
        $con->set_charset("utf8");
    
        return $con;
    }
    function login($user_id, $username, $name, $surname){
        //Login is done with setting session variables, better to let php handle it
        $_SESSION["user_id"] = $user_id;
        $_SESSION["username"] = $username;
        $_SESSION["name"] = $name;
        $_SESSION["surname"] = $surname;
    }
    function logoff(){
        //unsetting everything
        unset($_SESSION);
    }
    function is_logedin($con){
        if (isset($_SESSION["user_id"]) && isset($_SESSION["username"]) && isset($_SESSION["name"]) && isset($_SESSION["surname"])){

            $stmt = $con->prepare('SELECT * FROM users WHERE user_id = ? and username = ?');
            $stmt->bind_param('ss', $_SESSION["user_id"], $_SESSION["username"]); // 's' specifies the variable type => 'string'
            $stmt->execute();
            $result = $stmt->get_result()->num_rows;

            if ($result == 1) {
                return TRUE;
            }else{
                logoff();
                return FALSE;
            }
        } else{
            return FALSE;
        }
    }
    function rand_color() {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }
?>
