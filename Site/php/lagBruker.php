<?php
    // Main PHP bulk, it is before the document because redirecting does not work otherwise
    include 'phpRepo.php';
    $message = "";

    // if post data, retrieve it and make variables
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        if (isset($_POST['username']) && isset($_POST['name']) && isset($_POST['surname']) && isset($_POST['password'])){
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
            if (mysqli_num_rows($result) != null) {
                $message = "Brukernavnet er allerede tatt :(";
            }else{
                // Making a new user, W. username and hashed password, server SQL code does rest
                $stmt = $con->prepare('INSERT into users (username, name, surname, password) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $username, $name, $surname, $pwd); // 's' specifies the variable type => 'string'
                $stmt->execute();
                // Retrieve the inserted id created with Auto increment in sql
                $user_id = $stmt->insert_id;

                // Login function found in phprepo
                login($user_id, $username, $name, $surname);
                $con->close();

                $message = 'Brukeren er registrert og inlogget.';
                header('Location: booking.php');
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

        <!-- Form for inputting userdate (u.name & pwd), password has too be typed twice, done with JS -->
        <form action="" method="post">
            <input type="text" name="username" id="username" placeholder="Brukernavn"><br>
            <input type="text" name="name" id="name" placeholder="Fornavn"><br>
            <input type="text" name="surname" id="surname" placeholder="Etternavn"><br>
            <input type="password" name="password" id="password" placeholder="Passord"><br>
            <input type="password" name="passwordControll" id="passwordControll" placeholder="Gjenta passord"><br>
            <input type="submit" value="Lag bruker" id="submit" class="submitwmargin"><br>
        </form>

        <p><?php echo($message);?></p>

    </main>
    <!-- Extra script, becouse page needs extra functionality -->
    <script src="../js/script.js"></script>
</body>
</html>
