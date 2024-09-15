<?php
      mb_internal_encoding('UTF-8');
      session_start();
      if (!isset($_SESSION['user'])) {
            header('Location: ./login.php');
		die;
      }
      $error = NULL;
      if (isset($_POST['submit'])
      && isset($_POST['password']) && mb_strlen($_POST['password']) <= 60
      ) {
            try {
                  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                  $conn = mysqli_connect('localhost', 'root', '', 'VSLD');
                  $stmt = $conn->prepare('UPDATE Utenti SET Password = ? WHERE Email = ?');
                  $new_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
                  $stmt->bind_param('ss', $new_password, $_SESSION['user']['email']);
                  $stmt->execute();
                  header('Location: ./login.php');
                  die;
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
            <title>Modifica password</title>
      </head>
      <body>
            <section>
                  <h1>VSLD</h1>
            </section>
            <section>
                  <a href="./internal.php">Indietro</a>
            </section>
            <form method=POST action="./password.php">
			<table>
                        <tr>
                              <th colspan=2>Modifica password</th>
                        </tr>
				<tr>
					<td>
						<label for=email>Nuova password:</label>
					</td>
					<td>
						<input class=text-wide type=password required id=password name=password maxlength=60 placeholder="..." autocomplete="new-password">
					</td>
				</tr>
				<?php
					if ($error == 'connection') {
						echo '<tr><td></td><td>Si è verificato un errore.</td></tr>';
					}
				?>
				<tr>
					<td class=center colspan=2>
						<input class=button-wide type=submit name=submit value="Conferma">
					</td>
				</tr>
			</table>
		</form>
      </body>
</html>
