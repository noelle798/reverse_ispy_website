<?php

include 'DatabaseInteraction.php';

session_start();

if(empty($_SESSION['uncertainQuestions'])) { 
	$_SESSION['uncertainQuestions'] = array();
}
if(empty($_SESSION['questionsAsked'])) { 
	$_SESSION['questionsAsked'] = array();
}

function idf($term) {
    
	$docFrequency = array();

	$mysqli = new mysqli("localhost", "iSpy_team", "password", "iSpy_features");
	$tag = array();

	if ($mysqli->connect_errno) {
	    printf("Connect failed: %s\n", $mysqli->connect_error);
	    exit();
	}

	$results = $mysqli->query("SELECT question FROM Questions");

	while($result = $results->fetch_assoc()) {

		$tokens = explode(" ", $result['question']);
		$uniqueTokens = array();

		foreach (array_keys($tokens) as $token) {
			$uniqueTokens[strtolower(preg_replace("/[.,?!]+/","",$tokens[$token]))] = null;
		}

		foreach (array_keys($uniqueTokens) as $token) {
		    if (array_key_exists($token, $docFrequency)) {
		        $docFrequency[$token] = $docFrequency[$token] + 1;
		    }
		    else {
		        $docFrequency[$token] = 1;
		    }
		}
	}

    if (array_key_exists($term, $docFrequency)) {
        return log10(count($docFrequency)/$docFrequency[$term]);
    }
    else {
        return -1.0;
    }
}

function insertToDB() {
	$mysqli = new mysqli("localhost", "iSpy_team", "password", "iSpy_features");

	if ($mysqli->connect_errno) {
	    printf("Connect failed: %s\n", $mysqli->connect_error);
	    exit();
	}
    $location = "/usr/local/share/reverse_iSpy/I Spy - Reversed/questions.txt";
    $file = fopen($location, "r") or die("Unable to open file!");
	$N = 0;
    while (($line = fgets($file)) !== false && $N < 289) {
	$sql = 'INSERT INTO Questions (question) VALUES ("' . trim($line) . '")';
	if ($mysqli->query($sql)=== TRUE) {
	}
	else {
		echo $mysqli->error;
	}
	$N = $N + 1;
    }
	$mysqli->close();
}

function extractKeyword($question) {
	$tokens = explode(" ", $question);
        $uniqueTokens = array();
	$keyword = "";

        foreach (array_keys($tokens) as $token) {
		$uniqueTokens[strtolower(preg_replace("/[.,?!]+/","",$tokens[$token]))] = null;
	}

        $highestIDF = 0.0;
        $newToken = false;

	foreach (array_keys($uniqueTokens) as $token) {
		if (idf($token) === -1.0) {
		    if ($newToken) {
			$keyword = $keyword . " " . $token;
		    }
		    else {
			$keyword = $token;
		    }
		    $newToken = true;
		}
		else if ((idf($token) > $highestIDF) && !$newToken) {
		    $keyword = $token;
		    $highestIDF = idf($token);
		}
		else if ((idf($token) === $highestIDF) && !$newToken) {
		    $keyword = $keyword . " " . $token;
		}
	}

	return $keyword;
}

function inTraining($question) {
	$mysqli = new mysqli("localhost", "iSpy_team", "password", "iSpy_features");

	if ($mysqli->connect_errno) {
	    printf("Connect failed: %s\n", $mysqli->connect_error);
	    exit();
	}

	$results = $mysqli->query("SELECT question FROM Questions");
	$mysqli->close();
	while($result = $results->fetch_assoc()) {
		if (strcmp(strtolower($result['question']), strtolower($question)) === 0) {
               		return true;
            	}
	}

        return false;
}

function addToTraining($question) {
	$mysqli = new mysqli("localhost", "iSpy_team", "password", "iSpy_features");

	if ($mysqli->connect_errno) {
	    printf("Connect failed: %s\n", $mysqli->connect_error);
	    exit();
	}

	$mysqli->query('INSERT INTO Questions (question) VALUES ("' . trim($question) . '")');
}

function endsWith($word, $ending) {
    /*
        Function taken from the StackExchange user MrHus
    */
    $length = strlen($ending);
    if ($length == 0) {
        return true;
    }

    return (substr($word, -$length) === $ending);
}

function selectObject($gameID) {
    $dir = "/usr/local/share/iSpy/Human_Games/Game" . $gameID;

    $listOfFiles = scandir($dir);
    $objectIDs = array();

    foreach (array_keys($listOfFiles) as $file) {
        if (endsWith(strval($listOfFiles[$file]), ".jpg")) {
            $singleObjectID = preg_replace("/[^0-9]/","",$listOfFiles[$file]);
            array_push($objectIDs, $singleObjectID);
        }
    }

    $random = rand(1, count($objectIDs));
    return $random;
}

