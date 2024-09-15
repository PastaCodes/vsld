<?php
      mb_internal_encoding('UTF-8');
      session_start();
      unset($_SESSION['user']);
      $error = NULL;
      if (isset($_POST['submit'])
      && isset($_POST['email']) && mb_strlen($_POST['email']) <= 255
      && isset($_POST['password']) && mb_strlen($_POST['password']) <= 60
      ) {
            try {
                  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                  $conn = mysqli_connect('localhost', 'root', '', 'VSLD');
                  $stmt = $conn->prepare('SELECT Nome, Cognome, Password, Ruolo FROM Utenti WHERE Email = ?');
                  $stmt->bind_param('s', $_POST['email']);
                  $stmt->execute();
                  $result = $stmt->get_result();
                  if ($result->num_rows != 1) {
                        $error = 'credentials';
                  } else {
                        $user = $result->fetch_assoc();
                        if (password_verify($_POST['password'], $user['Password']) === false) {
                              $error = 'credentials';
                        } else {
                              $_SESSION['user'] = array(
                                    'first_name' => $user['Nome'],
                                    'last_name' => $user['Cognome'],
                                    'email' => $_POST['email'],
                                    'role' => $user['Ruolo']
                              );
                              header('Location: ./internal.php');
                              die;
                        }
                  }
            } catch (Exception $e) {
                  $error = 'connection';
            }
      }
?>
<!DOCTYPE html>
<html lang="it" dir="ltr">
      <head>
            <meta charset="utf-8">
            <link rel="stylesheet" href="./style.css">
            <link rel="icon" type="image/x-icon" href="./dude.svg">
            <title>Accedi</title>
      </head>
      <body>
            <section>
                  <h1>VSLD</h1>
            </section>
            <section>
                  <a href="./index.php">Vai alla visuale pubblica</a>
            </section>
            <form method=POST action="./login.php">
			<table>
                        <tr>
                              <th colspan=2>Accedi alla visuale interna</th>
                        </tr>
				<tr>
					<td>
						<label for=email>Indirizzo email aziendale:</label>
					</td>
					<td>
						<input class=text-wide type=email required id=email name=email maxlength=255 placeholder="nome.cognome@sus.com" autocomplete="email">
					</td>
				</tr>
				<tr>
					<td>
						<label for=password>Password:</label>
					</td>
					<td>
						<input class=text-wide type=password required id=password name=password maxlength=60 placeholder="..." autocomplete="current-password">
					</td>
				</tr>
				<?php
					if ($error == 'connection') {
						echo '<tr><td></td><td>Si è verificato un errore.</td></tr>';
					} else if ($error == 'credentials') {
						echo '<tr><td></td><td>Credenziali errate.</td></tr>';
					}
				?>
				<tr>
					<td class=center colspan=2>
						<input class=button-wide type=submit name=submit value="Accedi">
					</td>
				</tr>
			</table>
		</form>
      </body>
</html>
