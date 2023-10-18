<?php

/**Class to store subject details**/
class Subject
{
    public $code; //Subject code
    public $classes = 0; //No. of classes
    public $semester; //semester of subject
    public $alias; //alias for subject teacher
    public $alias2; //alias for subject teacher2 (only for practical subjects)
    public $alias3; //alias for subject teacher3 (only for practical subjects)
    public $subjectteacher; //faculty number of teacher1
    public $subjectteacher2; //faculty number of teacher2
    public $subjectteacher3; //faculty number of teacher3
}

/**Class to store teachers details**/
class Teacher
{
    public $id; //faculty number
    public $days = array(); //schedule
    public $classroom_names = array(); //classroom names
}

$subjectslots = array(); //subjects slots for all semesters
$aliasslots = array(); //alias slots corresponding to each subject

$query = mysqli_query(mysqli_connect("localhost", "root", "", "ttms"), "SELECT * FROM subjects ");
$subjects[] = new Subject(); //to store theory subjects
$practicals[] = new Subject(); //to store practical subjects

$count = 0;

/** fetching theory subjects and saving in subjects array*/
while ($row = mysqli_fetch_assoc($query)) {
    if ($row['course_type'] == 'LAB') {
        continue;
    }
    $temp = new Subject();
    $temp->code = $row['subject_code'];
    $temp->semester = $row['semester'];
    
    if (isset($row['allotedto'])) {
        $temp->subjectteacher = $row['allotedto'];
        $teacheralias_query = mysqli_query(mysqli_connect("localhost", "root", "", "ttms"),
            "SELECT * FROM teachers WHERE faculty_number='$temp->subjectteacher'");
        if ($teacheralias_query && $teacher_row = mysqli_fetch_assoc($teacheralias_query)) {
            $temp->alias = $teacher_row['alias'];
        }
    }
    
    $subjects[$count++] = $temp;
}

$subjects_count = $count;
/**Fetching teachers and saving into teachers array*/
$query = mysqli_query(mysqli_connect("localhost", "root", "", "ttms"), "SELECT * FROM teachers ");

$teachers[] = new Teacher();
$count = 0;
while ($row = mysqli_fetch_assoc($query)) {
    $temp = new Teacher();
    $temp->id = $row['faculty_number'];
    $teachers[$count++] = $temp;
}
$teachers_count = $count;
$r = -1;

/** Genrating timetable for theory courses, with maximum class for each subject equal to 4 */
for ($I = 0; $I < $subjects_count * 4; $I++) {
    $i = $I % $subjects_count;
    $sem = $subjects[$i]->semester;
    $year = ($sem + 1) / 2;
    $classroom_query = mysqli_query(mysqli_connect("localhost", "root", "", "ttms"),
        "SELECT name FROM classrooms WHERE status='$year'");
    $row = mysqli_fetch_assoc($classroom_query);
    // $classroom = $row['name'];
    for ($j = 0; $j < 30; $j++) {
        $subject_teacher;
        if ($j % 6 == 0) {
            if ($j == 18) {
                // Skip launch hour (11:10 to 11:40)
                $r++;
            }
            $r++;
        }
        for ($z = 0; $z < $count; $z++) {
            if ($teachers[$z]->id == $subjects[$i]->subjectteacher) {
                $tindex = $z;
                break;
            }
        }
        if ($j % 6 == 0)
            $r++;
        if (isset($subjectslots[$sem][$r % 6][$j % 5])) {
            //check if subjectslot is empty
            continue;
        } else if (isset($teachers[$tindex]->days[$sem % 2][$r % 6][$j % 5])) {
            //check if subject teacher is free
            continue;
        } else {
            //check if existing in same day
            $already = false;
            for ($z = 0; $z < 5; $z++) {
                if (isset($subjectslots[$sem][$r % 6][$z])) {
                    if ($z == ($j % 5)) {
                        continue;
                    }
                    if ($subjectslots[$sem][$r % 6][$z] == $subjects[$i]->code) {
                        $already = true;
                    }
                }
            }
            if ($already) {
                continue;
            }
            // set subject
            $subjects[$i]->classes++;
            $subjectslots[$sem][$r % 6][$j % 5] = $subjects[$i]->code;
            $aliasslots[$sem][$r % 6][$j % 5][0] = $subjects[$i]->alias;
            $teachers[$tindex]->days[$sem % 2][$r % 6][$j % 5] = $subjects[$i]->code;
            // $teachers[$tindex]->classroom_names[$sem % 2][$r % 6][$j % 5] = $classroom;
            break;
        }
    }
}
/**********************check for empty slots in semester's timetable*******************************/
for ($i = 1; $i <= 8; $i++) {
    for ($k = 0; $k < 6; $k++) {
        for ($j = 0; $j < 5; $j++) {

            if (isset($subjectslots[$i][$k][$j % 5])) {
            } else {
                $subjectslots[$i][$k][$j % 5] = "-";
                $aliasslots[$i][$k][$j % 5][0] = "-";
            }

        }
    }
}
/**********************check for empty slots in teacher's timetable*******************************/
for ($i = 0; $i < $count; $i++) {
    for ($k = 0; $k < 6; $k++) {
        for ($j = 0; $j < 5; $j++) {

            if (isset($teachers[$i]->days[1][$k][$j])) {
            } else {
                $teachers[$i]->days[1][$k][$j] = "-";
                $teachers[$i]->classroom_names[1][$k][$j] = "-";
            }
        }
    }
}
// Fetch information about practical courses from the database for all 8 semesters
// $query = mysqli_query($connection, "SELECT * FROM subjects");
$count = 0;

