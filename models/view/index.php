<?php
	ini_set("allow_url_fopen", 1); //needed for Marketcheck API
    $model = "";
    $make = "";
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $model = $_POST["model"];
        $make = $_POST["make"];
    } else if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $make = $_GET["make"];
        $model = $_GET["model"];
    }
    $modelClean = str_replace(" ", "+", htmlentities($model));
    $makeClean = str_replace(" ", "+", htmlentities($make));
?>
<html>
  <head>
    <title><?php echo $make . " " . $model; ?></title>
    <link rel="stylesheet" href="../../styles.css"/>
  </head>
  <body>
  <div id="content"></div>
  <div class=mainWithSidebar>
<script>
function hndlr(response) {
	for (var i = 0; i < 1; i++) {
        var item = []
		item[i] = response.items[i];
		var model = <?php echo "\"$model\""; ?>;
		document.getElementById("content").innerHTML += "<form action='index.php' method='post' id='imageSearch'><input type='text' hidden name='make' value='<?php echo $make; ?>'/><input type='text' hidden name='model' value='" + model + "'/><input type='text' hidden name='imgUrl' value='" + item[i].link + "' /><input type='text' hidden name='useApi' value='yes'/></form>";
		document.getElementById("imageSearch").submit();
    }
}

    </script>
<?php
	require("../../config.php"); // DB connection credentials
    $results = null;
	try {
			$conn = new mysqli($dbHost, $dbRead, $dbReadPw, $dbSchema);
			$sql = "SELECT * FROM steve WHERE model = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s", $model);
			$stmt->execute();
			$result = $stmt->get_result();
			$results = $result->fetch_all(MYSQLI_ASSOC);
			$stmt->close();
			$conn->close();
			if (count($results) == 0) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if ($_POST["useApi"] == "yes") {
			$model = $_POST["model"];
			$imgUrl = $_POST["imgUrl"];
			$conn = new mysqli($dbHost, $dbAdmin, $dbAdminPw, $dbSchema);
			$sql = "INSERT INTO steve (model, url) VALUES (?, ?)";
			$stmt =$conn->prepare($sql);
			echo $conn->error;
			$stmt->bind_param("ss", $model, $imgUrl);
			$stmt->execute();
			$stmt->close();
			$conn->close();
			echo "<img class=carDisplay src='" . $imgUrl . "'/>";
        }
    } else {
                echo '<script src="https://www.googleapis.com/customsearch/v1?q=' . $makeClean . "+" . $modelClean . '&searchType=image&key=AIzaSyAFG_XFuzvrGSF_UML-V34t-UwNrYAngtI&cx=013204994027278291303:mhubrebrimn&callback=hndlr"></script>';
        }
			} else
			{
                $imgUrl = $results[0]["url"];
				echo "<img class=carDisplay src='" . $imgUrl . "'/>";
			}
	} catch (Exception $ex) {
		echo "Error loading image. Please contact our support team: 555-555-5555 with error code 3cars";
    }
    ///
    /// SIDE NAV
    ///
    try {
		$useDb = true; //initialize variables in this scope
		$results = null;
		try { // Check if current data is available from the database before trying API
			$conn = new mysqli($dbHost, $dbRead, $dbReadPw, $dbSchema);
			$sql = "SELECT * FROM tom_modelsbymake WHERE Make_Name = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s", $make);
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
                $url = "https://vpic.nhtsa.dot.gov/api/vehicles/getmodelsformake/$makeClean?format=json";
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
			echo "<a href=./?make=$makeClean&model=";
			if ($useDb) {
				echo str_replace(" ", "+", htmlentities($results[$i]["Model_Name"]));
                echo ">" . $results[$i]["Model_Name"];
			} else {
				echo str_replace(" ", "+", htmlentities($results[$i]->Model_Name));
                echo "\">" . $results[$i]->Model_Name;
			}
			echo "</a>";
	        }
	    echo "</div>";
    }
    catch (Exception $error) {
        echo "Error loading navigation. Please contact our support team: 555-555-5555 with error code 52cars";
    }
?>
		<?php
			$year = date("Y");
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => "http://api.marketcheck.com/v2/search/car/active?api_key=RKxVk0wx7MAmyMOao4WT2p74ajYVgIFg&year=$year&make=$makeClean&model=$modelClean",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 1,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
					"Host: marketcheck-prod.apigee.net"
				)
			));

			try {
				try {
					$useDb = true;
					$results = null;
					$conn = new mysqli($dbHost, $dbRead, $dbReadPw, $dbSchema);
					$sql = "SELECT * FROM dylan_db WHERE query = ?";
					$stmt = $conn->prepare($sql);
					$query = "$year $make $model";
					$stmt->bind_param("s", $query);
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
							if (date_diff($now, $time)->format('d') > 2) {
								$useDb = false;
								echo "DB outdated, falling back to API and populating for next time";
							}
						}
					}
					$conn->close();
				} catch (Exception $ex) {
					$useDb = false;
				}
				if ($useDb == false) { // Fallback to API if the data is outdated or the db is unavailable
					$raw = curl_exec($curl);
					$json = json_decode($raw);
					$results = $json->listings;
					$conn = new mysqli($dbHost, $dbAdmin, $dbAdminPw, $dbSchema);
					for ($i = 0; $i < count($results); $i++) {
						$sql = "REPLACE INTO dylan_db (query, heading, vin, miles, msrp) VALUES(?, ?, ?, ?, ?)";
						$stmt=$conn->prepare($sql) or die($conn->error); //*REMOVE ERROR OUTPUT FOR PRODUCTION
						$heading = $results[$i]->heading;
						$vin = $results[$i]->vin;
						property_exists($results[$i], "miles") ? $miles = $results[$i]->miles : $miles = NULL;
						property_exists($results[$i], "msrp") ? $msrp = $results[$i]->msrp : $msrp = NULL;
						$query = "$year $make $model";
						$stmt->bind_param("sssss", $query, $heading, $vin, $miles, $msrp);
						$stmt->execute();
						$stmt->close();
					}
					$conn->close();
				}
				echo "<p>Sales Listings for $query</p>";
				echo "<table><tr><th>Heading</th><th>VIN</th><th>Miles</th><th>MSRP</th></tr>"; // echo results
				for ($i = 0; $i < count($results); $i++) {
					echo "<tr>";
					if ($useDb) {
						echo "<td>" . $results[$i]["heading"] . "</td>";
						echo "<td>" . $results[$i]["vin"] . "</td>";
						echo $results[$i]["miles"] != NULL ? "<td>" . $results[$i]["miles"] . "</td>" : "<td>N/A</td>";
						echo $results[$i]["msrp"] != NULL ? "<td>" . $results[$i]["msrp"] . "</td>" : "<td>N/A</td>";
					} else {
						echo "<td>" . $results[$i]->heading . "</td>";
						echo "<td>" . $results[$i]->vin . "</td>";
						echo property_exists($results[$i], "miles") ? "<td>" . $results[$i]->miles . "</td>" : "<td>N/A</td>";
						echo property_exists($results[$i], "msrp") ? "<td>\$" . $results[$i]->msrp . "</td>" : "<td>N/A</td>";
					}
					echo "</tr>";
				}
				echo "</table>";
			} catch (Exception $ex) {
				echo "Error loading sale listings. Please contact our support team: 555-555-5555 with error code 78cars";
			}
		?>
    </div>
  </body>
</html>
