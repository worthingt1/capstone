<?php
    require("../../config.php"); // DB connection credentials
	ini_set("allow_url_fopen", 1); //needed for Marketcheck API
    $model = "";
    $make = "";
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $model = $_POST["model"];
        $make = $_POST["make"];
        $year = $_POST["year"];
    } else if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $make = $_GET["make"];
        $model = $_GET["model"];
        $year = $_GET["year"];
    }
    $modelClean = str_replace(" ", "+", htmlentities($model));
    $makeClean = str_replace(" ", "+", htmlentities($make));
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
        if (!property_exists($json, "listings")) { die("API quota reached. Check back tomorrow for listings."); } // check if API is reachable.
        $results = $json->listings;
        $conn = new mysqli($dbHost, $dbAdmin, $dbAdminPw, $dbSchema);
        for ($i = 0; $i < count($results); $i++) {
            $sql = "REPLACE INTO dylan_db (query, heading, zip, vin, miles, msrp) VALUES(?, ?, ?, ?, ?, ?)";
            $stmt=$conn->prepare($sql) or die($conn->error); //*REMOVE ERROR OUTPUT FOR PRODUCTION
            $heading = $results[$i]->heading;
            property_exists($results[$i], "zip") ? $zip = $results[$i]->zip : $zip = NULL;
            $vin = $results[$i]->vin;
            property_exists($results[$i], "miles") ? $miles = $results[$i]->miles : $miles = NULL;
            property_exists($results[$i], "msrp") ? $msrp = $results[$i]->msrp : $msrp = NULL;
            $query = "$year $make $model";
            $stmt->bind_param("ssssss", $query, $heading, $zip, $vin, $miles, $msrp);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
    }
    echo "<p>Sales Listings for $query</p>";
    echo "<table class=marketTable><tr><th>Heading</th><th>Zip</th><th>VIN</th><th>Miles</th><th>MSRP</th></tr>"; // echo results
    for ($i = 0; $i < count($results); $i++) {
        echo "<tr>";
        if ($useDb) {
            echo "<td>" . $results[$i]["heading"] . "</td>";
            echo $results[$i]["zip"] != NULL ? "<td>" . $results[$i]["zip"] . "</td>" : "<td>N/A</td>";
            echo "<td>" . $results[$i]["vin"] . "</td>";
            echo $results[$i]["miles"] != NULL ? "<td>" . $results[$i]["miles"] . "</td>" : "<td>N/A</td>";
            echo $results[$i]["msrp"] != NULL ? "<td>" . $results[$i]["msrp"] . "</td>" : "<td>N/A</td>";
        } else {
            echo "<td>" . $results[$i]->heading . "</td>";
            echo property_exists($results[$i], "zip") ? "<td>" . $results[$i]->zip . "</td>" : "<td>N/A</td>";
            echo "<td>" . $results[$i]->vin . "</td>";
            echo property_exists($results[$i], "miles") ? "<td>" . $results[$i]->miles . "</td>" : "<td>N/A</td>";
            echo property_exists($results[$i], "msrp") ? "<td>\$" . $results[$i]->msrp . "</td>" : "<td>N/A</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
?>