function isGuess($question) {
    if (preg_match('/object.*\\d{1,2}/', strtolower($question))) {
        return true;
    }
    else {
        return false;
    }
}

function isWin($question, $objectID) {
    $objectGuess = preg_replace('/\D/',"",$question);
    if (strcmp(trim($objectGuess), trim($objectID)) === 0) {
        return 1;
    }
    else {
        return 0;
    }
}

function printObject($objectID) {
	if(strcmp($objectID, "1") === 0) {
		echo '<img src="http://i.imgur.com/jMk8tcS.jpg" style="width:100px" alt="Digital Alarm Clock">';
	}
	else if(strcmp($objectID, "2") === 0) {
		echo '<img src="http://i.imgur.com/LEw4TgD.jpg" style="width:100px" alt="Analog Alarm Clock">';	
	}
	else if(strcmp($objectID, "3") === 0) {
		echo '<img src="http://i.imgur.com/zwbOSnU.jpg" style="width:100px" alt="Red Soccer Ball">';
	}
	else if(strcmp($objectID, "4") === 0) {
		echo '<img src="http://i.imgur.com/68qgAIw.jpg" style="width:100px;" alt="Basketball">';	
	}
	else if(strcmp($objectID, "5") === 0) {
		echo '<img src="http://i.imgur.com/HKg6Gxv.jpg" style="width:100px" alt="Football">';
	}
	else if(strcmp($objectID, "6") === 0) {
		echo '<img src="http://i.imgur.com/JSSRVXH.jpg" style="width:100px" alt="Textbook">';
	}
	else if(strcmp($objectID, "7") === 0) {
		echo '<img src="http://i.imgur.com/0YU2bBh.jpg" style="width:100px;" alt="Yellow Flashlight">';
	}
	else if(strcmp($objectID, "8") === 0) {
		echo '<img src="http://i.imgur.com/s1wDLX2.jpg" style="width:100px" alt="Blue Soccer Ball">';
	}
	else if(strcmp($objectID, "9") === 0) {
		echo '<img src="http://i.imgur.com/ExgTd4b.jpg" style="width:100px" alt="Apple">';
	}
	else if(strcmp($objectID, "10") === 0) {
		echo '<img src="http://i.imgur.com/khDRiyI.jpg" style="width:100px" alt="Black Coffee Mug">';
	}
	else if(strcmp($objectID, "11") === 0) {
		echo '<img src="http://i.imgur.com/EX9nOGC.jpg" style="width:100px" alt="Book">';
	}
	else if(strcmp($objectID, "12") === 0) {
		echo '<img src="http://i.imgur.com/NJ3NVTB.jpg" style="width:100px" alt="Blue Flashlight">';
	}
	else if(strcmp($objectID, "13") === 0) {
		echo '<img src="http://i.imgur.com/9UtQMTd.jpg" style="width:100px" alt="Cardboard Box">';
	}
	else if(strcmp($objectID, "14") === 0) {
		echo '<img src="http://i.imgur.com/xCKSyyt.jpg" style="width:100px" alt="Pepper">';
	}
	else if(strcmp($objectID, "15") === 0) {
		echo '<img src="http://i.imgur.com/DYkscUc.jpg" style="width:100px" alt="Green Mug">';
	}
	else if(strcmp($objectID, "16") === 0) {
		echo '<img src="http://i.imgur.com/y68UQdI.jpg" style="width:100px" alt="Polkadot Box">';	
	}
	else if(strcmp($objectID, "17") === 0) {
		echo '<img src="http://i.imgur.com/BYdPMr7.jpg" style="width:100px" alt="Scissors">';
	}
}

function confirmation(){
	printHeader();
	echo '<div style="text-align:center"><p>Please keep this number as confirmation:</p><p>' . $_SESSION['sessionID'] . '</p></div>';
	echo '<form method="post" action="Game.php"><div style="text-align:center"><p>Would you just like to play again?</p></div><div style="width:93px; margin-left:auto; margin-right:auto;"><input type="submit" name="playAgain" value="Play Again"></div>';
	printFooter();
}

