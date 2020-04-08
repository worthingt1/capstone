<?php
ini_set('display_errors', 1); //*REMOVE FOR PRODUCTION
ini_set('display_startup_errors', 1); //*REMOVE FOR PRODUCTION
error_reporting(E_ALL); //*REMOVE FOR PRODUCTION
require("config.php"); // DB connection credentials
ini_set("allow_url_fopen", 1); //needed to load json API url
$make = "ford"; //To be replaced with a POST to load a make
$year = "2015"; //To be replaced with a POST to load a specified year
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => "http://api.marketcheck.com/v2/search/car/active?api_key=RKxVk0wx7MAmyMOao4WT2p74ajYVgIFg&year=2015&make=ford",
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
    $useDb = true; //initialize variables in this scope
    $results = null;
    try { // Check if current data is available from the database before trying API
        $conn = new mysqli($dbHost, $dbRead, $dbReadPw, $dbSchema);
        $sql = "SELECT * FROM dylan_db WHERE vin = ?";
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
                //echo $time->format("d-m-Y"); /*to be removed, debug
                if (date_diff($now, $time)->format('d') > 2) {
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
        $raw = curl_exec($curl);
        $json = json_decode($raw);
        $results = $json->listings;
        //var_dump($results);
        $conn = new mysqli($dbHost, $dbAdmin, $dbAdminPw, $dbSchema);
        for ($i = 0; $i < count($results); $i++) {
            $sql = "REPLACE INTO dylan_db (heading, vin, miles, msrp) VALUES(?, ?, ?, ?)";
            $stmt=$conn->prepare($sql) or die($conn->error); //*REMOVE ERROR OUTPUT FOR PRODUCTION
            $heading = $results[$i]->heading;
            $vin = $results[$i]->vin;
            property_exists($results[$i], "miles") ? $miles = $results[$i]->miles : $miles = NULL;
            $msrp = $results[$i]->msrp;
            $stmt->bind_param("ssss", $heading, $vin, $miles, $msrp);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
    }
    echo "Using API? ";
    echo $useDb ? "No. DB has current information." : "Yes. Updated DB for next use."; //*to be removed for production, showing if api is being used or not
    echo "<table><tr><th>Heading, VIN, miles, MSRP</th></tr>"; // echo results
    for ($i = 0; $i < count($results); $i++) {
        echo "<tr><td>";
        if ($useDb) {
            echo $results[$i]["heading"];
            echo " | ";
            echo $results[$i]["vin"];
            echo " | ";
            echo $results[$i]["miles"];
            echo " | ";
            echo $results[$i]["msrp"];
            echo " | ";
        } else {
            echo $results[$i]->heading;
            echo " | ";
            echo $results[$i]->vin;
            echo " | ";
            echo property_exists($results[$i], "miles") ? $miles = $results[$i]->miles : $miles = "NULL";
            echo " | ";
            echo $results[$i]->msrp;
            echo " | ";
        }
        echo "</td></tr>";
    }
    echo "</table>";
}
catch (Exception $error) {
    echo $error->getMessage(); 
}
?>