while ($row = mysqli_fetch_assoc($query)) {
    if ($row['course_type'] != 'LAB') {
        continue;
    }

    $temp = new Subject();
    $temp->code = $row['subject_code'];
    $temp->semester = $row['semester'];
    $temp->subjectteacher = $row['allotedto'];
    $temp->subjectteacher2 = $row['allotedto2'];
    $temp->subjectteacher3 = $row['allotedto3'];

    // Fetch teacher aliases
    $teacheralias_query = mysqli_query($connection, "SELECT * FROM teachers WHERE faculty_number='$temp->subjectteacher'");
    $row = mysqli_fetch_assoc($teacheralias_query);
    $temp->alias = $row['alias'];

    $teacheralias_query = mysqli_query($connection, "SELECT * FROM teachers WHERE faculty_number='$temp->subjectteacher2'");
    $row = mysqli_fetch_assoc($teacheralias_query);
    $temp->alias2 = $row['alias'];

    $teacheralias_query = mysqli_query($connection, "SELECT * FROM teachers WHERE faculty_number='$temp->subjectteacher3'");
    $row = mysqli_fetch_assoc($teacheralias_query);
    $temp->alias3 = $row['alias'];

    $practicals[$count++] = $temp;
}

// Calculate the number of slots required for all 8 semesters
$total_slots = $count * 2 * 8; // Assuming two slots per practical course per semester

// Assign practical courses to slots for all 8 semesters
for ($I = 0; $I < $total_slots; $I++) {
    $i = $I % $count;
    $sem = $practicals[$i]->semester;

    // Check teacher availability
    $tindex = -1;
    $tindex2 = -1;
    $tindex3 = -1;

    for ($z = 0; $z < count($teachers); $z++) {
        if (isset($practicals[$i]->subjectteacher) && $teachers[$z]->id == $practicals[$i]->subjectteacher) {
            $tindex = $z;
        }
        if (isset($practicals[$i]->subjectteacher2) && $teachers[$z]->id == $practicals[$i]->subjectteacher2) {
            $tindex2 = $z;
        }
        if (isset($practicals[$i]->subjectteacher3) && $teachers[$z]->id == $practicals[$i]->subjectteacher3) {
            $tindex3 = $z;
        }
    }

    // Check for available slots and assign the practical course
    $slot_found = false;

    for ($j = 0; $j < 6; $j++) {
        if ($sem == 4 && $j == 2) {
            // Skip launch hour (11:10 to 11:40) in semester 4
            continue;
        }

        if (!isset($subjectslots[$sem][$j][5]) &&
            (!isset($teachers[$tindex]->days[1][$j][5]) ||
            !isset($teachers[$tindex2]->days[1][$j][5]) ||
            !isset($teachers[$tindex3]->days[1][$j][5]))
        ) {
            $practicals[$i]->classes++;
            $subjectslots[$sem][$j][5] = $practicals[$i]->code;
            $aliasslots[$sem][$j][5][0] = $practicals[$i]->alias;
            $aliasslots[$sem][$j][5][1] = $practicals[$i]->alias2;
            $aliasslots[$sem][$j][5][2] = $practicals[$i]->alias3;
            $teachers[$tindex]->days[1][$j][5] = $practicals[$i]->code;
            $teachers[$tindex2]->days[1][$j][5] = $practicals[$i]->code;
            $teachers[$tindex3]->days[1][$j][5] = $practicals[$i]->code;
            $slot_found = true;
            break;
        }
    }

    if (!$slot_found) {
        // Handle cases where there are no available slots
        // You may want to implement error handling or alternative logic here
    }
}

