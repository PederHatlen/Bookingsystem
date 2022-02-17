<?php
    include 'phpRepo.php';
    echo var_dump($_SESSION);
    if (!is_logedin()){
        header('Location: login.php');
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['room']) && isset($_POST['from']) && isset($_POST['to'])){
            echo var_dump($_POST);
            $from = strtotime($_POST["from"]);
            $from = date('Y-m-d H:i:s', $from);

            $to = strtotime($_POST["to"]);
            $to = date('Y-m-d H:i:s', $to);
            echo $_POST["room"]." | ".$_SESSION["user_id"]." | ".$to." | ".$from;

            $con = connect();
            $stmt = $con->prepare('INSERT INTO booking (room_id, user_id, time_from, time_to) VALUES(?,?,?,?)');
            $stmt->bind_param('iiss', $_POST["room"], $_SESSION["user_id"], $from, $to);
            $stmt->execute();
            $messages = $stmt->get_result();
        }
    }
    if ($_SERVER["REQUEST_METHOD"] == "GET"){

    }

    $searchDate_from = strtotime('today midnight');
    $searchDate_to = strtotime('tomorrow midnight');

    $search_from = date('Y-m-d H:i:s', $searchDate_from);
    $search_to = date('Y-m-d H:i:s', $searchDate_to);

    $con = connect();

    $stmt = $con->prepare('SELECT * FROM room');
    $stmt->execute();
    $rooms = $stmt->get_result();


    $stmt = $con->prepare('SELECT * FROM booking WHERE time_from >= ? or time_to < ?');
    $stmt->bind_param('ss', $search_from, $search_to); // 's' specifies the variable type => 'string'
    $stmt->execute();
    $bookings = $stmt->get_result();
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
    <form name="newBooking" id="newBooking" method="post"">
        <input type="hidden" name="form" value="make">
        <select name="room" id="roomSelect" onchange="selectChange()">
            <?php
                while ($row = $rooms->fetch_assoc()) {
                    echo '<option value="'.$row["room_id"].'">'.$row["room_name"].'</option>';
                }
            ?>
        </select>
        <input type="hidden" name="room" id="roomValue" value="room">
        <label for="from">Time from:</label><input type="datetime-local" name="from" id="from">
        <label for="to">Time to:</label><input type="datetime-local" name="to" id="to">
        <input type="submit" value="Send bookingen">
    </form>
    <br>
    <main>
    
    <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <pattern id="grid" width="calc(100%/12 - 1px/12)" height=<?php echo '"calc(100%/'.mysqli_num_rows($rooms).' - 1px/'.mysqli_num_rows($rooms).')"';?> patternUnits="userSpaceOnUse">
                <rect width="100%" height="100%" fill="none" stroke="gray" stroke-location="center" stroke-width="1"/>
            </pattern>
        </defs>
            
        <rect width="100%" height="100%" fill="url(#grid)" />

        <?php
            while ($row = $bookings->fetch_assoc()) {
                $from = strtotime($row["time_from"]);
                $to = strtotime($row["time_to"]);

                $width = (intval($row["time_to"]) - intval($row["time_from"]))/(intval($searchDate_to) - intval($searchDate_from))*100;
                $x = $width;
                echo '<rect x="'.intval(($row["room_id"]/mysqli_num_rows($rooms))*100).'%" width="'.$width.'%" height="calc(100%/'.mysqli_num_rows($rooms).')" fill="lightcoral"/>';
            }
        ?>

    </svg>  
    </main>
    <script>
        let roomSelectEL = document.getElementById("roomSelect");
        let roomValueEL = document.getElementById("roomValue");

        function selectChange(){
            roomValueEL.value = roomSelectEL.value
            console.log(roomValueEL.value);
        }
    </script>
</body>
</html>
