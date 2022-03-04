<?php
    // Setting that phpRepo is being imported and importing it
    define("IS_INCLUDED", TRUE);
    include 'phpRepo.php';
    $con = connect();

    // User is logged in, not? Redirect to login
    if (!is_logedin($con)){
        header('Location: login.php');
    }

    // Declaring output message (Handy for debugging/info)
    $message = "";

    // The magic is in the post
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // checking if all data was sent or if it is a edit request
        if ((isset($_POST['room']) && isset($_POST['from']) && isset($_POST['to'])) || $_POST["form"] == 'edit'){
            $room = null;
            $from = 0;
            $to = 0;
            
            // If the request is to make a booking the data will be collected, else if it edit, missing data will be infered from existing
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

            // Creating PHP-date from html text-date
            $from = strtotime($from);
            $to = strtotime($to);

            // Format PHP-date for mysql
            $fromFormat = date('Y-m-d H:i:s', $from);
            $toFormat = date('Y-m-d H:i:s', $to);

            // Basic check if the time from is before time to. Handy for not creating black holes.
            // System is not grate at handeling timedifferences of a day or more: it's a feature
            if ($from < $to){
                $datediff = ($to - $from) / (60 * 60 * 24);

                // Settingbooking ID to 0 if not set in post data (edit sends id)
                $bookingId = 0;
                if (isset($_POST["booking_id"])){
                    $bookingId = $_POST["booking_id"];
                }
                // I used more time debugging this than i care to admit
                $stmt = $con->prepare('SELECT * FROM booking WHERE room_id = ? AND booking_id != ? AND ((? BETWEEN time_from AND time_to) or (time_from BETWEEN ? AND ?) or (time_from = ?))');
                $stmt->bind_param('iissss', $room, $bookingId, $fromFormat, $fromFormat, $toFormat, $fromFormat);
                $stmt->execute();

                $conflictions = $stmt->get_result()->num_rows;

                // If no conflicts, find what request was sent, and sort (all previous code would be the same on both, so put this as long back i could)
                if ($conflictions == 0){
                    if ($datediff < 1){
                        switch ($_POST["form"]) {
                            case 'make':
                                $color = rand_color();
                                // Basic SQL-Proof statement, with bind_param (Serriously love this (Battletested AF BTW))
                                $stmt = $con->prepare('INSERT INTO booking (room_id, user_id, color, time_from, time_to) VALUES(?,?,?,?,?)');
                                $stmt->bind_param('iisss', $room, $_SESSION["user_id"], $color, $fromFormat, $toFormat); // The funky letters are for type specification
                                $stmt->execute();
                                $message = "Bookingen ble laget.";
                                break;
                            case 'edit':
                                // You get the point
                                $stmt = $con->prepare('UPDATE booking SET room_id = ?, time_from = ?, time_to = ? WHERE booking_id = ?');
                                $stmt->bind_param('issi', $room, $fromFormat, $toFormat, $bookingId);
                                $stmt->execute();
                                $message = "Bookingen ble redigert.";
                                break;
                        }
                        
                        // So sorry, this down here is just what happens when you need different messages
                    }else{
                        $message = "Det er en for lang periode.";
                    }     
                }else{
                    $message = "Den tiden er opptatt.";
                }
            }else{
                $message = "Fra er etter til.";
            }
        }else if($_POST["form"] == 'remove' && isset($_POST["booking_id"])){

            // Finding out if the booking exists and is made by the signed inn user
            $stmt = $con->prepare('SELECT * FROM booking where user_id = ? and booking_id = ?');
            $stmt->bind_param('ii', $_SESSION["user_id"], $_POST["booking_id"]); // 's' specifies the variable type => 'string'       (<- I didn't write this btw -Peder)
            $stmt->execute();
            $bookingsFound = $stmt->get_result()->num_rows;

            // if found, delete it
            if($bookingsFound == 1){
                $stmt = $con->prepare('DELETE FROM booking where user_id = ? and booking_id = ?');
                $stmt->bind_param('ii', $_SESSION["user_id"], $_POST["booking_id"]); // 's' specifies the variable type => 'string'       (<- I didn't write this btw -Peder)
                $stmt->execute();
                $message = "Bookingen ble slettet.";
            }else{
                $message = "Du har bare lov å slette dine egne bookinger.";
            }
        }else{
            $message = "Ikke nok felter ble sendt med.";
        }
    }

    // Devlaring timeperiod to search inn (Default = current date -> tomorrow date)
    $search_from = null;
    $search_to = null;

    // If getdata date is sendt find that and add a day with strtotime (<- favourite PHP function <3), else get current date and tomorrow
    if (isset($_GET["date"])){
        $search_from = strtotime($_GET["date"]);
        $search_to = strtotime('+1 day', $search_from);
    }else{
        $search_from = strtotime('today midnight');
        $search_to = strtotime('tomorrow midnight');
    }

    // Formating as mysql readable
    $searchDate_from = date('Y-m-d H:i:s', $search_from);
    $searchDate_to = date('Y-m-d H:i:s', $search_to);


    // Get all the rooms available for booking, and put them in ann array for safekeeping
    $stmt = $con->prepare('SELECT * FROM room');
    $stmt->execute();
    $rooms = $stmt->get_result();

    $roomsArr = $rooms->fetch_all($mode = MYSQLI_BOTH);

    // Get all bookings in the current range
    $stmt = $con->prepare('SELECT * FROM booking left join users using(user_id) WHERE (time_from BETWEEN ? AND ?) OR (time_to BETWEEN ? AND ?)');
    $stmt->bind_param('ssss', $searchDate_from, $searchDate_to, $searchDate_from, $searchDate_to); // 's' specifies the variable type => 'string'       (<- I didn't write this btw -Peder)
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
        <?php
            echo "<p>".date("j. M, Y", $search_from)."</p>";
        ?>
    </header>
    <main>
        <button id="newBookingBtn" onclick="newBookingClick();">Ny booking</button>
        <div id="newBookingContainer" class="floatingForm">
            <div class="floatingFormContent">
                <h2>Ny booking <a href="#" class="closeBtn" onclick="newBookingClick();">X</a></h2>
                <form name="newBooking" id="newBooking" method="post">
                    <input type="hidden" name="form" value="make">
                    <label for="roomSelect">Jeg vil booke:</label><br>
                    <select name="roomSelect" id="roomSelect" onchange="selectChange()">
                        <?php
                            for ($i=0; $i < count($roomsArr); $i++) { 
                                echo '<option value="'.$roomsArr[$i]["room_id"].'">'.$roomsArr[$i]["room_name"].'</option>';
                            }
                        ?>
                    </select><br>
                    <input type="hidden" name="room" id="roomValue" value="1">
                    <label for="from">Jeg vil booke fra:</label><br>
                    <input type="datetime-local" name="from" id="from" value=<?php echo '"'.date("Y-m-d", $search_from).'T00:00"';?>><br>

                    <label for="to">Jeg vil booke til:</label><br>
                    <input type="datetime-local" name="to" id="to" value=<?php echo '"'.date("Y-m-d", $search_from).'T00:00"';?>><br>
                    <div><input type="submit" value="Send bookingen"></div>
                </form>
            </div>
        </div>
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

                    echo '<rect id="'.$bookingsArr[$i]["booking_id"].'" class="booking" x="'.$x.'%" y="'.$y.'" width="'.$width.'%" height="100" fill="'.$bookingsArr[$i]["color"].'"/>';
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
        <div id="displayTimeChange">
            <button id="minus1Day" onclick="dateBtn(false);"><</button>
            <form action="" method="get">
                <input type="date" name="date" id="date" onInput="this.form.submit();">
            </form>
            <button id="plus1Day" onclick="dateBtn(true);">></button>
        </div>
        <div id="bookingInfo">
            <?php
                for ($i=0; $i < count($bookingsArr); $i++) {
                    echo '<div class="bookingInfo" id="'.$bookingsArr[$i]["booking_id"].'Info" ><p>'.
                    'Booket fra '.date("H:i:s" ,strtotime($bookingsArr[$i]["time_from"])).
                    ', og til '.date("H:i:s" ,strtotime($bookingsArr[$i]["time_to"])).
                    '. Rommet er booket av '.$bookingsArr[$i]["name"]." (@".$bookingsArr[$i]["username"].')</p>';
                    if ($bookingsArr[$i]["username"] == $_SESSION["username"]){
                        echo '<button class="editBtn" id="editBtn'.$bookingsArr[$i]["booking_id"].'" data-booking="'.$bookingsArr[$i]["booking_id"].'" data-room="'.$bookingsArr[$i]["room_id"].'">Rediger</button>';
                        echo '<form action="" method="post" name="removeBooking'.$bookingsArr[$i]["booking_id"].'" id="removeBooking'.$bookingsArr[$i]["booking_id"].'">
                            <input type="hidden" name="form" value="remove">
                            <input type="hidden" name="booking_id" value="'.$bookingsArr[$i]["booking_id"].'">
                            <input type="submit" class="fjernBookingSubmitt" value="Fjern booking">
                        </form>';
                    }
                    echo "</div>";
                }
            ?>
        </div>
        <div id="editContainer" class="floatingForm">
            <div class="floatingFormContent">
                <h2>Rediger booking <a href="#" class="closeBtn" id="closeEdit" onclick="closeEdit()">X</a></h2>
                <form action="" method="post" name="edit" id="edit">
                    <input type="hidden" name="form" value="edit">
                    <input type="hidden" name="booking_id" value="" id="editBookingId">

                    <label for="roomSelect">Rom:</label><br>
                    <select name="roomSelect" id="editRoomSelect" onchange="selectChange()">
                        <?php
                            for ($i=0; $i < count($roomsArr); $i++) {
                                echo '<option value="'.$roomsArr[$i]["room_id"].'">'.$roomsArr[$i]["room_name"].'</option>';
                            }
                        ?>
                    </select><input type="hidden" name="room" id="editRoomValue" value="1"><br>

                    <label for="from">Fra:</label><br>
                    <input type="datetime-local" name="from" id="editFrom"><br>

                    <label for="to">Til:</label><br>
                    <input type="datetime-local" name="to" id="editTo"><br>
                    <div><input type="submit" value="Send endring"></div>
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
        let editBtnElArr = document.getElementsByClassName("editBtn");
        let editBookingIdEl = document.getElementById("editBookingId");

        let newBookingContainerEl = document.getElementById("newBookingContainer");

        let editFormEl = document.getElementById("edit");

        for (let i = 0; i < bookingsElArr.length; i++) {
            bookingsElArr[i].onclick = function (e){
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
            }
        }
        function closeEdit(){
            editContainerEl.style.display = "";
            return false;
        }

        function newBookingClick(){
            newBookingContainerEl.style.display = (newBookingContainerEl.style.display == "flex"? "":"flex") ;
        }

        function selectChange(){
            roomValueEL.value = roomSelectEL.value;
            editRoomValueEL.value = editRoomSelectEL.value;
        }
        editFormEl.onsubmit = selectChange;
        selectChange();

        window.onclick = function(e){
            if (e.target.nodeName == "MAIN" || e.target.nodeName == "HEADER" || e.target.nodeName == "HTML" || e.target.nodeName == "BODY"){
                for (let i = 0; i < bookingsElArr.length; i++) {
                    bookingsElArr[i].style.stroke = "";
                    bookingsInfoElArr[i].style.display = "none";
                }
            }
        }

        function dateBtn(forward){
            let path = location.href.split("?");
            let currentTimestamp = new Date(<?php echo '"'.date("Y-m-d", $search_from).'"';?>);
            currentTimestamp.setDate(currentTimestamp.getDate() + (forward? 1:-1));
            location.href = path[0] + "?date=" + currentTimestamp.toISOString().split('T')[0];
        }
    </script>
</body>
</html>
<?php $con->close();?>
