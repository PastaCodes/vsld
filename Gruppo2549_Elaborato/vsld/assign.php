<?php
      mb_internal_encoding('UTF-8');
      session_start();
      if (!isset($_SESSION['user'])) {
            header('Location: ./login.php');
            die;
      }
      if ($_SESSION['user']['role'] != 'Amministratore') {
            header('Location: ./internal.php');
            die;
      }
?>
<!DOCTYPE html>
<html lang="it" dir="ltr">
      <head>
            <meta charset="utf-8">
            <link rel="stylesheet" href="./style.css">
            <link rel="icon" type="image/x-icon" href="./dude.svg">
            <title>Assegnazione spazi</title>
      </head>
      <body>
            <section>
                  <h1>VSLD</h1>
            </section>
            <?php
                  $error = NULL;
                  try {
                        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                        $conn = mysqli_connect('localhost', 'root', '', 'VSLD');
                  } catch (Exception $e) {
                        $error = 'connection';
                  }
                  if (isset($_GET['space'])) {
                        $space = $_GET['space'];
                        if (isset($_GET['add'])) {
                              try {
                                    $stmt = $conn->prepare('INSERT INTO Assegnazioni VALUES (?, ?)');
                                    $stmt->bind_param('ss', $space, $_GET['add']);
                                    $stmt->execute();
                              } catch (Exception $e) {
                                    $error = 'connection';
                              }
                        }
                        if (isset($_GET['remove'])) {
                              try {
                                    $stmt = $conn->prepare('DELETE FROM Assegnazioni WHERE Spazio = ? AND Sviluppatore = ?');
                                    $stmt->bind_param('ss', $space, $_GET['remove']);
                                    $stmt->execute();
                              } catch (Exception $e) {
                                    $error = 'connection';
                              }
                        }
                        echo '<section><a href="./assign.php">Indietro</a></section><section><h3>Hai selezionato lo spazio <div class=boxed>' . htmlentities($space) . '</div>.</h3></section><section><h2>Attualmente assegnati:</h2><ul>';
                        try {
                              $stmt = $conn->prepare(
                                    'SELECT Nome, Cognome, Email, COUNT(Spazio) AS Numero
                                          FROM Utenti LEFT JOIN Assegnazioni
                                          ON Sviluppatore = Email
                                          WHERE Ruolo = \'Sviluppatore\'
                                          AND Email IN (SELECT Sviluppatore FROM assegnazioni WHERE Spazio = ?)
                                          GROUP BY Email
                                          ORDER BY Numero DESC'
                              );
                              $stmt->bind_param('s', $space);
                              $stmt->execute();
                              $result = $stmt->get_result();
                              if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                          echo '<li>(' . $row['Numero'] . ') ' . htmlentities($row['Nome']) . ' ' . htmlentities($row['Cognome']) . ' (' . htmlentities($row['Email']) . ')' .
                                                '<span class=code>&nbsp;|&nbsp;<a href="./assign.php?space=' . $_GET['space'] . '&remove=' . urlencode($row['Email']) . '" title="Rimuovi">&#8659;-</a></span></li>';
                                    }
                              } else {
                                    echo 'Non ci sono sviluppatori assegnati.';
                              }
                        } catch (Exception $e) {
                              $error = 'connection';
                        }
                        echo '</ul></section><h2>Assegna:</h2><ul>';
                        try {
                              $stmt = $conn->prepare(
                                    'SELECT Nome, Cognome, Email, COUNT(Spazio) AS Numero
                                          FROM Utenti LEFT JOIN Assegnazioni
                                          ON Sviluppatore = Email
                                          WHERE Ruolo = \'Sviluppatore\'
                                          AND Email NOT IN (SELECT Sviluppatore FROM Assegnazioni WHERE Spazio = ?)
                                          GROUP BY Email
                                          ORDER BY Numero'
                              );
                              $stmt->bind_param('s', $space);
                              $stmt->execute();
                              $result = $stmt->get_result();
                              if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                          echo '<li>(' . $row['Numero'] . ') ' . htmlentities($row['Nome']) . ' ' . htmlentities($row['Cognome']) . ' (' . htmlentities($row['Email']) . ')' .
                                                '<span class=code>&nbsp;|&nbsp;<a href="./assign.php?space=' . $_GET['space'] . '&add=' . urlencode($row['Email']) . '" title="Aggiungi">&#8657;+</a></span></li>';
                                    }
                              } else {
                                    echo 'Non ci sono altri sviluppatori.';
                              }
                        } catch (Exception $e) {
                              $error = 'connection';
                        }
                        echo '</ul>';
                  } else if (isset($_GET['user'])) {
                        $email = $_GET['user'];
                        if (isset($_GET['add'])) {
                              try {
                                    $stmt = $conn->prepare('INSERT INTO Assegnazioni VALUES (?, ?)');
                                    $stmt->bind_param('ss', $_GET['add'], $email);
                                    $stmt->execute();
                              } catch (Exception $e) {
                                    $error = 'connection';
                              }
                        }
                        if (isset($_GET['remove'])) {
                              try {
                                    $stmt = $conn->prepare('DELETE FROM Assegnazioni WHERE Spazio = ? AND Sviluppatore = ?');
                                    $stmt->bind_param('ss', $_GET['remove'], $email);
                                    $stmt->execute();
                              } catch (Exception $e) {
                                    $error = 'connection';
                              }
                        }
                        echo '<section><a href="./assign.php">Indietro</a></section><section><h3>Hai selezionato l\'utente <div class=box-simple>' . htmlentities($email) . '</div>.</h3></section><section><h2>Attualmente assegnati:</h2><ul>';
                        try {
                              $stmt = $conn->prepare(
                                    'SELECT Nome, COUNT(Sviluppatore) AS Numero
                                          FROM Spazi LEFT JOIN Assegnazioni
                                          ON Spazio = Nome
                                          WHERE Nome IN (SELECT Spazio FROM Assegnazioni WHERE Sviluppatore = ?)
                                          GROUP BY Nome
                                          ORDER BY Numero DESC'
                              );
                              $stmt->bind_param('s', $email);
                              $stmt->execute();
                              $result = $stmt->get_result();
                              if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                          echo '<li>(' . $row['Numero'] . ') <span class=code>' . htmlentities($row['Nome']) . '&nbsp;|&nbsp;<a href="./assign.php?user=' . $_GET['user'] . '&remove=' . urlencode($row['Nome']) . '" title="Rimuovi">&#8659;-</a></span></li>';
                                    }
                              } else {
                                    echo 'Non ci sono spazi assegnati.';
                              }
                        } catch (Exception $e) {
                              $error = 'connection';
                        }
                        echo '</ul></section><h2>Assegna:</h2><ul>';
                        try {
                              $stmt = $conn->prepare(
                                    'SELECT Nome, COUNT(Sviluppatore) AS Numero
                                          FROM Spazi LEFT JOIN Assegnazioni
                                          ON Spazio = Nome
                                          WHERE Nome NOT IN (SELECT Spazio FROM Assegnazioni WHERE Sviluppatore = ?)
                                          GROUP BY Nome
                                          ORDER BY Numero'
                              );
                              $stmt->bind_param('s', $email);
                              $stmt->execute();
                              $result = $stmt->get_result();
                              if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                          echo '<li>(' . $row['Numero'] . ') <span class=code>' . htmlentities($row['Nome']) . '&nbsp;|&nbsp;<a href="./assign.php?user=' . $_GET['user'] . '&add=' . urlencode($row['Nome']) . '" title="Aggiungi">&#8657;+</a></span></li>';
                                    }
                              } else {
                                    echo 'Non ci sono altri spazi.';
                              }
                        } catch (Exception $e) {
                              $error = 'connection';
                        }
                        echo '</ul>';
                  } else {
                        echo '<section><a href="./internal.php">Indietro</a></section><section><h2>Seleziona uno spazio:</h2><ul>';
                        try {
                              $result = $conn->query('SELECT Nome, COUNT(Sviluppatore) AS Numero FROM Spazi LEFT JOIN Assegnazioni ON Spazio = Nome GROUP BY Nome ORDER BY Numero DESC');
                              if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                          echo '<li>(' . $row['Numero'] . ') <span class=code><a href="./assign.php?space=' . urlencode($row['Nome']) . '">' . htmlentities($row['Nome']) . '</a> </span></li>';
                                    }
                              } else {
                                    echo 'Non ci sono spazi.';
                              }
                        } catch (Exception $e) {
                              $error = 'connection';
                        }
                        echo '</ul></section><section>Oppure</section><h2>Seleziona uno sviluppatore:</h2><ul>';
                        try {
                              $result = $conn->query('SELECT Nome, Cognome, Email, COUNT(Spazio) AS Numero FROM Utenti LEFT JOIN Assegnazioni ON Sviluppatore = Email WHERE Ruolo = \'Sviluppatore\' GROUP BY Email ORDER BY Numero DESC');
                              if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                          echo '<li>(' . $row['Numero'] . ') <a href="./assign.php?user=' . urlencode($row['Email']) . '">' . htmlentities($row['Nome']) . ' ' . htmlentities($row['Cognome']) . ' (' . htmlentities($row['Email']) . ')</a> </span></li>';
                                    }
                              } else {
                                    echo 'Non ci sono sviluppatori.';
                              }
                        } catch (Exception $e) {
                              $error = 'connection';
                        }
                  }
                  if ($error == 'connection') {
                        echo '<section class=reverse>Si è verificato un errore.</section>';
                  }
            ?>
      </body>
</html>
