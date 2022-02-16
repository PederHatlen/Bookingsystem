<?php
    include 'phpRepo.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        echo var_dump($_POST);
        $con = connect();
        $stmt = $con->prepare('INSERT INTO ');
        $stmt->bind_param('i', $conversation_id);
        $stmt->execute();
        $messages = $stmt->get_result();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form name="newBooking" id="newBooking" method="post">
        <input type="hidden" name="form" value="newBooking">
        <label for="from">Time from:</label><input type="datetime-local" name="from" id="from">
        <label for="to">Time to:</label><input type="datetime-local" name="to" id="to">
        <input type="submit" value="Send bookingen">
    </form>

    <main>
    <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <pattern id="grid" width="10%" height="10%" patternUnits="userSpaceOnUse">
                <rect width="100%" height="100%" fill="none" stroke="gray" stroke-location="inside" stroke-width="1"/>
            </pattern>
        </defs>
            
        <rect width="100%" height="100%" fill="url(#grid)" />
    </svg>  
    </main>
</body>
</html>