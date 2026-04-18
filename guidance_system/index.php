<?php
include "connect.php";

if(isset($_POST['submit'])){

    $name = $_POST['student_name'];
    $id = $_POST['student_id'];
    $concern = $_POST['concern'];

    $sql = "INSERT INTO counseling_requests (student_name, student_id, concern)
            VALUES ('$name','$id','$concern')";

    $conn->query($sql);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Guidance Counseling System</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

<h1>Guidance Counseling Request</h1>

<form method="POST">

<input type="text" name="student_name" placeholder="Student Name" required>

<input type="text" name="student_id" placeholder="Student ID" required>

<textarea name="concern" placeholder="Enter your concern" required></textarea>

<button type="submit" name="submit">Submit Request</button>

</form>

<h2>Submitted Requests</h2>

<table>

<tr>
<th>Name</th>
<th>Student ID</th>
<th>Concern</th>
<th>Date</th>
</tr>

<?php

$result = $conn->query("SELECT * FROM counseling_requests");

while($row = $result->fetch_assoc()){
echo "<tr>
<td>".$row['student_name']."</td>
<td>".$row['student_id']."</td>
<td>".$row['concern']."</td>
<td>".$row['date_submitted']."</td>
</tr>";
}

?>

</table>

</div>

</body>
</html>