function postGame($isWin, $objectID, $guessID) {
	$_SESSION['isWin'] = $isWin;
	$_SESSION['guessID'] = $guessID;
	$time = date('U') - $_SESSION['startTime'];

	logGame($_SESSION['sessionID'], $isWin, $_SESSION['gameID'], $objectID, $time, $_SESSION['numGuesses']);
	printHeader();
	if($isWin) {
		if(count($_SESSION['uncertainQuestions']) > 0) {
			echo '<div><div style="text-align:center"><p>Congratulations! You won!</p></div><div style="text-align:center"><p>Would you help teach me the questions I had trouble with?</p></div><form method="post" action="Game.php"><div style="width:131px; margin-left:auto; margin-right:auto;"><input type="submit" name="teach" value="Teach questions"></div></form></div>';
		}
		else {
			echo '<div><div style="text-align:center"><p>Congratulations! You won!</p></div><div style="text-align:center"><p>Please keep this number as confirmation:</p><p>' . $_SESSION['sessionID'] . '</p></div><form method="post" action="Game.php"><div style="text-align:center"><p>Would you like to play again?</p></div><div style="width:93px; margin-left:auto; margin-right:auto;"><input type="submit" name="playAgain" value="Play Again"></div></form></div>';
		}
	}
	else {
		foreach (array_keys($_SESSION['questionsAsked']) as $question) {
		    if (retrieveConfidenceData($guessID, $_SESSION['questionsAsked'][$question])) {
			$_SESSION['uncertainQuestions'][$question] = $_SESSION['questionsAsked'][$question];
		    }
		}
		if(count($_SESSION['uncertainQuestions']) > 0) {
			echo '<div style="text-align:center"><p>Sorry, I was thinking of Object ' . $objectID . ', not Object ' . $guessID . '.</p><p>Would you help teach me the questions I had trouble with?</p><form method="post" action="Game.php"><input type="submit" name="teach" value="Teach questions"></form></div>';
		}
		else {
			echo '<div><div style="text-align:center"><p>Sorry, I was thinking of Object ' . $objectID . ', not Object ' . $guessID . '.</p><p>Please keep this number as confirmation:</p><p>' . $_SESSION['sessionID'] . '</p></div><form method="post" action="Game.php"><div style="text-align:center"><p>Would you like to play again?</p></div><div style="width:93px; margin-left:auto; margin-right:auto;"><input type="submit" name="playAgain" value="Play Again"></div></form></div>';

		}
	}
	printFooter();
}

function askQuestions($isWin, $objectID, $guessID, $uncertainQuestions, $questionsAsked) {
	$N = 0;
	printHeader();
    if ($isWin) {
	echo '<div style="text-align:center"><p>Just select "Yes" or "No" with object ' . $objectID . ' in mind.</p></div><div style="width:100px; margin-left: auto; margin-right: auto; margin-bottom: 5px">';
	printObject($objectID);
	echo '</div><form method="post" action="Game.php">';
	foreach (array_keys($uncertainQuestions) as $question) {
	    echo '<div style="margin-left:auto; margin-right: auto; width:310px"><input type="text" style="width:200px" value="' . $question . '" readonly><input type="radio" name="yes' . $N . '" value="1"><b>Yes</b><input type="radio" name="yes' . $N . '" value="0"><b>No</b></div>';
		$N = $N + 1;
	}

	echo '<div style="margin-left:auto; margin-right: auto; width:72px; margin-top:45px"><input type="submit" value="Submit" name="submitWin"></div></form>';
    }
    else {
	echo '<div style="text-align: center"><p>Just select "Yes" or "No" with Object ' . $guessID . ' in mind.</p></div><div style="width:100px; margin-left: auto; margin-right: auto; margin-bottom:5px">';
	printObject($guessID);
	echo '</div><form method="post" action="Game.php">';
	foreach (array_keys($uncertainQuestions) as $question) {
		echo '<div style="margin-left:auto; margin-right: auto; width:310px"><input type="text" style="width:200px" value="' . $question . '" readonly><input type="radio" name="guess' . $N . '" value="1"><b>Yes</b><input type="radio" name="guess' . $N . '" value="0"><b>No</b></div>';
		$N = $N + 1;
	}
	$N = 0;
	echo '<div style="text-align:center"><p>Now please help me with the same questions, but for Object ' . $objectID . ' in mind.</p></div><div style="width:100px; margin-left: auto; margin-right: auto; margin-bottom:5px">';
	printObject($objectID);
	echo '</div>';
	foreach (array_keys($uncertainQuestions) as $question) {
		echo '<div style="margin-left:auto; margin-right: auto; width:310px"><input type="text" style="width:200px" value="' . $question . '" readonly><input type="radio" name="object' . $N . '" value="1"><b>Yes</b><input type="radio" name="object' . $N . '" value="0"><b>No</b></div>';
		$N = $N + 1;
	}

	echo '<div style="margin-left:auto; margin-right: auto; width:72px; margin-top:45px"><input type="submit" value="Submit" name="submitLose"></div></form>';
    }
	printFooter();
}

