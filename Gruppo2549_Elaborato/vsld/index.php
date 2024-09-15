<?php
      mb_internal_encoding('UTF-8');
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
      function myhtml($str) {
            return str_replace("\n", '<br>', htmlentities($str));
      }
      function display($conn, &$error, $do_section, $limit, $space, $type = NULL, $member = NULL, $parameter = NULL) {
            try {
                  $result = nullable_query($conn, $error, 'SELECT Codice, Testo FROM Blocchi WHERE Spazio = ?',
                        [$space], ['Tipo' => $type, 'Membro' => $member, 'Parametro' => $parameter]);
                  if ($result->num_rows > 0) {
                        if ($do_section) {
                              echo '<section>';
                        }
                        $row = $result->fetch_assoc();
                        $id = $row['Codice'];
                        if ($limit === NULL) {
                              $text = str_replace("\r\n", "\n", $row['Testo']);
                        } else {
                              $text = str_replace("\r\n", " ", $row['Testo']);
                              if (mb_strlen($text) > $limit) {
                                    $text = mb_substr($text, 0, $limit - 3) . '...';
                              }
                        }
                        $stmt = $conn->prepare('SELECT Inizio, Lunghezza, Spazio, Tipo, Membro, Parametro FROM Riferimenti WHERE Blocco = ?');
                        $stmt->bind_param('s', $id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $cursor = 0;
                        while ($row = $result->fetch_assoc()) {
                              echo myhtml(mb_substr($text, $cursor, $row['Inizio'] - $cursor));
                              $url = './index.php?space=' . urlencode($row['Spazio']) .
                                    ($row['Tipo'] === null ? '' : '&type=' . urlencode($row['Tipo'])) .
                                    ($row['Membro'] === null ? '' : '&member=' . urlencode($row['Membro'])) .
                                    ($row['Parametro'] === null ? '' : '&parameter=' . urlencode($row['Parametro']));
                              echo '<a href="' . $url . '">';
                              echo myhtml(mb_substr($text, $row['Inizio'], $row['Lunghezza']));
                              echo '</a>';
                              $cursor = $row['Inizio'] + $row['Lunghezza'];
                        }
                        echo myhtml(mb_substr($text, $cursor));
                        if ($do_section) {
                              echo '</section>';
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
            <title>VSLD</title>
            <script type="text/javascript">
                  document.addEventListener('DOMContentLoaded', () => {
                        const focus = document.getElementById('focus');
                        if (focus) {
                              focus.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center',
                                    inline: 'nearest'
                              });
                        }
                  });
            </script>
      </head>
      <body>
            <section>
                  <h1>VSLD</h1>
            </section>
            <section>
                  <a href="./internal.php">Accedi alla visuale interna</a>
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
                        if (isset($_GET['type'])) {
                              $type = $_GET['type'];
                              /* VISUALE TIPO */
                              $extends = FALSE;
                              $type_details = NULL;
                              try {
                                    $stmt = $conn->prepare('SELECT Categoria FROM Tipi WHERE Spazio = ? AND Nome = ? AND Pubblico = TRUE');
                                    $stmt->bind_param('ss', $space, $type);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    if ($result->num_rows == 1) {
                                          $type_details = $result->fetch_assoc();
                                    }
                              } catch (Exception $e) {
                                    $error = 'connection';
                              }
                              echo '<section><div><h3 class=code><a href="./index.php">&#8962;</a>&nbsp;/&nbsp;<a href="./index.php?space=' . urlencode($space) . '">' . htmlentities($space) . '</a>&nbsp;/&nbsp;' . htmlentities($type) . '</h3></div></section>';
                              if ($type_details !== NULL) {
                                    $display = array('Interfaccia' => 'intf', 'Enumerazione' => 'enum');
                                    echo '<section><span class=code style="font-size: 24px;"> ' .
                                          $display[$type_details['Categoria']] . ' ' . htmlentities($type) . '</span></section>';
                                    display($conn, $error, TRUE, NULL, $space, $type);
                                    if ($type_details['Categoria'] == 'Interfaccia') {
                                          try {
                                                $stmt = $conn->prepare('SELECT TipoPadre, SpazioPadre FROM Ereditarietà WHERE SpazioFiglio = ? AND TipoFiglio = ?');
                                                $stmt->bind_param('ss', $space, $type);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                if ($result->num_rows > 0) {
                                                      $extends = TRUE;
                                                      echo '<section><h3>Interfacce estese:<span class=code> ';
                                                      $i = 0;
                                                      while ($row = $result->fetch_assoc()) {
                                                            echo '<a href="./index.php?space=' . urlencode($row['SpazioPadre']) . '&type=' . urlencode($row['TipoPadre']) . '">' . htmlentities($row['TipoPadre']) . '</a>';
                                                            if (++$i < $result->num_rows) {
                                                                  echo ', ';
                                                            }
                                                      }
                                                      echo '</span>.</h3></section>';
                                                }
                                          } catch (Exception $e) {
                                                $error = 'connection';
                                          }
                                          try {
                                                $stmt = $conn->prepare('SELECT TipoFiglio, SpazioFiglio FROM Ereditarietà, Tipi WHERE SpazioPadre = ? AND TipoPadre = ? AND Spazio = SpazioFiglio AND Nome = TipoFiglio AND Pubblico = TRUE');
                                                $stmt->bind_param('ss', $space, $type);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                if ($result->num_rows > 0) {
                                                      echo '<section><h3>Interfacce derivate:<span class=code> ';
                                                      $i = 0;
                                                      while ($row = $result->fetch_assoc()) {
                                                            echo '<a href="./index.php?space=' . urlencode($row['SpazioFiglio']) . '&type=' . urlencode($row['TipoFiglio']) . '">' . htmlentities($row['TipoFiglio']) . '</a>';
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
                                          $stmt = $conn->prepare('SELECT Nome, Categoria, Tipo, Spazio FROM Membri WHERE SpazioPadre = ? AND TipoPadre = ? AND Pubblico = TRUE');
                                          $stmt->bind_param('ss', $space, $type);
                                          $stmt->execute();
                                          $result = $stmt->get_result();
                                          if ($result->num_rows > 0) {
                                                $display = array('Metodo' => 'method', 'MetodoAstratto' => 'abstract method', 'MetodoStatico' => 'static method', 'Costante' => 'const', 'Valore' => 'val');
                                                while ($row = $result->fetch_assoc()) {
                                                      $parameters = [];
                                                      $params = '';
                                                      if (in_array($row['Categoria'], ['Metodo', 'MetodoStatico', 'MetodoAstratto'])) {
                                                            $params = ' (';
                                                            $stmt = $conn->prepare('SELECT Nome, Spazio, Tipo FROM Parametri WHERE SpazioFunzione = ? AND TipoFunzione = ? AND MembroFunzione = ? ORDER BY Indice');
                                                            $stmt->bind_param('sss', $space, $type, $row['Nome']);
                                                            $stmt->execute();
                                                            $presult = $stmt->get_result();
                                                            $i = 0;
                                                            while ($prow = $presult->fetch_assoc()) {
                                                                  $parameters[] = $prow['Nome'];
                                                                  $params .= '<a  href="./index.php?space=' . urlencode($prow['Spazio']) . '&type=' . urlencode($prow['Tipo']) . '">' . htmlentities($prow['Tipo']) . '</a> ' . htmlentities($prow['Nome']);
                                                                  if (++$i < $presult->num_rows) {
                                                                        $params .= ', ';
                                                                  }
                                                            }
                                                            $params .= ')';
                                                      }
                                                      echo '<li><section><span class=code>' . $display[$row['Categoria']] . ' ';
                                                      if ($row['Categoria'] != 'Enumerazione') {
                                                            echo '<a href="./index.php?space=' . urlencode($row['Spazio']) . '&type=' . urlencode($row['Tipo']) . '">' . htmlentities($row['Tipo']) . '</a> ';
                                                      }
                                                      if (isset($_GET['member']) && $_GET['member'] == $row['Nome'] && !isset($_GET['parameter'])) {
                                                            echo '<span id=focus>' . htmlentities($row['Nome']) . '</span>' . $params . '</span></b>&emsp;';
                                                      } else {
                                                            echo htmlentities($row['Nome']) . $params . '</span></section></li>';
                                                      }
                                                      display($conn, $error, TRUE, NULL, $space, $type, $row['Nome']);
                                                      if (count($parameters) > 0) {
                                                            echo '<section><h3>Parametri:</h3><ol>';
                                                            foreach ($parameters as $parameter) {
                                                                  echo '<li><b><span class=code style="display: inline-block; min-width: 120px;">';
                                                                  if (isset($_GET['member']) && $_GET['member'] == $row['Nome'] && isset($_GET['parameter']) && $_GET['parameter'] == $parameter) {
                                                                        echo '<span id=focus>' . htmlentities($parameter) . '</span></span></b>&emsp;';
                                                                  } else {
                                                                        echo htmlentities($parameter) . '</span></b>&emsp;';
                                                                  }
                                                                  display($conn, $error, FALSE, NULL, $space, $type, $row['Nome'], $parameter);
                                                                  echo '</li>';
                                                            }
                                                            echo '</ol></section>';
                                                      }
                                                }
                                          } else {
                                                echo 'Questo tipo non ha membri.';
                                          }
                                    } catch (Exception $e) {
                                          $error = 'connection';
                                    }
                                    if ($extends) {
                                          echo '<h3 style="margin: 15px 0 5px 0;">Metodi ereditati:</h3>';
                                          try {
                                                $stmt = $conn->prepare('SELECT Nome, TipoAvo, SpazioAvo, Tipo, Spazio FROM MetodiEreditati WHERE SpazioFiglio = ? AND TipoFiglio = ?');
                                                $stmt->bind_param('ss', $space, $type);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                if ($result->num_rows > 0) {
                                                      while ($row = $result->fetch_assoc()) {
                                                            echo '<li><span class="code gray">method </span><span class=code><a href="./index.php?space=' . urlencode($row['Spazio']) . '&type=' . urlencode($row['Tipo']) . '">' . htmlentities($row['Tipo']) . '</a> ' . htmlentities($row['Nome']) . ' (';
                                                            $stmt = $conn->prepare('SELECT Nome, Spazio, Tipo FROM Parametri WHERE SpazioFunzione = ? AND TipoFunzione = ? AND MembroFunzione = ? ORDER BY Indice');
                                                            $stmt->bind_param('sss', $row['SpazioAvo'], $row['TipoAvo'], $row['Nome']);
                                                            $stmt->execute();
                                                            $presult = $stmt->get_result();
                                                            $i = 0;
                                                            while ($prow = $presult->fetch_assoc()) {
                                                                  echo '<a href="./index.php?space=' . urlencode($prow['Spazio']) . '&type=' . urlencode($prow['Tipo']) . '">' . htmlentities($prow['Tipo']) . '</a> ' . htmlentities($prow['Nome']);
                                                                  if (++$i < $presult->num_rows) {
                                                                        echo ', ';
                                                                  }
                                                            }
                                                            echo ')</span><span class=gray>&emsp;da&emsp;<a class=code href="./index.php?space=' . urlencode($row['SpazioAvo']) . '&type=' . urlencode($row['TipoAvo']) . '">' . htmlentities($row['TipoAvo']) . '</a></span></li>';
                                                      }
                                                } else {
                                                      echo 'Questo tipo non eredita metodi.';
                                                }
                                          } catch (Exception $e) {
                                                $error = 'connection';
                                          }
                                    }
                                    echo '</ul></section>';
                              } else {
                                    echo 'Elemento non trovato.';
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
                              echo '<section><div><h3 class=code><a href="./index.php">&#8962;</a>&nbsp;/&nbsp;' . htmlentities($space) . '</h3></div></section>';
                              echo '<section><span class=code style="font-size: 24px;">' . htmlentities($space) . '</span></section>';
                              if ($exists) {
                                    display($conn, $error, TRUE, NULL, $space);
                                    echo '<h2>Elenco tipi:</h2><ul>';
                                    try {
                                          $stmt = $conn->prepare('SELECT Nome, Categoria FROM Tipi WHERE Spazio = ? AND Pubblico = TRUE');
                                          $stmt->bind_param('s', $space);
                                          $stmt->execute();
                                          $result = $stmt->get_result();
                                          if ($result->num_rows > 0) {
                                                $display = array('Interfaccia' => 'intf', 'Enumerazione' => 'enum');
                                                while ($row = $result->fetch_assoc()) {
                                                      echo '<li><section><span class=code style="display:inline-block; min-width: 200px;">' . $display[$row['Categoria']] . ' <a href="./index.php?space=' . urlencode($space) . '&type=' . urlencode($row['Nome']) . '">' . htmlentities($row['Nome']) . '</a></span>&emsp;';
                                                      display($conn, $error, FALSE, 70, $space, $row['Nome']);
                                                      echo '</section></li>';
                                                }
                                          } else {
                                                echo 'In questo spazio non ci sono tipi.';
                                          }
                                    } catch (Exception $e) {
                                          $error = 'connection';
                                    }
                                    echo '</ul>';
                              } else {
                                    echo 'Elemento non trovato.';
                              }
                        }
                  } else {
                        /* HOME (ELENCO SPAZI) */
                        echo '<section><div><h3 class=code>&#8962;</h3></div></section>';
                        echo '<h2>Elenco spazi:</h2><ul>';
                        try {
                              $result = $conn->query('SELECT Nome FROM Spazi');
                              if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                          echo '<li><section><span class=code style="display:inline-block; min-width: 200px;"><a href="./index.php?space=' . urlencode($row['Nome']) . '">' . htmlentities($row['Nome']) . '</a></span>&emsp;';
                                          display($conn, $error, FALSE, 70, $row['Nome']);
                                          echo '</section></li>';
                                    }
                              } else {
                                    echo 'Non ci sono spazi.';
                              }
                        } catch (Exception $e) {
                              $error = 'connection';
                        }
                        echo '</ul>';
                  }
                  if ($error == 'connection') {
                        echo '<section class=reverse>Si è verificato un errore.</section>';
                  }
            ?>
      </body>
</html>
