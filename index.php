<!DOCTYPE HTML>
<html>
<head>
    <title>Car For Sale Near You</title>
    <link rel="stylesheet" href="styles.css"/>
</head>
<body>
    <div class="main">
        <h1>Cars for Sale in Your Local Area</h1>
        <marquee><h2 class="flash">Car Salesmen HATE this!!!</h2></marquee>
        <p><a href="makes" class="button">MAKES</a></p>
    </div>
    <div class="listings">
        <h1 class="listHeader">Listings Near Rowan</h2>
    </div>
    <?php
ini_set('display_errors', 1); //*REMOVE FOR PRODUCTION
ini_set('display_startup_errors', 1); //*REMOVE FOR PRODUCTION
error_reporting(E_ALL); //*REMOVE FOR PRODUCTION
	require("config.php"); // DB connection credentials
    ini_set("allow_url_fopen", 1); //needed to load json API url
    $makes = [444, 445, 440, 441, 442, 443, 448, 449, 456, 460, 464, 465, 466, 467, 468, 469, 472, 473, 474, 475, 476, 477, 478, 480, 481, 482, 483, 485, 493, 498, 499, 502, 515, 523, 536];
    $cnt = count($makes);
    $qs = implode(',', array_fill(0, $cnt, '?'));
    $bind = str_repeat('i', $cnt);
	$url = "https://vpic.nhtsa.dot.gov/api/vehicles/getallmakes?format=json";
	try {
		$useDb = true; //initialize variables in this scope
		$results = null;
		try { // Check if current data is available from the database before trying API
			$conn = new mysqli($dbHost, $dbRead, $dbReadPw, $dbSchema);
			$sql = "SELECT * FROM tom_makes WHERE Make_ID IN ($qs)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($bind, ...$makes);
			$stmt->execute();
			$result = $stmt->get_result();
			$results = $result->fetch_all(MYSQLI_ASSOC);
			$stmt->close();
			if (count($results) == 0) { // check if no results are found so we can fallback to api
				$useDb = false;
			} else {
				foreach ($results as $row) {
					$time = DateTime::createFromFormat ("Y-m-d H:i:s", $row["timestamp"]); // check to see if information in DB is current enough, otherwise use API
					$now = new DateTime();
					//echo $time->format("d-m-Y"); /*to be removed, debug
					if (date_diff($now, $time)->format('d') > 5) {
						$useDb = false;
						echo "DB outdated, falling back to API and populating for next time";
					}
				}
			}
			$conn->close();
		} catch (Exception $error) {
			$useDb = false;
		}
		if ($useDb == false) { // Fallback to API if the data is outdated or the db is unavailable
            $raw = file_get_contents("$url");
            $json = json_decode($raw);
			$results = $json->Results;
			$conn = new mysqli($dbHost, $dbAdmin, $dbAdminPw, $dbSchema);
			for ($i = 0; $i < count($results); $i++) {
				$sql = "REPLACE INTO tom_makes (Make_ID, Make_Name) VALUES(?, ?) WHERE Make_ID IN ($qs)";
				$stmt=$conn->prepare($sql) or die($conn->error); //*REMOVE ERROR OUTPUT FOR PRODUCTION
				$makeId = $results[$i]->Make_ID;
				$makeName = $results[$i]->Make_Name;
				$stmt->bind_param("ss" . $bind, $makeId, $makeName, ...$makes);
				$stmt->execute();
				$stmt->close();
			}
			$conn->close();
		}
	        echo "<div class='sidenav'><div class='sideHeader'>Makes</div>";
	        for ($i = 0; $i < count($results); $i++) {
			echo "<a href=../models/?make=";
			if ($useDb) {
                echo str_replace(" ", "+", htmlentities($results[$i]["Make_Name"]));
                echo ">" . $results[$i]["Make_Name"];
			} else {
                echo str_replace(" ", "+", htmlentities($results[$i]->Make_Name));
                echo "\">" . $results[$i]->Make_Name;
			}
			echo "</a>";
	        }
	        echo "</div>";
    }
    catch (Exception $error) {
        echo "Error loading navigation. Please contact our support team: 555-555-5555 with error code 61";
    }
?>
</body>
</html>