function askAboutWin() {
	$uncertainQuestions = $_SESSION['uncertainQuestions'];
	$N = 0;
	$objectID = $_SESSION['objectID'];

	foreach (array_keys($uncertainQuestions) as $question) {
		if(isset($_POST['yes'.$N])) {
			if (strcmp($_POST['yes'.$N],"1") === 0) {

				updateGameplayYesCount($objectID, $uncertainQuestions[$question]);
			}
			else {
				updateGameplayNoCount($objectID, $uncertainQuestions[$question]);
			}
		}
		$N = $N + 1;
	}
	confirmation();
}

function askAboutLose() {
	$uncertainQuestions = $_SESSION['uncertainQuestions'];
	$N = 0;
	$objectID = $_SESSION['objectID'];
	$guessID = $_SESSION['guessID'];

	foreach (array_keys($uncertainQuestions) as $question) {
		if(isset($_POST['guess'.$N])) {
			if (strcmp($_POST['guess'.$N],"1") === 0) {
				updateGameplayYesCount($guessID, $uncertainQuestions[$question]);
			}
			else {
				updateGameplayNoCount($guessID, $uncertainQuestions[$question]);
			}
		}
		$N = $N + 1;
	}
	
	$N = 0;

	foreach (array_keys($uncertainQuestions) as $question) {
		if(isset($_POST['object'.$N])) {
			if (strcmp($_POST['object'.$N],"1") === 0) {
				updateGameplayYesCount($objectID, $uncertainQuestions[$question]);
			}
			else {
				updateGameplayNoCount($objectID, $uncertainQuestions[$question]);
			}
		}
		$N = $N + 1;
	}
	confirmation();
}

function getTimestamp() {
	/*
	Function taken from StackOverflow user eydelber
	*/
	return date('mdHis') . substr((string)microtime(), 2, 8);
}

function getQuestion($decision, $question) {
	printHeader();
	echo '<div style="text-align:center">
		<p>Example questions:</p>
		<p>Is it red?</p>
		<p>Is it Object 7?</p>

		<form method="post" action="Game.php">
			<input type="text" name="question" placeholder="Question" style="width: 300px"></input>
			<input type="submit" name="ask" value="Ask"></input>
		</form>';
	printFooter();
}

function getGameID() {
	session_destroy();
	printHeader();
	echo '<section id="game_id">
	   <form method="post" action="Game.php">
	      <input type="text" name="gameNum" placeholder="Game ID (1-30)"></input>
	      <input type="submit" name="submit" value="Play"></input>
	   </form>
	</section>;'
	printFooter();
	if (!empty($_POST['id'])) {
  		$question = $_POST['id'];
	} else {
 		$question = "";
	}
	return $question;
}

function makeDecision($keyword) {
	$decision = exec('java -Dfile.encoding=UTF-8 -classpath "/usr/local/share/reverse_ispy/src:/usr/local/share/reverse_ispy/libraries/lucene-1.4.3.jar:/usr/local/share/reverse_ispy/libraries/jaws-bin.jar:/usr/local/share/reverse_ispy/libraries/jnaoqi-1.14.5.jar:/usr/local/share/reverse_ispy/libraries/mysql-connector-java-5.1.30-bin.jar:/usr/local/share/reverse_ispy/libraries/stanford-corenlp-3.3.1.jar:/usr/local/share/reverse_ispy/libraries/simplenlg-v4.4.2.jar:/usr/local/share/reverse_ispy/libraries/stanford-corenlp-3.4.1-models.jar:/usr/local/share/reverse_ispy/libraries/stanford-corenlp-3.4.1.jar" DecisionMaker ' . $_SESSION['objectID'] . ' ' . $keyword);
	return $decision; 
}

function processQuestion($question) {
	$keyword = extractKeyword($question);
	$guess = isGuess($question);
	
	if($guess) {
		$win = isWin($question, $_SESSION['objectID']);
		$guessID = preg_replace("/[^0-9]/","",$question);
		postGame($win, $_SESSION['objectID'], $guessID);
	}
	else {
		$_SESSION['numGuesses'] = $_SESSION['numGuesses'] + 1;
		$inTraining = inTraining($question);
		if (!$inTraining) {
			addToTraining($question);
		}
		$decision = makeDecision($keyword);
		if ($decision !== "Yes." && $decision !== "No.") {
			$_SESSION['uncertainQuestions'][$question] = $keyword;
		}
		$_SESSION['questionsAsked'][$question] = $keyword;
		logQuestion($_SESSION['sessionID'], $question, $decision, $_SESSION['gameID'], $_SESSION['objectID']);
		getQuestion($decision,$question);
	}
}

