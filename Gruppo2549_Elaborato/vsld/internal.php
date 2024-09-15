<?php
      mb_internal_encoding('UTF-8');
      session_start();
      if (!isset($_SESSION['user'])) {
            header('Location: ./login.php');
		die;
      }
      $is_admin = $_SESSION['user']['role'] == 'Amministratore';
      function nullable_query($conn, &$error, $query, $notnull, $null) {
            $str = str_repeat('s', count($notnull));
            try {
                  foreach ($null as $name => $value) {
                        if ($value === NULL) {
                              $query .= ' AND ' . $name . ' IS NULL';
                        } else {
                              $query .= ' AND ' . $name . ' = ?';
                              $str .= 's';
                              $notnull[] = $value;
                        }
                  }
                  $stmt = $conn->prepare($query);
                  $stmt->bind_param($str, ...$notnull);
                  $stmt->execute();
                  return $stmt->get_result();
            } catch (Exception $e) {
                  $error = 'connection';
            }
      }
      function editor($can_edit, $conn, &$error, $space, $type = NULL, $member = NULL, $parameter = NULL) {
            try {
                  if ($can_edit && isset($_POST['add'])) {
                        $stmt = $conn->prepare('INSERT INTO Blocchi(Spazio, Tipo, Membro, Parametro) VALUES (?, ?, ?, ?)');
                        $stmt->bind_param('ssss', $space, $type, $member, $parameter);
                        $stmt->execute();
                  }
                  $url = './internal.php?space=' . urlencode($space) .
                        ($type === NULL ? '' : '&type=' . urlencode($type)) .
                        ($member === NULL ? '' : '&member=' . urlencode($member)) .
                        ($parameter === NULL ? '' : '&parameter=' . urlencode($parameter));
                  $result = nullable_query($conn, $error, 'SELECT Codice FROM Blocchi WHERE Spazio = ?',
                        [$space], ['Tipo' => $type, 'Membro' => $member, 'Parametro' => $parameter]);
                  $id = $result->num_rows > 0 ? $result->fetch_assoc()['Codice'] : NULL;
                  if ($id !== NULL) {
                        try {
                              if (isset($_POST['save']) && isset($_POST['text']) && mb_strlen($_POST['text']) <= 1000) {
                                    $stmt = $conn->prepare('UPDATE Blocchi SET Testo = ?, UltimaModifica = CURRENT_DATE WHERE Codice = ?');
                                    $stmt->bind_param('ss', $_POST['text'], $id);
                                    $stmt->execute();
                                    $links = json_decode($_POST['links']);
                                    $conn->autocommit(FALSE);
                                    $stmt = $conn->prepare('DELETE FROM Riferimenti WHERE Blocco = ?');
                                    $stmt->bind_param('s', $id);
                                    $stmt->execute();
                                    foreach ($links as $link) {
                                          $stmt = $conn->prepare('INSERT INTO Riferimenti VALUES (?, ?, ?, ?, ?, ?, ?)');
                                          $stmt->bind_param('sssssss', $id, ...$link);
                                          $stmt->execute();
                                    }
                                    $conn->commit();
                              }
                        } catch (Exception $e) {
                              $error = 'links';
                        }
                        if (isset($_POST['remove'])) {
                              $stmt = $conn->prepare('DELETE FROM Blocchi WHERE Codice = ?');
                              $stmt->bind_param('s', $id);
                              $stmt->execute();
                              $id = NULL;
                        }
                        if (isset($_POST['addauthor']) && isset($_POST['email']) && mb_strlen($_POST['email']) <= 255) {
                              $stmt = $conn->prepare('INSERT INTO Autori VALUES (?, ?)');
                              $stmt->bind_param('ss', $id, $_POST['email']);
                              $stmt->execute();
                        }
                        if (isset($_GET['removeauthor']) && mb_strlen($_GET['removeauthor']) <= 255) {
                              $stmt = $conn->prepare('DELETE FROM Autori WHERE Blocco = ? AND Sviluppatore = ?');
                              $stmt->bind_param('ss', $id, $_GET['removeauthor']);
                              $stmt->execute();
                        }
                  }
                  echo '<h2>Documentazione:</h2>';
                  $stmt = $conn->prepare('SELECT Testo, UltimaModifica FROM Blocchi WHERE Codice = ?');
                  $stmt->bind_param('s', $id);
                  $stmt->execute();
                  $result = $stmt->get_result();
                  if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $text = $row['Testo'];
                        $last_edited = $row['UltimaModifica'];
                        $stmt = $conn->prepare('SELECT Inizio, Lunghezza, Spazio, Tipo, Membro, Parametro FROM Riferimenti WHERE Blocco = ?');
                        $stmt->bind_param('s', $id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $links = [];
                        while ($row = $result->fetch_assoc()) {
                              $links[] = [$row['Inizio'], $row['Lunghezza'], $row['Spazio'], $row['Tipo'], $row['Membro'], $row['Parametro']];
                        }
                        echo '<script type="text/javascript">links = ' . json_encode($links) . ';</script>';
                        if ($can_edit) {
                              echo '<h3>Antemprima:</h3><hr><p id=preview>' . htmlentities($text) . '</p><hr><h3 style="margin-bottom: 5px;">Modifica:</h3>';
                              echo '<section><form method=POST action="' . $url . '"><textarea id=text name=text rows=5 placeholder="..." spellcheck=false>';
                              echo $text;
                              echo '</textarea><input type=hidden id=links name=links value=""><button id=cancel class=button-wide disabled>Annulla</button><input class=button-wide type=submit disabled id=save name=save value="Salva">' .
                                    '<input class=button-wide type=submit name=remove value="Rimuovi"></form></section>';
                              echo '<section><h3>Gestisci riferimenti:</h3><p class=gray style="margin-bottom: 10px;">Seleziona una porzione di testo per continuare.</p>' .
                                    '<button id=cleanse class=button-wide disabled onclick="doCleanse()">Pulisci</button>&emsp;oppure&emsp;' .
                                    '<input type=text id=linkspace placeholder="(specifica uno spazio)">' .
                                    '<input type=text id=linktype placeholder="(specifica un tipo)">' .
                                    '<input type=text id=linkmember placeholder="(specifica un membro)">' .
                                    '<input type=text id=linkparameter placeholder="(specifica un parametro)">' .
                                    '<button id=add disabled onclick="doAdd()">+</button>' .
                                    '</section>';
                        } else {
                              echo '<section><p id=preview>' . htmlentities($text) . '</p></section>';
                        }
                        if ($error == 'links') {
                              echo '<section>Si è verificato un errore nell\'aggiornamento della documentazione. Verificare gli elementi.</section>';
                        }
                        $stmt = $conn->prepare('SELECT Sviluppatore FROM Autori WHERE Blocco = ?');
                        $stmt->bind_param('s', $id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                              echo '<section><h3>Autori: ';
                              $i = 0;
                              while ($row = $result->fetch_assoc()) {
                                    echo $row['Sviluppatore'];
                                    if ($can_edit) {
                                          echo ' (<a href="' . $url . '&removeauthor=' . urlencode($row['Sviluppatore']) . '" title="Rimuovi">&#128465;</a>)';
                                    }
                                    if (++$i < $result->num_rows) {
                                          echo ', ';
                                    }
                              }
                              echo '.</h3></section>';
                        }
                        if ($can_edit) {
                              $stmt = $conn->prepare('SELECT Email FROM Utenti WHERE Ruolo = \'Sviluppatore\' AND Email NOT IN (SELECT Sviluppatore FROM Autori WHERE Blocco = ?)');
                              $stmt->bind_param('s', $id);
                              $stmt->execute();
                              $result = $stmt->get_result();
                              echo '<section><h3 style="margin-bottom: 5px;">Aggiungi autore:</h3><form method=POST action="' . $url . '"><select class=text-wide id=email name=email required><option selected value style="color: gray;">(seleziona)</option>';
                              while ($row = $result->fetch_assoc()) {
                                    echo '<option>' . htmlentities($row['Email']) . '</option>';
                              }
                              echo '</select><input type=submit name=addauthor value="+"></form></section>';
                        }
                        echo '<section><span class=gray>Ultima modifica: ' . $last_edited . '.</span></section>';
                  } else if ($can_edit) {
                        echo '<section><form method=POST action="' . $url . '"><input class=button-wide type=submit name=add value="Aggiungi"></form></section>';
                  } else {
                        echo '<section><span class=gray>Questo elemento non è attualmente documentato.</span></section>';
                  }
            } catch (Exception $e) {
                  $error = 'connection';
            }
            try {
                  $result = nullable_query($conn, $error,
                        'SELECT B.Testo, R.Inizio, R.Lunghezza, B.Spazio, B.Tipo, B.Membro, B.Parametro
                              FROM Riferimenti AS R, Blocchi AS B
                              WHERE B.Codice = R.Blocco
                              AND R.Spazio = ?
                        ',
                        [$space], ['R.Tipo' => $type, 'R.Membro' => $member, 'R.Parametro' => $parameter]);
                  echo '<h2>Riferimenti:</h2><ul>';
                  if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                              $url = './internal.php?space=' . urlencode($row['Spazio']) .
                                    ($row['Tipo'] === NULL ? '' : '&type=' . urlencode($row['Tipo'])) .
                                    ($row['Membro'] === NULL ? '' : '&member=' . urlencode($row['Membro'])) .
                                    ($row['Parametro'] === NULL ? '' : '&parameter=' . urlencode($row['Parametro']));
                              $text = str_replace("\r\n", ' ', $row['Testo']);
                              echo '<li>&quot;' . (
                                    $row['Inizio'] <= 10
                              ?     htmlentities(mb_substr($text, 0, $row['Inizio']))
                              :     '...' . htmlentities(mb_substr($text, $row['Inizio'] - 7, 7))
                              ) . '<u>' . htmlentities(mb_substr($text, $row['Inizio'], $row['Lunghezza'])) . '</u>' . (
                                    mb_strlen($text) - $row['Inizio'] - $row['Lunghezza'] <= 10
                              ?     htmlentities(mb_substr($text, $row['Inizio'] + $row['Lunghezza']))
                              :     htmlentities(mb_substr($text, $row['Inizio'] + $row['Lunghezza'], 7)) . '...'
                              ) . '&quot;&emsp;in&emsp;<a class=code href="' . $url . '">' .
                              htmlentities($row['Spazio']) .
                              ($row['Tipo'] === NULL ? '' : '<span class=gray>/</span>' . htmlentities($row['Tipo'])) .
                              ($row['Membro'] === NULL ? '' : '<span class=gray>/</span>' . htmlentities($row['Membro'])) .
                              ($row['Parametro'] === NULL ? '' : '<span class=gray>/</span>' . htmlentities($row['Parametro'])) .
                              '</a></li>';
                        }
                  } else {
                        echo '<span class=gray>Non ci sono riferimenti a questo elemento.</span>';
                  }
                  echo '</ul>';
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
            <title>Visuale interna</title>
            <script type="text/javascript">
                  let visibility, label, preview, textarea, cancel, save, cleanse, add;
                  let linkspace, linktype, linkmember, linkparameter, linksinput;
                  function categoryChanged(select, element) {
                        if (element == 'type') {
                              if (select.value == 'Implementazione') {
                                    visibility.value = 'Privata';
                                    visibility.setAttribute('disabled', 'disabled');
                              } else {
                                    visibility.removeAttribute('disabled');
                              }
                        } else if (element == 'member') {
                              if (select.value == 'Campo') {
                                    visibility.value = 'Privato';
                                    visibility.setAttribute('disabled', 'disabled');
                              } else if (select.value == 'MetodoAstratto') {
                                    visibility.value = 'Pubblico';
                                    visibility.setAttribute('disabled', 'disabled');
                              } else {
                                    visibility.removeAttribute('disabled');
                              }
                              if (select.value == 'Campo' || select.value == 'Costante') {
                                    label.innerHTML = 'Tipo del membro:';
                              } else {
                                    label.innerHTML = 'Tipo restituito:';
                              }
                        }
                  }
                  var links = [];
                  function escape(text) {
                        return text.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll('\'', '&apos;').replaceAll('\n', '<br>');
                  }
                  function render(text) {
                        rendered = '';
                        cursor = 0;
                        links.forEach(link => {
                              rendered += escape(text.substring(cursor, link[0]));
                              const url = './internal.php?space=' + encodeURIComponent(link[2]) +
                                    (link[3] === null ? '' : '&type=' + encodeURIComponent(link[3])) +
                                    (link[4] === null ? '' : '&member=' + encodeURIComponent(link[4])) +
                                    (link[5] === null ? '' : '&parameter=' + encodeURIComponent(link[5]));
                              rendered += '<a href="' + url + '">';
                              rendered += escape(text.substring(link[0], link[0] + link[1]));
                              rendered += '</a>';
                              cursor = link[0] + link[1];
                        });
                        rendered += escape(text.substring(cursor));
                        preview.innerHTML = rendered;
                  }
                  function reactToRemove(start, end) {
                        links.forEach(link => {
                              if (link[0] < start && link[0] + link[1] > end) {
                                    link[1] -= end - start;
                              } else if (link[0] >= start && link[0] + link[1] <= end) {
                                    link[1] = 0;
                              } else if (link[0] < start && link[0] + link[1] > start) {
                                    link[1] = start - link[0];
                              } else if (link[0] < end && link[0] + link[1] > end) {
                                    link[1] -= end - link[0];
                                    link[0] = start;
                              } else if (link[0] >= end) {
                                    link[0] -= end - start;
                              }
                        });
                        links = links.filter(link => link[1] > 0);
                  }
                  function reactToInsert(start, end) {
                        links.forEach(link => {
                              if (link[0] < start && link[0] + link[1] >= start) {
                                    link[1] += end - start;
                              } else if (link[0] >= start) {
                                    link[0] += end - start;
                              }
                        });
                  }
                  var lastSelection = [null, null];
                  var lastLength = null;
                  function selectionChanged() {
                        lastSelection = [textarea.selectionStart, textarea.selectionEnd];
                        if (lastSelection[0] < lastSelection[1]) {
                              cleanse.removeAttribute('disabled');
                              add.removeAttribute('disabled');
                        } else {
                              cleanse.setAttribute('disabled', 'disabled');
                              add.setAttribute('disabled', 'disabled');
                        }
                  }
                  function textChanged() {
                        if (lastSelection[0] < lastSelection[1]) {
                              reactToRemove(lastSelection[0], lastSelection[1]);
                              const inserted = textarea.textLength - lastLength + lastSelection[1] - lastSelection[0];
                              if (inserted > 0) {
                                    reactToInsert(lastSelection[0], lastSelection[0] + inserted);
                              }
                        } else if (textarea.textLength > lastLength) {
                              reactToInsert(lastSelection[0], lastSelection[0] + textarea.textLength - lastLength);
                        } else if (textarea.selectionStart == lastSelection[0]) {
                              reactToRemove(lastSelection[0], lastSelection[0] + lastLength - textarea.textLength);
                        } else {
                              reactToRemove(lastSelection[0] - lastLength + textarea.textLength, lastSelection[0]);
                        }
                        render(textarea.value);
                        cancel.removeAttribute('disabled');
                        save.removeAttribute('disabled');
                        lastLength = textarea.textLength;
                        selectionChanged();
                        linksinput.value = JSON.stringify(links);
                  }
                  document.addEventListener('DOMContentLoaded', () => {
                        visibility = document.getElementById('visibility');
                        label = document.getElementById('type-label');
                        preview = document.getElementById('preview');
                        textarea = document.getElementById('text');
                        cancel = document.getElementById('cancel');
                        save = document.getElementById('save');
                        cleanse = document.getElementById('cleanse');
                        add = document.getElementById('add');
                        linkspace = document.getElementById('linkspace');
                        linktype = document.getElementById('linktype');
                        linkmember = document.getElementById('linkmember');
                        linkparameter = document.getElementById('linkparameter');
                        linksinput = document.getElementById('links');
                        if (textarea) {
                              linksinput.value = JSON.stringify(links);
                              textarea.addEventListener('selectionchange', () => selectionChanged());
                              textarea.addEventListener('input', () => textChanged());
                              textarea.addEventListener('focusin', () => selectionChanged());
                              textarea.addEventListener('focusout', (event) => {
                                    if (!event.relatedTarget || event.relatedTarget.id != 'cleanse') {
                                          cleanse.setAttribute('disabled', 'disabled');
                                    }
                                    if (!event.relatedTarget || event.relatedTarget.id != 'add') {
                                          add.setAttribute('disabled', 'disabled');
                                    }
                              });
                              lastLength = textarea.textLength;
                        }
                        if (preview) {
                              render(preview.innerHTML);
                        }
                  });
                  function doCleanse() {
                        window.getSelection().empty();
                        length = links.length;
                        links = links.filter(link => link[0] < lastSelection[0] || link[0] + link[1] > lastSelection[1]);
                        cleanse.setAttribute('disabled', 'disabled');
                        if (links.length != length) {
                              render(textarea.value);
                              cancel.removeAttribute('disabled');
                              save.removeAttribute('disabled');
                              linksinput.value = JSON.stringify(links);
                        }
                  }
                  function doAdd() {
                        window.getSelection().empty();
                        if (links.every(link => link[0] >= lastSelection[1] || lastSelection[0] >= link[0] + link[1])) {
                              link = [lastSelection[0], lastSelection[1] - lastSelection[0],
                                    linkspace.value,
                                    linktype.value.length === 0 ? null : linktype.value,
                                    linkmember.value.length === 0 ? null : linkmember.value,
                                    linkparameter.value.length === 0 ? null : linkparameter.value
                              ];
                              linkspace.value = linktype.value = linkmember.value = linkparameter.value = '';
                              links.push(link);
                              links.sort((a, b) => a[0] - b[0]);
                              render(textarea.value);
                              cancel.removeAttribute('disabled');
                              save.removeAttribute('disabled');
                              linksinput.value = JSON.stringify(links);
                        }
                        add.setAttribute('disabled', 'disabled');
                  }
            </script>
      </head>
      <body>
            <section>
                  <h1>VSLD</h1>
            </section>
            <section>
                  <h3>Ciao, <?= htmlentities($_SESSION['user']['first_name']) ?>.</h3>
                  (<?= htmlentities($_SESSION['user']['email']) ?>)
                  <br>
                  <a href="./login.php">Esci / Cambia accesso</a>
                  <br>
                  <a href="./password.php">Modifica password</a>
            </section>
            <?php
                  if ($is_admin) {
                        echo '<section><div><h3>Pannello Amministratore:</h3><ul>
                              <li><a href="./users.php">Gestisci utenti</a></li>
                              <li><a href="./assign.php">Assegna spazi</a></li>
                        </ul></div></section>';
                  }
            ?>
            <section>
                  <a href="./index.php">Vai alla visuale pubblica</a>
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
                        $can_edit = $is_admin;
                        if (!$is_admin && $_SESSION['user']['role'] == 'Sviluppatore') {
                              try {
                                    $stmt = $conn->prepare('SELECT * FROM Assegnazioni WHERE Spazio = ? AND Sviluppatore = ?');
                                    $stmt->bind_param('ss', $space, $_SESSION['user']['email']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $can_edit = $result->num_rows == 1;
                              } catch (Exception $e) {
                                    $error = 'connection';
                              }
                        }
                        if (isset($_GET['type'])) {
                              $type = $_GET['type'];
                              $type_details = NULL;
                              try {
                                    $stmt = $conn->prepare('SELECT Categoria, Pubblico FROM Tipi WHERE Spazio = ? AND Nome = ?');
                                    $stmt->bind_param('ss', $space, $type);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    if ($result->num_rows == 1) {
                                          $type_details = $result->fetch_assoc();
                                    }
                              } catch (Exception $e) {
                                    $error = 'connection';
                              }
                              if (isset($_GET['member'])) {
                                    $member = $_GET['member'];
                                    if (isset($_GET['parameter'])) {
                                          $parameter = $_GET['parameter'];
                                          $exists = FALSE;
                                          try {
                                                $stmt = $conn->prepare('SELECT * FROM Parametri WHERE SpazioFunzione = ? AND TipoFunzione = ? AND MembroFunzione = ? AND Nome = ?');
                                                $stmt->bind_param('ssss', $space, $type, $member, $parameter);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                $exists = $result->num_rows == 1;
                                          } catch (Exception $e) {
                                                $error = 'connection';
                                          }
                                          /* VISUALE PARAMETRO */
                                          echo '<section><div><h3 class=code><a href="./internal.php">&#8962;</a>&nbsp;/&nbsp;<a href="./internal.php?space=' . urlencode($space) . '">' . htmlentities($space) . '</a>&nbsp;/&nbsp;<a href="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '">' . htmlentities($type) .
                                                '</a>&nbsp;/&nbsp;<a href="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '&member=' . urlencode($member) . '">' . htmlentities($member) . '</a>&nbsp;/&nbsp;' . $parameter . '</h3></div></section>';
                                          if ($exists) {
                                                editor($can_edit, $conn, $error, $space, $type, $member, $parameter);
                                          } else {
                                                echo 'Elemento non trovato.';
                                          }
                                    } else {
                                          /* VISUALE MEMBRO */
                                          $exists = FALSE;
                                          $is_function = FALSE;
                                          $member_public = FALSE;
                                          try {
                                                $stmt = $conn->prepare('SELECT Categoria, Pubblico FROM Membri WHERE SpazioPadre = ? AND TipoPadre = ? AND Nome = ?');
                                                $stmt->bind_param('sss', $space, $type, $member);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                if ($result->num_rows == 1) {
                                                      $row = $result->fetch_assoc();
                                                      $exists = TRUE;
                                                      $is_function = in_array($row['Categoria'], ['Metodo', 'MetodoAstratto', 'MetodoStatico']);
                                                      $member_public = $row['Pubblico'];
                                                }
                                          } catch (Exception $e) {
                                                $error = 'connection';
                                          }
                                          echo '<section><div><h3 class=code><a href="./internal.php">&#8962;</a>&nbsp;/&nbsp;<a href="./internal.php?space=' . urlencode($space) . '">' . htmlentities($space) . '</a>&nbsp;/&nbsp;<a href="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '">' . htmlentities($type) . '</a>&nbsp;/&nbsp;' . htmlentities($member) . '</h3></div></section>';
                                          if ($exists) {
                                                if ($is_function) {
                                                      if (isset($_POST['submit'])
                                                      && isset($_POST['parameter']) && mb_strlen($_POST['parameter']) <= 60
                                                      && isset($_POST['value-space']) && mb_strlen($_POST['value-space']) <= 60
                                                      && isset($_POST['value-type']) && mb_strlen($_POST['value-type']) <= 60
                                                      ) {
                                                            try {
                                                                  $stmt = $conn->prepare('SELECT Pubblico FROM Tipi WHERE Spazio = ? AND Nome = ?');
                                                                  $stmt->bind_param('ss', $_POST['value-space'], $_POST['value-type']);
                                                                  $stmt->execute();
                                                                  $result = $stmt->get_result();
                                                                  if ($result->num_rows > 0) {
                                                                        if ($type_details['Pubblico'] && $member_public && !$result->fetch_assoc()['Pubblico']) {
                                                                              $error = 'visibility';
                                                                        } else {
                                                                              $stmt = $conn->prepare('SELECT MAX(Indice) AS Massimo FROM Parametri WHERE SpazioFunzione = ? AND TipoFunzione = ?');
                                                                              $stmt->bind_param('ss', $space, $type);
                                                                              $stmt->execute();
                                                                              $result = $stmt->get_result();
                                                                              $next = $result->num_rows > 0 ? $result->fetch_assoc()['Massimo'] + 1 : 0;
                                                                              $stmt = $conn->prepare('INSERT INTO Parametri VALUES (?, ?, ?, ?, ?, ?, ?)');
                                                                              $stmt->bind_param('sssssss', $_POST['parameter'], $member, $type, $space, $next, $_POST['value-type'], $_POST['value-space']);
                                                                              $stmt->execute();
                                                                        }
                                                                  }
                                                            } catch (Exception $e) {
                                                                  $error = 'connection';
                                                            }
                                                      }
                                                      if (isset($_GET['delete'])) {
                                                            try {
                                                                  $stmt = $conn->prepare('DELETE FROM Parametri WHERE SpazioFunzione = ? AND TipoFunzione = ? AND MembroFunzione = ? AND Nome = ?');
                                                                  $stmt->bind_param('ssss', $space, $type, $member, $_GET['delete']);
                                                                  $stmt->execute();
                                                            } catch (Exception $e) {
                                                                  $error = 'connection';
                                                            }
                                                      }
                                                      echo '<section><h2>Elenco parametri:</h2><ol>';
                                                      try {
                                                            $stmt = $conn->prepare('SELECT Nome, Tipo, Spazio FROM Parametri WHERE SpazioFunzione = ? AND TipoFunzione = ? AND MembroFunzione = ? ORDER BY Indice');
                                                            $stmt->bind_param('sss', $space, $type, $member);
                                                            $stmt->execute();
                                                            $result = $stmt->get_result();
                                                            if ($result->num_rows > 0) {
                                                                  while ($row = $result->fetch_assoc()) {
                                                                        echo '<li><span class=code><a href="./internal.php?space=' . urlencode($row['Spazio']) . '&type=' . urlencode($row['Tipo']) . '">' . htmlentities($row['Tipo']) . '</a> ';
                                                                        echo '<a href="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '&member=' . urlencode($member) . '&parameter=' . urlencode($row['Nome']) . '">' . htmlentities($row['Nome']) . '</a>';
                                                                        if ($can_edit) {
                                                                              echo '&nbsp;|&nbsp;<a href="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '&member=' . urlencode($member) . '&delete=' . urlencode($row['Nome']) . '" title="Elimina parametro">&#128465;</a>';
                                                                        }
                                                                        echo '</span></li>';
                                                                  }
                                                            } else {
                                                                  echo 'Questa funzione non ha parametri.';
                                                            }
                                                      } catch (Exception $e) {
                                                            $error = 'connection';
                                                      }
                                                      if ($can_edit) {
                                                            echo '<h3 style="margin: 15px 0 5px 0;">Inserisci nuovo:</h3><li><form method=POST action="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '&member=' . urlencode($member) . '">';
                                                            echo '<p id=type-label style="margin-bottom: 5px;">Tipo del parametro:</p>
                                                            <input type=text required id=value-space name=value-space maxlength=60 placeholder="Spazio"><input type=text required id=value-type name=value-type maxlength=60 placeholder="Nome">
                                                            <br><hr><input type=text required id=parameter name=parameter maxlength=60 placeholder="Nome del parametro"><input type=submit name=submit value="+"></form></li>';
                                                      }
                                                      echo '</ol></section>';
                                                      if ($error == 'visibility') {
                                                            echo '<section>Un membro pubblico di un tipo pubblico non può dipendere da un tipo privato.</section>';
                                                      }
                                                }
                                                editor($can_edit, $conn, $error, $space, $type, $member);
                                          } else {
                                                echo 'Elemento non trovato.';
                                          }
                                    }
                              } else {
                                    /* VISUALE TIPO */
                                    echo '<section><div><h3 class=code><a href="./internal.php">&#8962;</a>&nbsp;/&nbsp;<a href="./internal.php?space=' . urlencode($space) . '">' . htmlentities($space) . '</a>&nbsp;/&nbsp;' . htmlentities($type) . '</h3></div></section>';
                                    if ($type_details !== NULL) {
                                          if (isset($_POST['submit'])
                                          && isset($_POST['member']) && mb_strlen($_POST['member']) <= 60
                                          && (
                                                ($type_details['Categoria'] == 'Enumerazione' && !isset($_POST['category']) && !isset($_POST['visibility']) && !isset($_POST['value-space']) && !isset($_POST['value-type']))
                                                ||
                                                ($type_details['Categoria'] == 'Interfaccia' && isset($_POST['category']) && (
                                                      ($_POST['category'] == 'MetodoAstratto' && !isset($_POST['visibility']))
                                                      ||
                                                      (in_array($_POST['category'], ['MetodoStatico', 'Costante']) && isset($_POST['visibility']) && in_array($_POST['visibility'], ['Privato', 'Pubblico']))
                                                ) && isset($_POST['value-space']) && mb_strlen($_POST['value-space']) <= 60 && isset($_POST['value-type']) && mb_strlen($_POST['value-type']) <= 60)
                                                ||
                                                ($type_details['Categoria'] == 'Implementazione' && isset($_POST['category']) && (
                                                      ($_POST['category'] == 'Campo' && !isset($_POST['visibility']))
                                                      ||
                                                      (in_array($_POST['category'], ['Metodo', 'MetodoStatico', 'Costante']) && isset($_POST['visibility']) && in_array($_POST['visibility'], ['Privato', 'Pubblico']))
                                                ) && isset($_POST['value-space']) && mb_strlen($_POST['value-space']) <= 60 && isset($_POST['value-type']) && mb_strlen($_POST['value-type']) <= 60)
                                          )) {
                                                try {
                                                      $stmt = $conn->prepare('SELECT * FROM MetodiEreditati WHERE SpazioFiglio = ? AND TipoFiglio = ? AND Nome = ?');
                                                      $stmt->bind_param('sss', $space, $type, $_POST['member']);
                                                      $stmt->execute();
                                                      if ($stmt->get_result()->num_rows > 0) {
                                                            $error = 'duplicate';
                                                      } else {
                                                            $category = $type_details['Categoria'] == 'Enumerazione' ? 'Valore' : $_POST['category'];
                                                            $public = in_array($category, ['Valore', 'MetodoAstratto']) || ($category != 'Campo' && $_POST['visibility'] == 'Pubblico');
                                                            $stmt = $conn->prepare('SELECT Pubblico FROM Tipi WHERE Spazio = ? AND Nome = ?');
                                                            $stmt->bind_param('ss', $_POST['value-space'], $_POST['value-type']);
                                                            $stmt->execute();
                                                            $result = $stmt->get_result();
                                                            if ($result->num_rows == 0 && $type_details['Categoria'] != 'Enumerazione') {
                                                                  $error = 'valuenotfound';
                                                            } else {
                                                                  $row = $result->fetch_assoc();
                                                                  if ($type_details['Pubblico'] && $public && isset($row['Pubblico']) && !$row['Pubblico']) {
                                                                        $error = 'valuevisibility';
                                                                  } else {
                                                                        $stmt = $conn->prepare('INSERT INTO Membri VALUES (?, ?, ?, ?, ?, ?, ?)');
                                                                        $value_type = $_POST['value-type'] ?? NULL;
                                                                        $value_space = $_POST['value-space'] ?? NULL;
                                                                        $stmt->bind_param('sssssss', $_POST['member'], $type, $space, $category, $public, $value_type, $value_space);
                                                                        $stmt->execute();
                                                                  }
                                                            }
                                                      }
                                                } catch (Exception $e) {
                                                      $error = 'connection';
                                                }
                                          } else if (isset($_POST['submit'])
                                          && isset($_POST['intf-space']) && mb_strlen($_POST['intf-space']) <= 60
                                          && isset($_POST['intf-name']) && mb_strlen($_POST['intf-name']) <= 60
                                          ) {
                                                try {
                                                      $stmt = $conn->prepare('SELECT Categoria, Pubblico FROM Tipi WHERE Spazio = ? AND Nome = ?');
                                                      $stmt->bind_param('ss', $_POST['intf-space'], $_POST['intf-name']);
                                                      $stmt->execute();
                                                      $result = $stmt->get_result();
                                                      $row = $result->fetch_assoc();
                                                      if ($row === NULL || $row['Categoria'] != 'Interfaccia' || (!$row['Pubblico'] && $_POST['intf-space'] != $space)) {
                                                            $error = 'invalid';
                                                      } else if ($_POST['intf-space'] == $space && $_POST['intf-name'] == $type) {
                                                            $error = 'itself';
                                                      } else if ($type_details['Pubblico'] && !$row['Pubblico']) {
                                                            $error = 'intfvisibility';
                                                      } else {
                                                            $stmt = $conn->prepare('SELECT * FROM Avi WHERE SpazioAvo = ? AND TipoAvo = ? AND SpazioFiglio = ? AND TipoFiglio = ?');
                                                            $stmt->bind_param('ssss', $space, $type, $_POST['intf-space'], $_POST['intf-name']);
                                                            $stmt->execute();
                                                            if ($stmt->get_result()->num_rows > 0) {
                                                                  $error = 'cycle';
                                                            } else {
                                                                  $stmt = $conn->prepare(
                                                                        'WITH TuttiMembri(Nome, Tipo, Spazio) AS (
                                                                              SELECT Nome, TipoPadre, SpazioPadre FROM Membri
                                                                              UNION
                                                                              SELECT Nome, TipoFiglio, SpazioFiglio FROM MetodiEreditati
                                                                        )
                                                                        SELECT Nome FROM TuttiMembri
                                                                              WHERE Spazio = ? AND Tipo = ?
                                                                              AND Nome IN (
                                                                                    SELECT Nome FROM MetodiEreditati WHERE SpazioFiglio = ? AND TipoFiglio = ?
                                                                                    UNION
                                                                                    SELECT Nome FROM Membri WHERE SpazioPadre = ? AND TipoPadre = ? AND Categoria = "MetodoAstratto"
                                                                              );
                                                                  ');
                                                                  $stmt->bind_param('ssssss', $space, $type, $_POST['intf-space'], $_POST['intf-name'], $_POST['intf-space'], $_POST['intf-name']);
                                                                  $stmt->execute();
                                                                  if ($stmt->get_result()->num_rows > 0) {
                                                                        $error = 'duplicating';
                                                                  } else {
                                                                        $stmt = $conn->prepare('INSERT INTO Ereditarietà VALUES (?, ?, ?, ?)');
                                                                        $stmt->bind_param('ssss', $_POST['intf-name'], $_POST['intf-space'], $type, $space);
                                                                        $stmt->execute();
                                                                  }
                                                            }
                                                      }
                                                } catch (Exception $e) {
                                                      $error = 'connection';
                                                }
                                          }
                                          if (isset($_GET['delete'])) {
                                                try {
                                                      $stmt = $conn->prepare('DELETE FROM Membri WHERE SpazioPadre = ? AND TipoPadre = ? AND Nome = ?');
                                                      $stmt->bind_param('sss', $space, $type, $_GET['delete']);
                                                      $stmt->execute();
                                                } catch (Exception $e) {
                                                      $error = 'connection';
                                                }
                                          }
                                          if (isset($_GET['removespace']) && isset($_GET['removetype'])) {
                                                try {
                                                      $stmt = $conn->prepare('DELETE FROM Ereditarietà WHERE SpazioFiglio = ? AND TipoFiglio = ? AND SpazioPadre = ? AND TipoPadre = ?');
                                                      $stmt->bind_param('ssss', $space, $type, $_GET['removespace'], $_GET['removetype']);
                                                      $stmt->execute();
                                                } catch (Exception $e) {
                                                      $error = 'connection';
                                                }
                                          }
                                          if ($type_details['Categoria'] != 'Enumerazione') {
                                                try {
                                                      $stmt = $conn->prepare('SELECT TipoPadre, SpazioPadre FROM Ereditarietà WHERE SpazioFiglio = ? AND TipoFiglio = ?');
                                                      $stmt->bind_param('ss', $space, $type);
                                                      $stmt->execute();
                                                      $result = $stmt->get_result();
                                                      if ($result->num_rows > 0) {
                                                            echo '<section><h3>Interfacce ' . ($type_details['Categoria'] == 'Interfaccia' ? 'estese' : 'implementate') . ':<span class=code> ';
                                                            $i = 0;
                                                            while ($row = $result->fetch_assoc()) {
                                                                  echo '<a href="./internal.php?space=' . urlencode($row['SpazioPadre']) . '&type=' . urlencode($row['TipoPadre']) . '">' . htmlentities($row['TipoPadre']) . '</a>';
                                                                  if ($can_edit) {
                                                                        echo ' (<a href="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '&removespace=' . urlencode($row['SpazioPadre']) . '&removetype=' . urlencode($row['TipoPadre']) . '" title="Rimuovi">&#128465;</a>)';
                                                                  }
                                                                  if (++$i < $result->num_rows) {
                                                                        echo ', ';
                                                                  }
                                                            }
                                                            echo '</span>.</h3></section>';
                                                      }
                                                } catch (Exception $e) {
                                                      $error = 'connection';
                                                }
                                                if ($can_edit) {
                                                      echo '<section><h3 style="margin-bottom: 5px;">Aggiungi interfaccia da ' . ($type_details['Categoria'] == 'Interfaccia' ? 'estendere' : 'implementare') . ':</h3><form method=POST action="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '">
                                                            <input type=text required id=intf-space name=intf-space maxlength=60 placeholder="Spazio"><input type=text required id=intf-name name=intf-name maxlength=60 placeholder="Nome"><input type=submit name=submit value="+">
                                                      </form></section>';
                                                }
                                                if ($error == 'invalid') {
                                                      echo '<section>Il tipo specificato non esisteo o non è un\'interfaccia visibile.</section>';
                                                } else if ($error == 'itself') {
                                                      echo '<section>Un\'interfaccia non può ereditare da se stessa.</section>';
                                                } else if ($error == 'intfvisibility') {
                                                      echo '<section>Un\'interfaccia pubblica non può estenderne una privata.</section>';
                                                } else if ($error == 'cycle') {
                                                      echo '<section>Non sono ammesse ereditarietà cicliche.</section>';
                                                } else if ($error == 'duplicating') {
                                                      echo '<section>L\'interfaccia specificata richiede metodi con nomi già utilizzati da membri di questo tipo.</section>';
                                                }
                                          }
                                          if ($type_details['Categoria'] == 'Interfaccia') {
                                                try {
                                                      $stmt = $conn->prepare('SELECT TipoFiglio, SpazioFiglio FROM Ereditarietà WHERE SpazioPadre = ? AND TipoPadre = ?');
                                                      $stmt->bind_param('ss', $space, $type);
                                                      $stmt->execute();
                                                      $result = $stmt->get_result();
                                                      if ($result->num_rows > 0) {
                                                            echo '<section><h3>Tipi derivati:<span class=code> ';
                                                            $i = 0;
                                                            while ($row = $result->fetch_assoc()) {
                                                                  echo '<a href="./internal.php?space=' . urlencode($row['SpazioFiglio']) . '&type=' . urlencode($row['TipoFiglio']) . '">' . htmlentities($row['TipoFiglio']) . '</a>';
                                                                  if (++$i < $result->num_rows) {
                                                                        echo ', ';
                                                                  }
                                                            }
                                                            echo '</span>.</h3></section>';
                                                      }
                                                } catch (Exception $e) {
                                                      $error = 'connection';
                                                }
                                          }
                                          echo '<section><h2>Elenco membri:</h2><ul>';
                                          try {
                                                if (isset($_GET['order'])) {
                                                      $stmt = $conn->prepare(
                                                            'SELECT M.Nome, M.Categoria, M.Pubblico, M.Tipo, M.Spazio, COUNT(R.Blocco) AS Numero
                                                                  FROM Membri AS M LEFT JOIN Riferimenti AS R
                                                                  ON R.Spazio = M.SpazioPadre
                                                                  AND R.Tipo = M.TipoPadre
                                                                  AND R.Membro = M.Nome
                                                                  AND R.Parametro IS NULL
                                                                  WHERE M.SpazioPadre = ?
                                                                  AND M.TipoPadre = ?
                                                                  GROUP BY M.Nome
                                                                  ORDER BY Numero DESC, M.Nome');
                                                } else {
                                                      $stmt = $conn->prepare('SELECT Nome, Categoria, Pubblico, Tipo, Spazio FROM Membri WHERE SpazioPadre = ? AND TipoPadre = ? ORDER BY Nome');
                                                }
                                                $stmt->bind_param('ss', $space, $type);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                if ($result->num_rows > 0) {
                                                      if (isset($_GET['order'])) {
                                                            echo '<section><a href="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '">Ordina alfabeticamente</a></section>';
                                                      } else {
                                                            echo '<section><a href="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '&order">Ordina per numero di riferimenti</a></section>';
                                                      }
                                                      $display = array('Campo' => 'field', 'Metodo' => 'method', 'MetodoAstratto' => 'abstract method', 'MetodoStatico' => 'static method', 'Costante' => 'const', 'Valore' => 'val');
                                                      while ($row = $result->fetch_assoc()) {
                                                            echo '<li>';
                                                            if (isset($_GET['order'])) {
                                                                  echo '<span class=code>(' . $row['Numero'] . ') </span>';
                                                            }
                                                            echo '<span class="code' . (in_array($row['Categoria'], ['Campo', 'MetodoAstratto', 'Valore']) ? ' gray' : '') . '">' . ($row['Pubblico'] ? 'publ' : 'priv') . ' </span>';
                                                            echo '<span class=code>' . $display[$row['Categoria']] .
                                                                  ($row['Categoria'] == 'Enumerazione' ? '' : ' <a href="./internal.php?space=' . urlencode($row['Spazio']) . '&type=' . urlencode($row['Tipo']) . '">' . htmlentities($row['Tipo']) . '</a>') .
                                                                  ' <a href="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '&member=' . urlencode($row['Nome']) . '">' . htmlentities($row['Nome']) . '</a>';
                                                            if ($can_edit) {
                                                                  echo '&nbsp;|&nbsp;<a href="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '&delete=' . urlencode($row['Nome']) . '" title="Elimina membro">&#128465;</a>';
                                                            }
                                                            echo '</span></li>';
                                                      }
                                                } else {
                                                      echo 'Questo tipo non ha membri.';
                                                }
                                          } catch (Exception $e) {
                                                $error = 'connection';
                                          }
                                          if ($can_edit) {
                                                echo '<h3 style="margin: 15px 0 5px 0;">Inserisci nuovo:</h3><li><form method=POST action="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($type) . '">';
                                                if ($type_details['Categoria'] == 'Enumerazione') {
                                                      echo '<input type=text required id=member name=member maxlength=60 placeholder="Nome del valore"><input type=submit name=submit value="+">';
                                                } else {
                                                      echo '<select id=visibility name=visibility disabled>
                                                            <option>Pubblico</option>
                                                            <option' . ($type_details['Categoria'] == 'Implementazione' ? ' selected' : '') . '>Privato</option>
                                                      </select>' .
                                                      '<select id=category name=category onchange="categoryChanged(this, \'member\')">' .
                                                            ($type_details['Categoria'] == 'Implementazione' ? '<option>Campo</option><option>Metodo</option>' : '') .
                                                            ($type_details['Categoria'] == 'Interfaccia' ? '<option value="MetodoAstratto">Metodo astratto</option>' : '') .
                                                            '<option value="MetodoStatico">Metodo statico</option><option>Costante</option>' .
                                                      '</select>
                                                      <br><hr><p id=type-label style="margin-bottom: 5px;">' . ($type_details['Categoria'] == 'Implementazione' ? 'Tipo del membro:' : 'Tipo restituito:') . '</p>
                                                      <input type=text required id=value-space name=value-space maxlength=60 placeholder="Spazio"><input type=text required id=value-type name=value-type maxlength=60 placeholder="Nome">
                                                      <br><hr><input type=text required id=member name=member maxlength=60 placeholder="Nome del membro"><input type=submit name=submit value="+">';
                                                }
                                          }
                                          echo '</ul></form></section>';
                                          if ($error == 'valuenotfound') {
                                                echo '<section>Tipo non trovato.</section>';
                                          } else if ($error == 'valuevisibility') {
                                                echo '<section>Un membro pubblico di un tipo pubblico non può dipendere da un tipo privato.</section>';
                                          } else if ($error == 'duplicate') {
                                                echo '<section>Questo nome è già utilizzato da un metodo ereditato.</section>';
                                          }
                                          editor($can_edit, $conn, $error, $space, $type);
                                    } else {
                                          echo 'Elemento non trovato.';
                                    }
                              }
                        } else {
                              /* VISUALE SPAZIO */
                              $exists = FALSE;
                              try {
                                    $stmt = $conn->prepare('SELECT * FROM Spazi WHERE Nome = ?');
                                    $stmt->bind_param('s', $space);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $exists = $result->num_rows == 1;
                              } catch (Exception $e) {
                                    $error = 'connection';
                              }
                              echo '<section><div><h3 class=code><a href="./internal.php">&#8962;</a>&nbsp;/&nbsp;' . htmlentities($space) . '</h3></div></section>';
                              if ($exists) {
                                    if (isset($_POST['submit'])
                                    && isset($_POST['type']) && mb_strlen($_POST['type']) <= 60
                                    &&    (($_POST['category'] == 'Implementazione' && !isset($_POST['visibility']))
                                          || (in_array($_POST['category'], ['Interfaccia', 'Enumerazione']) && isset($_POST['visibility']) && in_array($_POST['visibility'], ['Privata', 'Pubblica']))
                                          )
                                    ) {
                                          try {
                                                $stmt = $conn->prepare('INSERT INTO Tipi VALUES (?, ?, ?, ?)');
                                                $public = isset($_POST['visibility']) && $_POST['visibility'] == 'Pubblica';
                                                $stmt->bind_param('ssss', $_POST['type'], $space, $_POST['category'], $public);
                                                $stmt->execute();
                                          } catch (Exception $e) {
                                                $error = 'connection';
                                          }
                                    }
                                    if (isset($_GET['delete'])) {
                                          try {
                                                $stmt = $conn->prepare('DELETE FROM Tipi WHERE Spazio = ? AND Nome = ?');
                                                $stmt->bind_param('ss', $space, $_GET['delete']);
                                                $stmt->execute();
                                          } catch (Exception $e) {
                                                $error = 'connection';
                                          }
                                    }
                                    echo '<section><h2>Elenco tipi:</h2><ul>';
                                    try {
                                          $stmt = $conn->prepare('SELECT Nome, Categoria, Pubblico FROM Tipi WHERE Spazio = ?');
                                          $stmt->bind_param('s', $space);
                                          $stmt->execute();
                                          $result = $stmt->get_result();
                                          if ($result->num_rows > 0) {
                                                $display = array('Interfaccia' => 'intf', 'Implementazione' => 'impl', 'Enumerazione' => 'enum');
                                                while ($row = $result->fetch_assoc()) {
                                                      echo '<li><span class="code' . ($row['Categoria'] == 'Implementazione' ? ' gray' : '') . '">' . ($row['Pubblico'] ? 'publ' : 'priv') . ' </span>';
                                                      echo '<span class=code>' . $display[$row['Categoria']] . ' <a href="./internal.php?space=' . urlencode($space) . '&type=' . urlencode($row['Nome']) . '">' . htmlentities($row['Nome']) . '</a>';
                                                      if ($can_edit) {
                                                            echo '&nbsp;|&nbsp;<a href="./internal.php?space=' . urlencode($space) . '&delete=' . urlencode($row['Nome']) . '" title="Elimina tipo">&#128465;</a>';
                                                      }
                                                      echo '</span></li>';
                                                }
                                          } else {
                                                echo 'In questo spazio non ci sono tipi.';
                                          }
                                    } catch (Exception $e) {
                                          $error = 'connection';
                                    }
                                    if ($can_edit) {
                                          echo '<h3 style="margin: 15px 0 5px 0;">Inserisci nuovo:</h3><li><form method=POST action="./internal.php?space=' . urlencode($space) . '">' .
                                                '<select id=visibility name=visibility>
                                                      <option>Pubblica</option>
                                                      <option>Privata</option>
                                                </select>' .
                                                '<select id=category name=category onload="categoryChanged(this, \'type\')" onchange="categoryChanged(this, \'type\')">
                                                      <option>Interfaccia</option>
                                                      <option>Implementazione</option>
                                                      <option>Enumerazione</option>
                                                </select>' .
                                                '<input type=text required id=type name=type maxlength=60 placeholder="Nome"><input type=submit name=submit value="+"></form>';
                                    }
                                    echo '</ul></section>';
                                    editor($can_edit, $conn, $error, $space);
                              } else {
                                    echo 'Elemento non trovato.';
                              }
                        }
                  } else {
                        /* HOME (ELENCO SPAZI) */
                        echo '<section><div><h3 class=code>&#8962;</h3></div></section>';
                        if (isset($_POST['submit'])
                        && isset($_POST['space']) && mb_strlen($_POST['space']) <= 60
                        ) {
                              try {
                                    $stmt = $conn->prepare('INSERT INTO Spazi VALUES (?)');
                                    $stmt->bind_param('s', $_POST['space']);
                                    $stmt->execute();
                              } catch (Exception $e) {
                                    $error = 'connection';
                              }
                        }
                        if (isset($_GET['delete'])) {
                              try {
                                    $stmt = $conn->prepare('DELETE FROM Spazi WHERE Nome = ?');
                                    $stmt->bind_param('s', $_GET['delete']);
                                    $stmt->execute();
                              } catch (Exception $e) {
                                    $error = 'connection';
                              }
                        }
                        echo '<h2>Elenco spazi:</h2><ul>';
                        try {
                              $result = $conn->query('SELECT Nome FROM Spazi');
                              if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                          echo '<li><span class=code><a href="./internal.php?space=' . urlencode($row['Nome']) . '">' . htmlentities($row['Nome']) . '</a>';
                                          if ($is_admin) {
                                                echo '&nbsp;|&nbsp;<a href="./internal.php?delete=' . urlencode($row['Nome']) . '" title="Elimina spazio">&#128465;</a>';
                                          }
                                          echo '</span></li>';
                                    }
                              } else {
                                    echo 'Non ci sono spazi.';
                              }
                        } catch (Exception $e) {
                              $error = 'connection';
                        }
                        if ($is_admin) {
                              echo '<h3 style="margin: 15px 0 5px 0;">Inserisci nuovo:</h3><li><form method=POST action="./internal.php">' .
                                    '<input type=text required id=space name=space maxlength=60 placeholder="...">' .
                                    '<input type=submit name=submit value="+">
                              </form></li>';
                        }
                        echo '</ul>';
                  }
                  if ($error == 'connection') {
                        echo '<section class=reverse>Si è verificato un errore.</section>';
                  }
            ?>
      </body>
</html>
