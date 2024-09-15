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
      if (isset($_GET['delete'])) {
            try {
                  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                  $conn = mysqli_connect('localhost', 'root', '', 'VSLD');
                  $stmt = $conn->prepare('DELETE FROM Utenti WHERE Email = ?');
                  $stmt->bind_param('s', $_GET['delete']);
                  $stmt->execute();
            } catch (Exception $e) { }
      }
      $error = NULL;
      $generated_password = '';
      if (isset($_POST['submit'])
      && isset($_POST['first_name']) && mb_strlen($_POST['first_name']) <= 30
      && isset($_POST['last_name']) && mb_strlen($_POST['last_name']) <= 60
      && isset($_POST['badge_number']) && mb_strlen($_POST['badge_number']) == 5
      && (empty($_POST['email']) || mb_strlen($_POST['email']) <= 255)
      && isset($_POST['role']) && in_array($_POST['role'], ['Visualizzatore', 'Sviluppatore', 'Amministratore'])
      ) {
            if (empty($_POST['email'])) {
                  $first_name_s = str_replace(' ', '', $_POST['first_name']);
                  $last_name_s = str_replace(' ', '', $_POST['last_name']);
                  if (!ctype_alpha($first_name_s) || !ctype_alpha($last_name_s)) {
                        $error = 'generate';
                  }
                  $email = strtolower($first_name_s) . '.' . strtolower($last_name_s) . '@sus.com';
            } else {
                  $email = $_POST['email'];
            }
            if ($error === NULL) {
                  try {
                        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                        $conn = mysqli_connect('localhost', 'root', '', 'VSLD');
                        $stmt = $conn->prepare('SELECT Email FROM Utenti WHERE Email = ?');
                        $stmt->bind_param('s', $email);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                              $error = 'email';
                        } else {
                              $stmt = $conn->prepare('SELECT NumeroBadge FROM Utenti WHERE NumeroBadge = ?');
                              $stmt->bind_param('s', $_POST['badge_number']);
                              $stmt->execute();
                              $result = $stmt->get_result();
                              if ($result->num_rows > 0) {
                                    $error = 'badge';
                              } else {
                                    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=~!@#$%^&*()_+,./<>?;:[]{}|';
                                    $count = mb_strlen($alphabet);
                                    for ($i = 0; $i < 12; $i++) {
                                          $generated_password .= $alphabet[random_int(0, $count - 1)];
                                    }
                                    $stmt = $conn->prepare('INSERT INTO Utenti VALUES (?, ?, ?, ?, ?, ?)');
                                    $hash = password_hash($generated_password, PASSWORD_BCRYPT);
                                    $stmt->bind_param('ssssss', $email, $_POST['badge_number'], $_POST['first_name'], $_POST['last_name'], $hash, $_POST['role']);
                                    $stmt->execute();
                              }
                        }
                  } catch (Exception $e) {
                        $error = 'connection';
                  }
            }
      }
?>
<!DOCTYPE html>
<html lang="it" dir="ltr">
      <head>
            <meta charset="utf-8">
            <link rel="stylesheet" href="./style.css">
            <link rel="icon" type="image/x-icon" href="./dude.svg">
            <title>Gestione utenti</title>
      </head>
      <body>
            <section>
                  <h1>VSLD</h1>
            </section>
            <section>
                  <a href="./internal.php">Indietro</a>
            </section>
            <section>
                  <form method=POST action="./users.php">
      			<table>
                              <tr>
                                    <th colspan=2>Inserisci nuovo utente</th>
                              </tr>
      				<tr>
      					<td>
      						<label for=first_name>Nome:</label>
      					</td>
      					<td>
      						<input class=text-wide type=text required id=first_name name=first_name maxlength=30 placeholder="...">
      					</td>
      				</tr>
      				<tr>
                                    <td>
      						<label for=last_name>Cognome:</label>
      					</td>
      					<td>
      						<input class=text-wide type=text required id=last_name name=last_name maxlength=60 placeholder="...">
      					</td>
      				</tr>
                              <tr>
                                    <td>
      						<label for=badge_number>Numero badge:</label>
      					</td>
      					<td>
      						<input class=text-wide type=text required id=badge_number name=badge_number pattern=".{5}" title="Inserisci 5 caratteri." placeholder="00000">
      					</td>
      				</tr>
                              <tr>
                                    <td>
      						<label for=email>Indirizzo email aziendale:</label>
      					</td>
      					<td>
      						<input class=text-wide type=text id=email name=email maxlength=255 placeholder="(genera automaticamente)">
      					</td>
      				</tr>
                              <tr>
                                    <td>
      						<label for=role>Ruolo:</label>
      					</td>
      					<td>
                                          <select class=text-wide id=role name=role>
                                                <option>Visualizzatore</option>
                                                <option>Sviluppatore</option>
                                                <option>Amministratore</option>
                                          </select>
      					</td>
      				</tr>
      				<tr>
      					<td class=center colspan=2>
      						<input class=button-wide type=submit name=submit value="Inserisci">
      					</td>
      				</tr>
      			</table>
      		</form>
            </section>
            <?php
                  if ($error == 'connection') {
                        echo '<section>Si è verificato un errore.</section>';
                  } else if ($error == 'generate') {
                        echo '<section>Attenzione: Impossibile generare automaticamente l\'indirizzo email per questo utente.</section>';
                  } else if ($error == 'email') {
                        echo '<section>Attenzione: Indirizzo email già in uso.</section>';
                  } else if ($error == 'badge') {
                        echo '<section>Attenzione: Numero badge già in uso.</section>';
                  } else if (!empty($generated_password)) {
                        echo '<section>Importante: Utente inserito con password temporanea <div class=boxed>' . htmlentities($generated_password) . '</div>.</section>';
                  }
            ?>
            <h2>Elenco utenti:</h2>
            <?php
                  try {
                        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                        $conn = mysqli_connect('localhost', 'root', '', 'VSLD');
                        $result = $conn->query('SELECT Email, NumeroBadge, Nome, Cognome, Ruolo FROM Utenti');
                        if ($result->num_rows > 0) {
                              echo '<table><tr><th>Nome</th><th>Cognome</th><th>Ruolo</th><th>Email</th><th>Badge</th><td></td></tr>';
                              while ($row = $result->fetch_assoc()) {
                                    echo '<tr><td>' . htmlentities($row['Nome']) . '</td><td>' . htmlentities($row['Cognome']) . '</td><td>' . $row['Ruolo'] . '</td><td>' . htmlentities($row['Email']) . '</td><td>' . htmlentities($row['NumeroBadge']) . '</td>' .
                                          '<td class=center title="Elimina utente"><a href="./users.php?delete=' . urlencode($row['Email']) . '">&#128465;</a></td></tr>';
                              }
                              echo '</table>';
                        } else {
                              echo 'Non ci sono utenti. Come fai ad essere qui?';
                        }
                  } catch (Exception $e) {
                        $error = 'connection';
                  }
                  if ($error == 'connection') {
                        echo 'Si è verificato un errore.';
                  }
            ?>
      </body>
</html>
