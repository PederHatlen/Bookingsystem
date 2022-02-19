<?php
    include 'phpRepo.php';
    $con = connect();

    if (!is_logedin($con)){
        header('Location: login.php');
    }

    $message = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if ((isset($_POST['room']) && isset($_POST['from']) && isset($_POST['to'])) || $_POST["form"] == 'edit'){
            $room = null;
            $from = 0;
            $to = 0;
            
            if ($_POST["form"] == 'make') {
                $room = $_POST["room"];
                $from = $_POST["from"];
                $to = $_POST["to"];
            }else{
                $stmt = $con->prepare('SELECT room_id, time_from, time_to FROM booking WHERE booking_id = ?');
                $stmt->bind_param('i', $_POST["booking_id"]);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_row();

                if (trim($_POST['room']) == ""){$room = $result[0];}else{$room = $_POST['room'];}
                if (trim($_POST['from']) == ""){$from = $result[1];}else{$from = $_POST['from'];}
                if (trim($_POST['to']) == ""){$to = $result[2];}else{$to = $_POST['to'];}
            }

            $from = strtotime($from);
            $to = strtotime($to);

            $fromFormat = date('Y-m-d H:i:s', $from);
            $toFormat = date('Y-m-d H:i:s', $to);

            if (($from < $to) && is_logedin($con)){
                $datediff = ($to - $from) / (60 * 60 * 24);

                $bookingId = 0;
                if (isset($_POST["booking_id"])){
                    $bookingId = $_POST["booking_id"];
                }

                $stmt = $con->prepare('SELECT * FROM booking WHERE room_id = ? AND booking_id != ? AND ((? BETWEEN time_from AND time_to) or (time_from BETWEEN ? AND ?) or (time_from = ?))');
                $stmt->bind_param('iissss', $room, $bookingId, $fromFormat, $fromFormat, $toFormat, $fromFormat);
                $stmt->execute();

                $conflictions = $stmt->get_result()->num_rows;

                if ($conflictions == 0){
                    if ($datediff < 1){
                        switch ($_POST["form"]) {
                            case 'make':
                                $stmt = $con->prepare('INSERT INTO booking (room_id, user_id, time_from, time_to) VALUES(?,?,?,?)');
                                $stmt->bind_param('iiss', $room, $_SESSION["user_id"], $fromFormat, $toFormat);
                                $stmt->execute();
                                $message = "Bookingen ble laget.";
                                break;
                            case 'edit':
                                // echo($room." | ".$fromFormat." | ".$toFormat." | ".$bookingId);
                                $stmt = $con->prepare('UPDATE booking SET room_id = ?, time_from = ?, time_to = ? WHERE booking_id = ?');
                                $stmt->bind_param('issi', $room, $fromFormat, $toFormat, $bookingId);
                                $stmt->execute();
                                $message = "Bookingen ble redigert.";
                                break;
                        }
                        
                        unset($_POST);
                    }else{
                        $message = "Det er en for lang periode.";
                    }     
                }else{
                    $message = "Den tiden er opptatt.";
                }
            }else{
                $message = "Fra er etter til.";
            }
        }
    }

    $search_from = null;
    $search_to = null;

    if (isset($_GET["date"])){
        $search_from = strtotime($_GET["date"]);
        $search_to = strtotime('+1 day', $search_from);

    }else{  
        $search_from = strtotime('today midnight');
        $search_to = strtotime('tomorrow midnight');
    }

    $searchDate_from = date('Y-m-d H:i:s', $search_from);
    $searchDate_to = date('Y-m-d H:i:s', $search_to);

    $stmt = $con->prepare('SELECT * FROM room');
    $stmt->execute();
    $rooms = $stmt->get_result();

    $roomsArr = $rooms->fetch_all($mode = MYSQLI_BOTH);

    $stmt = $con->prepare('SELECT * FROM booking left join users ON booking.user_id = users.user_id WHERE time_from >= ? or time_to < ?');
    $stmt->bind_param('ss', $searchDate_from, $searchDate_to); // 's' specifies the variable type => 'string'
    $stmt->execute();
    $bookings = $stmt->get_result();

    $bookingsArr = $bookings->fetch_all($mode = MYSQLI_BOTH);

    $bookingMarginX = 0;
    $bookingMarginY = 30;

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
    <header>
        <h1>Booking</h1>
    </header>
    <main>
        <form name="newBooking" id="newBooking" method="post">
            <input type="hidden" name="form" value="make">
            <label for="roomSelect">Jeg vil booke:</label>
            <select name="roomSelect" id="roomSelect" onchange="selectChange()">
                <?php
                    for ($i=0; $i < count($roomsArr); $i++) { 
                        echo '<option value="'.$roomsArr[$i]["room_id"].'">'.$roomsArr[$i]["room_name"].'</option>';
                    }
                ?>
            </select>
            <input type="hidden" name="room" id="roomValue" value="1">

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
        <svg width="100%" height=<?php echo '"'.((mysqli_num_rows($rooms)*100)+$bookingMarginY+1).'"'?> xmlns="http://www.w3.org/2000/svg">
            <defs>
                <style>
                    .small { font: 10px "Arial Black", "Arial", sans-serif; fill: white; filter: drop-shadow(0 0 0.5rem #000);}
                    .booking {stroke-width: 20px; clip-path: inset(0 0);}
                </style>
                <pattern x=<?php echo '"'.$bookingMarginX.'"'?> y=<?php echo '"'.$bookingMarginY.'"'?> id="grid" width=<?php echo '"calc((100% - '.$bookingMarginX.'px)/12 - 1px/12)"'?> height="100" patternUnits="userSpaceOnUse">
                    <rect width="100%" height="100%" fill="none" stroke="gray" stroke-width="1"/>
                </pattern>
            </defs>

            <?php
                $searchOffset = $search_to-$search_from;
                for ($i=0; $i < count($bookingsArr); $i++) {
                    $from = strtotime($bookingsArr[$i]["time_from"]);
                    $to = strtotime($bookingsArr[$i]["time_to"]);

                    $width = (($to - $from)/($searchOffset))*100;
                    $x = (($from-$search_from)/($searchOffset))*100;
                    $y = (intval($bookingsArr[$i]["room_id"])*100) - (100 - $bookingMarginY);

                    // echo $width." | ".$x." | ".$y;

                    echo '<rect id="'.$bookingsArr[$i]["booking_id"].'" class="booking" x="'.$x.'%" y="'.$y.'" width="'.$width.'%" height="100" fill="'.rand_color().'"/>';
                }
            ?>

            <rect width="100%" x=<?php echo '"'.$bookingMarginX.'"'?> y=<?php echo '"'.$bookingMarginY.'"'?> height=<?php echo '"'.((100 * mysqli_num_rows($rooms)) + 31).'"';?> fill="url(#grid)" style="pointer-events: none;"/>

            <g><?php 
                for ($i=0; $i < 12; $i++) {
                    echo '<text x="calc((100%/12)*'.($i+0.1).')" y="20" class="small">'.str_pad($i*2, 2, "0", STR_PAD_LEFT).':00</text>';
                }
            ?></g>
            <g><?php 
                for ($i=0; $i < count($roomsArr); $i++) {
                    echo '<text x="15" y="'.((100*$i)+20+$bookingMarginY).'" class="small" writing-mode="vertical-rl">'.$roomsArr[$i]["room_name"].'</text>';
                }
            ?></g>

        </svg>
        <form action="" method="get">
            <input type="date" name="date" id="date">
            <input type="submit" value="Se en annen dag">
        </form>
        <div id="bookingInfo">
            <?php
                for ($i=0; $i < count($bookingsArr); $i++) {
                    echo '<div class="bookingInfo" id="'.$bookingsArr[$i]["booking_id"].'Info" ><p>'.
                    'Booket fra '.
                    date("H:i:s" ,strtotime($bookingsArr[$i]["time_from"])).
                    ', og til '.
                    date("H:i:s" ,strtotime($bookingsArr[$i]["time_to"])).
                    '. Rommet er bokket av '.
                    ($bookingsArr[$i]["name"]." ".$bookingsArr[$i]["surname"]." (@".$bookingsArr[$i]["username"].")").
                    '</p>';
                    if ($bookingsArr[$i]["username"] == $_SESSION["username"]){
                        echo '<button class="editBtn" id="editBtn'.$bookingsArr[$i]["booking_id"].'" data-booking="'.$bookingsArr[$i]["booking_id"].'" data-room="'.$bookingsArr[$i]["room_id"].'">Rediger</button>';
                    }
                    echo "</div>";
                }
            ?>
        </div>
        <div id="editContainer">
            <div>
                <h2>Rediger booking <a href="#" class="closeBtn" id="closeEdit" onclick="closeEdit()">X</a></h2>
                <form action="" method="post" name="edit" id="edit">
                    <input type="hidden" name="form" value="edit">
                    <input type="hidden" name="booking_id" value="" id="editBookingId">
                    <label for="roomSelect">Rom:</label>
                    <select name="roomSelect" id="editRoomSelect" onchange="selectChange()">
                        <?php
                            for ($i=0; $i < count($roomsArr); $i++) {
                                echo '<option value="'.$roomsArr[$i]["room_id"].'">'.$roomsArr[$i]["room_name"].'</option>';
                            }
                        ?>
                    </select>
                    <input type="hidden" name="room" id="editRoomValue" value="1">
                    <label for="from">Fra:</label>
                    <input type="datetime-local" name="from" id="editFrom">
                    <label for="to">Til:</label>
                    <input type="datetime-local" name="to" id="editTo">
                    <input type="submit" value="Send endring">
                </form>
            </div>
        </div>
    </main>
    <script>
        let roomSelectEL = document.getElementById("roomSelect");
        let roomValueEL = document.getElementById("roomValue");
        let editRoomSelectEL = document.getElementById("editRoomSelect");
        let editRoomValueEL = document.getElementById("editRoomValue");

        let bookingsElArr = document.getElementsByClassName("booking");
        let bookingsInfoElArr = document.getElementsByClassName("bookingInfo");

        let editContainerEl = document.getElementById("editContainer");
        let editBtnElArr = document.getElementsByClassName("bookingInfo");
        let editBookingIdEl = document.getElementById("editBookingId");
        let closeEditEl = document.getElementById("closeEdit");

        let editFormEl = document.getElementById("edit");

        for (let i = 0; i < bookingsElArr.length; i++) {
            bookingsElArr[i].onclick = function (e){
                console.log(e.target.id + " | " + e.target);
                for (let j = 0; j < bookingsElArr.length; j++) {
                    bookingsElArr[j].style.stroke = "";
                    bookingsInfoElArr[j].style.display = "none";
                }
                e.target.style.stroke = "white"
                document.getElementById(e.target.id + "Info").style.display = "block";
            }
        }
        for (let i = 0; i < editBtnElArr.length; i++) {
            editBtnElArr[i].onclick = function (e){
                editBookingIdEl.value = parseInt(e.target.getAttribute("data-booking"));
                editRoomSelectEL.value = parseInt(e.target.getAttribute("data-room"));
                editContainerEl.style.display = "flex";
                console.log(e.target.getAttribute("data-booking"));
                console.log("Edit: " + editBookingIdEl.value  + ", " + editRoomSelectEL.value);
            }
        }
        editFormEl.onsubmit = function (e){
            selectChange()
            // e.preventDefault();
        }
        function closeEdit(){
            editContainerEl.style.display = "none";
            return false;
        }

        function selectChange(){
            roomValueEL.value = roomSelectEL.value;
            editRoomValueEL.value = editRoomSelectEL.value;
            console.log("Changed: " + roomValueEL.value + ", " + editRoomValueEL.value);
        }
        selectChange();
    </script>
</body>
</html>
<?php $con->close();?>
