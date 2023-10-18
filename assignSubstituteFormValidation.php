<?php

include('sms.php');
session_start();

// Validate user input
if (empty($_SESSION['shown_id']) || empty($_POST['SB']) || empty($_POST['CN'])) {
    die("Invalid input data.");
}

$whose = $_SESSION['shown_id'];
$sub = $_POST['SB'];
$class = $_POST['CN'];

$days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday');
$day = $days[($class - 8) / 8];

$periods = array("period1", "period2", "period3", "period4", "period5", "period6");
$period = $periods[($class - 1) % 8];

// Establish a database connection
$connection = mysqli_connect("localhost", "root", "", "ttms");

// Check the database connection
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Validate user input against SQL injection
$whose = mysqli_real_escape_string($connection, $whose);
$sub = mysqli_real_escape_string($connection, $sub);

// Fetch teacher and subject information
$teacher_query = mysqli_query($connection, "SELECT * FROM teachers WHERE faculty_number = '$whose'");
if (!$teacher_query) {
    die("Teacher query failed: " . mysqli_error($connection));
}
$teacher_row = mysqli_fetch_assoc($teacher_query);

$whose_name = $teacher_row['name'];

$sub_query = mysqli_query($connection, "SELECT * FROM teachers WHERE faculty_number = '$sub'");
if (!$sub_query) {
    die("Subject query failed: " . mysqli_error($connection));
}
$sub_row = mysqli_fetch_assoc($sub_query);

$sub_name = $sub_row['name'];

// Query the subject's timetable for the specified day
$sub_timetable_query = mysqli_query($connection, "SELECT * FROM $sub WHERE day = '$day'");
if (!$sub_timetable_query) {
    die("Subject timetable query failed: " . mysqli_error($connection));
}
$sub_timetable_row = mysqli_fetch_assoc($sub_timetable_query);

$available = false;

if ($sub_timetable_row[$period] == "-<br>-" || $sub_timetable_row[$period] == "-<br>" || $sub_timetable_row[$period] == "-") {
    $message = 'Message Sent!';
} elseif (!empty($_POST['pwd'])) {
    echo "<script type='text/javascript'>alert('Selected substitute teacher is not available!');
        window.location.href = 'generatetimetable.php?display=$whose;</script>";
}

// Query the teacher's timetable for the specified day
$teacher_timetable_query = mysqli_query($connection, "SELECT * FROM $whose WHERE day = '$day'");
if (!$teacher_timetable_query) {
    die("Teacher timetable query failed: " . mysqli_error($connection));
}
$teacher_timetable_row = mysqli_fetch_assoc($teacher_timetable_query);

$pieces = explode("<br>", $teacher_timetable_row[$period]);

$string = "Hello $sub_name, You have to take class $pieces[0] of $whose_name in $pieces[1]\n\n-Sent from TimeTable Management System AMU";
$_SESSION['s'] = $string;

echo 'Sending SMS...';

if (!empty($_POST['pwd'])) {
    echo "<script type='text/javascript'>alert('Message Sent!');
        window.location.href = 'generatetimetable.php?display=$whose';</script>";
}

// Close the database connection
mysqli_close($connection);
?>
<div class="content">
    <form method="post" id="smsform">
        <input type="hidden" name="uid" value="sender mobile number goes here"/>
        <input type="hidden" name="pwd" value="way2sms password goes here"/>
        <input type="hidden" name="to" value="recipient mobile number goes here"/>
        <input type="hidden" name="msg" value="<?php echo $_SESSION['s'] ?>"/>
        <input type="hidden" value="Send SmS" id="send"/>
    </form>
</div>
<script>
    var send = document.getElementById('smsform');
    send.submit();
</script>