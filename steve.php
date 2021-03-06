<html>
  <head>
    <title>JSON Custom Search API Example</title>
  </head>
  <body>
    <div id="content"></div>
<script>
function hndlr(response) {
	      //for (var i = 0; i < response.items.length; i++) {
	      for (var i = 0; i < 1; i++) {
        var item = []
		item[i] = response.items[i];
	var model = "chevy";
	document.getElementById("content").innerHTML += "<form action='steve.php' method='post' id='imageSearch'><input type='text' hidden name='model' value='" + model + "'/><input type='text' hidden name='imgUrl' value='" + item[i].link + "' /><input type='text' hidden name='useApi' value='yes'/></form>";
	//alert(JSON.stringify(item[i])); //VIEW RETURNED RESULT PARAMERTERS
	document.getElementById("imageSearch").submit();
      }
      }

    </script>
<?php
ini_set('display_errors', 1); //*REMOVE FOR PRODUCTION
ini_set('display_startup_errors', 1); //*REMOVE FOR PRODUCTION
error_reporting(E_ALL); //*REMOVE FOR PRODUCTION
	require("config.php"); // DB connection credentials
	$make = 'chevy';
	$results = null;
	try {
			$conn = new mysqli($dbHost, $dbRead, $dbReadPw, $dbSchema);
			$sql = "SELECT * FROM steve WHERE model = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s", $make);
			$stmt->execute();
			$result = $stmt->get_result();
			$results = $result->fetch_all(MYSQLI_ASSOC);
			$stmt->close();
			$conn->close();
			if (count($results) == 0) {
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
		}
				echo '<script src="https://www.googleapis.com/customsearch/v1?q=chevy&searchType=image&key=AIzaSyAFG_XFuzvrGSF_UML-V34t-UwNrYAngtI&cx=013204994027278291303:mhubrebrimn&callback=hndlr"></script>';
			} else
			{
				$imgUrl = $results[0]["url"];
				echo "<img src='" . $imgUrl . "'/>";
			}
	} catch (Exception $ex) {

	}
?>
  </body>
</html>
