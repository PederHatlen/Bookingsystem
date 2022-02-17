<?php
    include 'phpRepo.php';

    if (!is_logedin()){
        header('Location: login.php');
    }

    $message = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['room']) && isset($_POST['from']) && isset($_POST['to'])){
            $from = strtotime($_POST["from"]);
            $fromFormat = date('Y-m-d H:i:s', $from);

            $to = strtotime($_POST["to"]);
            $toFormat = date('Y-m-d H:i:s', $to);
            // echo $_POST["room"]." | ".$_SESSION["user_id"]." | ".$to." | ".$from;

            $datediff = ($to - $from) / (60 * 60 * 24);
            
            $con = connect();
            $stmt = $con->prepare('SELECT * FROM booking WHERE room_id = ? AND (? BETWEEN time_from AND time_to OR ? BETWEEN time_from AND time_to)');
            $stmt->bind_param('iss', $_POST['room'], $fromFormat, $toFormat);
            $stmt->execute();
            $conflictions = $stmt->get_result()->num_rows;

            if ($conflictions == 0){
                if ($datediff < 1){
                    $stmt = $con->prepare('INSERT INTO booking (room_id, user_id, time_from, time_to) VALUES(?,?,?,?)');
                    $stmt->bind_param('iiss', $_POST["room"], $_SESSION["user_id"], $fromFormat, $toFormat);
                    $stmt->execute();
                    $message = "Bookingen ble laget.";
                    header('Location: booking.php');
                }else{
                    $message = "Det er en for lang periode.";
                }     
            }else{
                $message = "Den tiden er opptatt.";
            }   
        }
    }
    if ($_SERVER["REQUEST_METHOD"] == "GET"){

    }

    $search_from = strtotime('today midnight');
    $search_to = strtotime('tomorrow midnight');

    $searchDate_from = date('Y-m-d H:i:s', $search_from);
    $searchDate_to = date('Y-m-d H:i:s', $search_to);

    $con = connect();

    $stmt = $con->prepare('SELECT * FROM room');
    $stmt->execute();
    $rooms = $stmt->get_result();

    $roomsArr = $rooms->fetch_all($mode = MYSQLI_BOTH);

    $stmt = $con->prepare('SELECT * FROM booking WHERE time_from >= ? or time_to < ?');
    $stmt->bind_param('ss', $searchDate_from, $searchDate_to); // 's' specifies the variable type => 'string'
    $stmt->execute();
    $bookings = $stmt->get_result();

    $bookingMargin = 30;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header><h1>Booking</h1></header>
    <main>
        <form name="newBooking" id="newBooking" method="post"">
            <input type="hidden" name="form" value="make">
            <label for="roomSelect">Jeg vil booke:</label>
            <select name="roomSelect" id="roomSelect" onchange="selectChange()">
                <?php
                    for ($i=0; $i < count($roomsArr); $i++) { 
                        echo '<option value="'.$roomsArr[$i]["room_id"].'">'.$roomsArr[$i]["room_name"].'</option>';
                    }
                ?>
            </select>
            <input type="hidden" name="room" id="roomValue" value="room">

            <div class="formTime">
                <label for="from">Jeg vil booke fra:</label>
                <input type="datetime-local" name="from" id="from">
            </div class="formTime">
            <div>
            <label for="to">Jeg vil booke til:</label><input type="datetime-local" name="to" id="to">
            </div>
            <input type="submit" value="Send bookingen">
        </form>
        <?php echo "<p>".$message."</p>";?>
        <br>
        <svg width="100%" height=<?php echo '"'.((mysqli_num_rows($rooms)*100)+$bookingMargin+1).'"'?> xmlns="http://www.w3.org/2000/svg">
            <defs>
                <style>.small { font: italic 10px sans-serif; fill: white;}</style>
                <pattern y=<?php echo '"'.$bookingMargin.'"'?> x=<?php echo '"'.$bookingMargin.'"'?> id="grid" width="calc((100% - 30px)/12 - 1px/12)" height="100" patternUnits="userSpaceOnUse">
                    <rect width="100%" height="100%" fill="none" stroke="gray" stroke-width="1"/>
                </pattern>

            </defs>
            <rect width="100%" x=<?php echo '"'.$bookingMargin.'"'?> y=<?php echo '"'.$bookingMargin.'"'?> height=<?php echo '"'.((100 * mysqli_num_rows($rooms)) + 31).'"';?> fill="url(#grid)" />

            <g>
                <?php for ($i=0; $i < 12; $i++) {echo '<text x="10" y="20" class="small">'.str_pad($i*2, 2, "0", STR_PAD_LEFT).':00</text>';}?>
            </g>
            <g>
                <?php 
                var_dump($roomsArr);
                for ($i=0; $i < count($roomsArr); $i++) { 
                    echo '<text x="15" y="'.((100*$i)+$bookingMargin).'" class="small" writing-mode="vertical-rl">'.$roomsArr[$i]["room_name"].'</text>';
                }?>
            </g>


            <?php
                $searchOffset = $search_to-$search_from;
                while ($row = $bookings->fetch_assoc()) {
                    $from = strtotime($row["time_from"]);
                    $to = strtotime($row["time_to"]);

                    $width = (($to - $from)/($searchOffset))*100;
                    $x = (($from-$search_from)/($searchOffset))*100;
                    $y = (intval($row["room_id"])*100) - 70;
                    echo '<rect id="'.$row["booking_id"].'" x="calc('.$x.'% + 30px)" y="'.$y.'" width="calc('.$x.'% + 30px)" height="100" fill="'.rand_color().'" onclick="bookingInfo(this)"/>';
                }
            ?>

        </svg>
        <div id="bookingInfo">
            <?php
                while ($row = $bookings->fetch_assoc()) {
                    echo '<rect class="booking" id="'.$row["booking_id"].'" x="'.$x.'%" y="'.((intval($row["room_id"])*100)-70).'" width="'.$width.'%" height="calc((100% - 50px)/'.mysqli_num_rows($rooms).' - 1px/'.mysqli_num_rows($rooms).')" fill="'.rand_color().'" onclick="bookingInfo(this)"/>';
                }
            ?>
        </div>
    </main>
    <script>
        let roomSelectEL = document.getElementById("roomSelect");
        let roomValueEL = document.getElementById("roomValue");

        let bookingsElArr = document.getElementsByClassName("booking");

        function selectChange(){
            roomValueEL.value = roomSelectEL.value;
        }
        function bookingInfo(e){
            for (let i = 0; i < bookingsElArr.length; i++) {
                bookingsElArr[i].stroke = "";

            }
            e.stroke = "white"
        }
        selectChange();
    </script>
</body>
</html>
