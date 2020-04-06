<?php
ini_set('display_errors', 1); //*REMOVE FOR PRODUCTION
ini_set('display_startup_errors', 1); //*REMOVE FOR PRODUCTION
error_reporting(E_ALL); //*REMOVE FOR PRODUCTION
	require("../config.php"); // DB connection credentials
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
			//var_dump($results); //Dump SQL contents for testing
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
		echo "Using API? ";
		echo $useDb ? "No. DB has current information." : "Yes. Updated DB for next use."; //*to be removed for production, showing if api is being used or not
	        echo "<table><tr><th>Make</th></tr>"; // echo results
	        for ($i = 0; $i < count($results); $i++) {
			echo "<tr><td><a href=../models/?make=";
			if ($useDb) {
                echo str_replace(" ", "+", htmlentities($results[$i]["Make_Name"]));
                echo ">" . $results[$i]["Make_Name"];
			} else {
                echo str_replace(" ", "+", htmlentities($results[$i]->Make_Name));
                echo "\">" . $results[$i]->Make_Name;
			}
			echo "</a></td></tr>";
	        }
	        echo "</table>";
    }
    catch (Exception $error) {
        echo $error->getMessage(); //*REMOVE FOR PRODUCTION AND REPLACE WITH SERVICE UNAVAIL. MSG
    }
    //API Source: https://vpic.nhtsa.dot.gov/api/
?>