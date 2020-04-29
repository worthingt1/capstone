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
		<div class=logoContainer>
			<a class=logo href="/">CarHub</a>
		</div>
		<a href="./models/?make=Mazda"><img class=makeLogos src="img/mazda.png"></a>
		<a href="./models/?make=BMW"><img class=makeLogos src="img/bmw.png"></a>
        <p><a href="makes" class="button">ALL MAKES</a></p>
    </div>
    <?php
	require("config.php"); // DB connection credentials
    ini_set("allow_url_fopen", 1); //needed to load json API url
	$makes = [452, 444, 445, 440, 441, 442, 443, 448, 449, 456, 460, 464, 465, 466, 467, 468, 469, 472, 473, 474, 475, 476, 477, 478, 480, 481, 482, 483, 485, 493, 498, 499, 502, 515, 523, 536];
    $cnt = count($makes);
    $qs = implode(',', array_fill(0, $cnt, '?'));
    $bind = str_repeat('i', $cnt);
	$url = "https://vpic.nhtsa.dot.gov/api/vehicles/getallmakes?format=json";
	try {
		$useDb = true; //initialize variables in this scope
		$results = null;
		try { // Check if current data is available from the database before trying API
			$conn = new mysqli($dbHost, $dbRead, $dbReadPw, $dbSchema);
			$sql = "SELECT * FROM tom_makes WHERE Make_ID IN ($qs) ORDER BY Make_Name";
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
				if (in_array(intval($results[$i]->Make_ID), $makes)) {
					$sql = "REPLACE INTO tom_makes (Make_ID, Make_Name) VALUES(?, ?)";
					$stmt=$conn->prepare($sql) or die("Error loading info. Please contact our support team: 555-555-5555 with error code 420cars");
					$makeId = $results[$i]->Make_ID;
					$makeName = $results[$i]->Make_Name;
					$stmt->bind_param("ss", $makeId, $makeName);
					$stmt->execute();
					$stmt->close();
				}
			}
			$conn->close();
		}
		///sorting alphabetically if using API
		if (!$useDb) {
			function compare($s1, $s2) {
				return strcmp($s1->Make_Name, $s2->Make_Name);
			}
			usort($results, "compare");
		}
		///
		echo "<div class='sidenav'><div class='sideHeader'>Makes</div>";
		for ($i = 0; $i < count($results); $i++) {
			//do not display if not on makes list
			if (!$useDb && !in_array(intval($results[$i]->Make_ID), $makes)) {
				continue;
			} else {
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
		}
		echo "</div>";
    }
    catch (Exception $error) {
        echo "Error loading navigation. Please contact our support team: 555-555-5555 with error code 61cars";
    }
?>
</body>
</html>