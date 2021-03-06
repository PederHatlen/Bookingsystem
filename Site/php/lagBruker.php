<?php
    ////////////////////////////
    /// Hentet fra BinærChat ///
    ////////////////////////////

    // Main PHP bulk, it is before the document because redirecting does not work otherwise
    define("IS_INCLUDED", TRUE);
    include 'phpRepo.php';
    $message = "<br>";
    $isError = TRUE;

    // if post data, retrieve it and make variables
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if (isset($_POST['username']) && isset($_POST['name']) && isset($_POST['password'])){
            $username = $_POST['username'];
            $name = $_POST['name'];
            $surname = $_POST['surname'];
            // Hashing the password, PHP includes salt. Password_default hashing because PHP knows best
            $pwd = password_hash($_POST['password'], PASSWORD_DEFAULT);

            $con = connect();
            
            // Finding if user allready exists
            $stmt = $con->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->bind_param('s', $username); // 's' specifies the variable type => 'string'
            $stmt->execute();
            
            $result = $stmt->get_result();
            
            // If user allready exists/output from query is not null
            if (mysqli_num_rows($result) == null) {
                // Making a new user, W. username and hashed password, server SQL code does rest
                $stmt = $con->prepare('INSERT into users (username, name, password) VALUES (?, ?, ?)');
                $stmt->bind_param('sss', $username, $name, $pwd); // 's' specifies the variable type => 'string'
                $stmt->execute();
                // Retrieve the inserted id created with Auto increment in sql
                $user_id = $stmt->insert_id;

                // Login function found in phprepo
                login($user_id, $username, $name);
                $con->close();

                $message = 'Brukeren er registrert og inlogget.';
                $isError = FALSE;
                header('Location: booking.php');
            }else{
                $message = "Brukernavnet er allerede tatt :(";
            }
        }else{
            $message = 'Det ble ikke sendt med nokk detaljer.';
        }
    }
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking | LagBruker</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header><h1>Lag bruker</h1></header>
    <main>
        <!-- Page info and explaination -->
        <p>Har du allerede en bruker? <a href="login.php">Logg inn</a></p>

        <!-- Form for inputting userdate (u.name & pwd etc.), password has too be typed twice, done with JS -->
        <form action="" method="post">
            <input type="text" name="username" id="username" placeholder="Brukernavn"><br>
            <input type="text" name="name" id="name" placeholder="Fult navn"><br>
            <input type="password" name="password" id="password" placeholder="Passord"><br>
            <input type="password" name="passwordControll" id="passwordControll" placeholder="Gjenta passord"><br>
            <input type="submit" value="Lag bruker" id="submit" class="submitwmargin"><br>
        </form>

        <p><?php echo('<p class="'.($isError? 'ErrorMSG':'SuccessMSG').'">'.$message.'</p>');?></p>

    </main>
    <!-- Extra script, for password validation++ -->
    <script src="../js/lagBrukerScript.js"></script>
</body>
</html>