// Close the database connection
// mysqli_close($connection);

// Redirect to a success page
header("Location: generatetimetable.php?success=true");



/**checks for empty slot**/
for ($i = 1; $i <=8; $i ++) {
    for ($j = 0; $j < 6; $j++) {
        if (isset($subjectslots[$i][$j][5])) {}
        else {
            $subjectslots[$i][$j][5] = '-';
            $aliasslots[$i][$j][5][0] = '-';
            $aliasslots[$i][$j][5][1] = '-';
            $aliasslots[$i][$j][5][2] = '-';
        }
    }
}

for ($i = 0; $i < $teachers_count; $i++) {
    for ($k = 0; $k < 6; $k++) {

        if (isset($teachers[$i]->days[1][$k][5])) {}
        else {
            $teachers[$i]->days[1][$k][5] = "-";
        }
    }
}


/******Saving semesters timetable into database*****/
$days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday');
for ($i = 1; $i <=8; $i ++) {
    $database_name = " semester" . $i . " ";
    for ($j = 0; $j < 6; $j++) {
        if ($j == 3) {
            // Set launch hour (11:10 to 11:40)
            $period6 = "Launch";
        } else {
            $period6 = $subjectslots[$i][$j][5] . "<br>" . $aliasslots[$i][$j][5][0] . ", " . $aliasslots[$i][$j][5][1] . ", " . $aliasslots[$i][$j][5][2];
        }


        $query = "UPDATE" . $database_name . " SET  period1= '" . $subjectslots[$i][$j][0] . "<br>" . $aliasslots[$i][$j][0][0] . "',
period2='" . $subjectslots[$i][$j][1] . "<br>" . $aliasslots[$i][$j][1][0] . "', 
period3='" . $subjectslots[$i][$j][2] . "<br>" . $aliasslots[$i][$j][2][0] . "',
period4='" . $subjectslots[$i][$j][3] . "<br>" . $aliasslots[$i][$j][3][0] . "', 
period5='" . $subjectslots[$i][$j][4] . "<br>" . $aliasslots[$i][$j][4][0] . "',
period6='" . $subjectslots[$i][$j][5] . "<br>" . $aliasslots[$i][$j][5][0] . ", " . $aliasslots[$i][$j][5][1] . ", " . $aliasslots[$i][$j][5][2] . "'
 WHERE day='" . $days[$j] . "' ";
        $q = mysqli_query(mysqli_connect("localhost", "root", "", "ttms"), $query);
    }

}
/******Saving teachers timetable into database*****/
for ($i = 0; $i < $teachers_count; $i++) {
    $database_name = " " . strtolower($teachers[$i]->id) . " ";
    for ($j = 0; $j < 6; $j++) {
        $query = "UPDATE" . $database_name . " SET  period1= '" . $teachers[$i]->days[1][$j][0] . "<br>" . $teachers[$i]->classroom_names[1][$j][0] . "',
period2='" . $teachers[$i]->days[1][$j][1] . "<br>" . $teachers[$i]->classroom_names[1][$j][1] . "', 
period3='" . $teachers[$i]->days[1][$j][2] . "<br>" . $teachers[$i]->classroom_names[1][$j][2] . "',
period4='" . $teachers[$i]->days[1][$j][3] . "<br>" . $teachers[$i]->classroom_names[1][$j][3] . "', 
period5='" . $teachers[$i]->days[1][$j][4] . "<br>" . $teachers[$i]->classroom_names[1][$j][4] . "',
period6='" . $teachers[$i]->days[1][$j][5] . "'
 WHERE day='" . $days[$j] . "' ";
        $q = mysqli_query(mysqli_connect("localhost", "root", "", "ttms"), $query);
    }
}

/******redirect back to generate timetable **/
header("Location:generatetimetable.php?success=true");

?>