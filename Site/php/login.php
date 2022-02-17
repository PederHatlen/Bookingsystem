<?php
    // Main PHP bulk, it is before the document because redirecting does not work otherwise
    include 'phpRepo.php';
    $con = connect();
    $msgText = '';

    // If post data (data from the form on the site)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // setting temporary variables
        $username = $_POST['username'];
        $pwd = $_POST['password'];

        // finding if the user exists in DB
        $stmt = $con->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->bind_param('s', $username); // 's' specifies the variable type => 'string'
        $stmt->execute();

        $userquery_res = $stmt->get_result()->fetch_assoc();

        // if user exists run login function (from phpRepo), else output error message
        if (!is_null($userquery_res) && password_verify($pwd, $userquery_res['password'])) {
            login($userquery_res["user_id"], $username, $userquery_res["name"], $userquery_res["surname"]);
            $msgText = 'Innloggingen fungerte!';
            header('Location: booking.php');
        }else{
            $msgText = 'Feil brukernavn eller passord.';
        }
        $con->close();
    }
?>


<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking | Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header><h1>Login</h1></header>
    <main>
        <p>Har du ikke laget en bruker? <a href="lagBruker.php">Lag bruker</a></p>

        <!-- Form for inputting login details, text removing/formating done in JS (script.js) -->
        <form action="" method="post">
            <input type="text" name="username" id="username" placeholder="Brukernavn"><br>
            <input type="password" name="password" id="password" placeholder="Passord"><br>
            <input type="submit" value="log in" id="submit" class="submitwmargin"><br>
        </form>
        <p><?php echo $msgText;?></p>
    </main>
    <script src="../js/script.js"></script>
</body>
</html>