function playGame($gameID) {

	session_unset();
	if (!isset($_SESSION['objectID'])) {
		$_SESSION['objectID'] = selectObject($gameID);
		$_SESSION['gameID'] = $gameID;
		$objectID = $_SESSION['objectID'];
		$_SESSION['startingKeyword'] = chooseStartingKeyword($objectID);
		$startingKeyword = $_SESSION['startingKeyword'];
		$_SESSION['startTime'] = date('U');
		$_SESSION['sessionID'] = getTimestamp();
		$_SESSION['numGuesses'] = 0;
	}

	getQuestion("","");
}

function printHeader() {
	echo '<!DOCTYPE html>
<html>

<head>
  <title>Reverse I Spy Game</title>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="Reverse_I_Spy_CSS.css">
</head>

<header>
  <p id="subtitle">The I Spy Project Team at the HiLT Lab presents:</p>
  <p id="title">Reverse I Spy</p>
</header>

<body>

<section id="images">
  <figure>
    <img src="http://i.imgur.com/jMk8tcS.jpg" class="image" alt="Digital Alarm Clock">
    <figcaption>Object 1</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/LEw4TgD.jpg" class="image" alt="Analog Alarm Clock">
    <figcaption>Object 2</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/zwbOSnU.jpg" class="image" alt="Red Soccer Ball">
    <figcaption>Object 3</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/68qgAIw.jpg" class="image" alt="Basketball">
    <figcaption>Object 4</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/HKg6Gxv.jpg" class="image" alt="Football">
    <figcaption>Object 5</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/JSSRVXH.jpg" class="image" alt="Textbook">
    <figcaption>Object 6</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/0YU2bBh.jpg" class="image" alt="Yellow Flashlight">
    <figcaption>Object 7</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/s1wDLX2.jpg" class="image" alt="Blue Soccer Ball">
    <figcaption>Object 8</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/ExgTd4b.jpg" class="image" alt="Apple">
    <figcaption>Object 9</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/khDRiyI.jpg" class="image" alt="Black Coffee Mug">
    <figcaption>Object 10</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/NJ3NVTB.jpg" class="image" alt="Blue Flashlight">
    <figcaption>Object 12</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/EX9nOGC.jpg" class="image" alt="Book">
    <figcaption>Object 11</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/9UtQMTd.jpg" class="image" alt="Cardboard Box">
    <figcaption>Object 13</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/xCKSyyt.jpg" class="image" alt="Pepper">
    <figcaption>Object 14</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/DYkscUc.jpg" class="image" alt="Green Mug">
    <figcaption>Object 15</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/y68UQdI.jpg" class="image" alt="Polkadot Box">
    <figcaption>Object 16</figcaption>
  </figure>
  
  <figure>
    <img src="http://i.imgur.com/BYdPMr7.jpg" class="image" alt="Scissors">
    <figcaption>Object 17</figcaption>
  </figure>
</section>';
}

function printFooter() {
	echo '</body>';
	echo '</html>';
}

if (isset($_POST['teach'])) {
	askQuestions($_SESSION['isWin'], $_SESSION['objectID'], $_SESSION['guessID'], $_SESSION['uncertainQuestions'], $_SESSION['questionsAsked']);
}

if (isset($_POST['submitWin'])) {
	askAboutWin();
}

if (isset($_POST['submitLose'])) {
	askAboutLose();
}

if (isset($_POST['playAgain'])) {
	session_unset();
	getGameID();
}

if (isset($_POST['ask'])) {
	if(isset($_POST['question'])) {
		if(strcmp($_POST['question'],"") !== 0) {
			processQuestion($_POST['question']);
		}
		else {
			getQuestion("You can't ask empty questions.","");
		}
	}
	else {
		getQuestion("You can't ask empty questions.","");
	}
}

if (isset($_POST['submit'])) {
	if(isset($_POST['gameNum'])) {
		if(is_numeric($_POST['gameNum']) && $_POST['gameNum'] > 0 && $_POST['gameNum'] < 31) {
			playGame($_POST['gameNum']);
		}
		else {
			getGameID();
		}
	}
	else {
		getGameID();
	}
}

?>