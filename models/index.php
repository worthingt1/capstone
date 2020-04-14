<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" href="../styles.css"/>
</head>
<body>
<div class="mainWithSidebar">
<?php
ini_set('display_errors', 1); //*REMOVE FOR PRODUCTION
ini_set('display_startup_errors', 1); //*REMOVE FOR PRODUCTION
error_reporting(E_ALL); //*REMOVE FOR PRODUCTION
	require("../config.php"); // DB connection credentials
	ini_set("allow_url_fopen", 1); //needed to load json API url
	$make = str_replace(" ", "%20", htmlentities($_GET["make"])); //To be replaced with a POST to load a make
	$makeClean = $_GET["make"];
	$url = "https://vpic.nhtsa.dot.gov/api/vehicles/getmodelsformake/$make?format=json";
	try {
		$useDb = true; //initialize variables in this scope
		$results = null;
		try { // Check if current data is available from the database before trying API
			$conn = new mysqli($dbHost, $dbRead, $dbReadPw, $dbSchema);
			$sql = "SELECT * FROM tom_modelsbymake WHERE Make_Name = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s", $makeClean);
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
				$sql = "REPLACE INTO tom_modelsbymake (Make_ID, Make_Name, Model_ID, Model_Name) VALUES(?, ?, ?, ?)";
				$stmt=$conn->prepare($sql) or die("Error loading info. Please contact our support team: 555-555-5555 with error code 823cars");
				$makeId = $results[$i]->Make_ID;
				$makeName = $results[$i]->Make_Name;
				$modelId = $results[$i]->Model_ID;
				$modelName = $results[$i]->Model_Name;
				$stmt->bind_param("ssss", $makeId, $makeName, $modelId, $modelName);
				$stmt->execute();
				$stmt->close();
			}
			$conn->close();
		}
	        ?>
		<div class="sidenav">
			<div class="sidenavTop"><a href="/">&larr; Makes</a></div>
			<div class="sideHeader">Models</div>
			<?php
	        for ($i = 0; $i < count($results); $i++) {
			echo "<a href=view/?make=$make&model=";
			if ($useDb) {
				echo str_replace(" ", "+", htmlentities($results[$i]["Model_Name"]));
                echo ">" . $results[$i]["Model_Name"];
			} else {
				echo str_replace(" ", "+", htmlentities($results[$i]->Model_Name));
                echo ">" . $results[$i]->Model_Name;
			}
			echo "</a>";
	        }
	    echo "</div>";
    }
    catch (Exception $error) {
        echo "Error loading navigation. Please contact our support team: 555-555-5555 with error code 52cars";
    }
?>
</div>
</body>
</html>