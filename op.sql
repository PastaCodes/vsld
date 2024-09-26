---------- ASSIGN ----------

-- OP42
-- Aggiungere assegnazione
INSERT INTO Assegnazioni VALUES (?, ?);

-- OP43
-- Rimuovere assegnazione
DELETE FROM Assegnazioni
      WHERE Spazio = ? AND Sviluppatore = ?;

-- OP40
-- Elenco sviluppatori assegnati a uno spazio
SELECT Nome, Cognome, Email, COUNT(Spazio) AS Numero
      FROM Utenti LEFT JOIN Assegnazioni
      ON Sviluppatore = Email
      WHERE Ruolo = 'Sviluppatore'
      AND Email IN (SELECT Sviluppatore FROM assegnazioni WHERE Spazio = ?)
      GROUP BY Email
      ORDER BY Numero DESC;
-- Elenco sviluppatori assegnabili a uno spazio
SELECT Nome, Cognome, Email, COUNT(Spazio) AS Numero
      FROM Utenti LEFT JOIN Assegnazioni
      ON Sviluppatore = Email
      WHERE Ruolo = 'Sviluppatore'
      AND Email NOT IN (SELECT Sviluppatore FROM Assegnazioni WHERE Spazio = ?)
      GROUP BY Email
      ORDER BY Numero;

-- OP41
-- Elenco spazi assegnati a uno sviluppatore
SELECT Nome, COUNT(Sviluppatore) AS Numero
      FROM Spazi LEFT JOIN Assegnazioni
      ON Spazio = Nome
      WHERE Nome IN (SELECT Spazio FROM Assegnazioni WHERE Sviluppatore = ?)
      GROUP BY Nome
      ORDER BY Numero DESC;
-- Elenco spazi assegnabili a uno sviluppatore
SELECT Nome, COUNT(Sviluppatore) AS Numero
      FROM Spazi LEFT JOIN Assegnazioni
      ON Spazio = Nome
      WHERE Nome NOT IN (SELECT Spazio FROM Assegnazioni WHERE Sviluppatore = ?)
      GROUP BY Nome
      ORDER BY Numero;

-- OP39
-- Elenco spazi
SELECT Nome, COUNT(Sviluppatore) AS Numero
      FROM Spazi LEFT JOIN Assegnazioni
      ON Spazio = Nome
      GROUP BY Nome
      ORDER BY Numero DESC;
-- Elenco sviluppatori
SELECT Nome, Cognome, Email, COUNT(Spazio) AS Numero
      FROM Utenti LEFT JOIN Assegnazioni
      ON Sviluppatore = Email
      WHERE Ruolo = 'Sviluppatore'
      GROUP BY Email
      ORDER BY Numero DESC;

--------- INDEX ----------

-- OP1
-- Visualizzazione documentazione
SELECT Codice, Testo
      FROM Blocchi
      WHERE Spazio = ? AND Tipo = ? AND Membro = ? AND Parametro = ?;
SELECT Inizio, Lunghezza, Spazio, Tipo, Membro, Parametro
      FROM Riferimenti
      WHERE Blocco = ?;

-- OP2
-- Visuale iniziale
SELECT Nome FROM Spazi;
-- DOC * n_spazi

-- OP3
-- Ricerca spazio
SELECT * FROM Spazi WHERE Nome = ?;

-- OP4
-- Elenco tipi pubblici
SELECT Nome, Categoria
      FROM Tipi
      WHERE Spazio = ?
      AND Pubblico = TRUE;
-- DOC * (1 + n_tipi_publ)

-- Visuale tipo
-- OP5
-- Verifico che esista e sia pubblico
SELECT Categoria
      FROM Tipi
      WHERE Spazio = ?
      AND Nome = ?
      AND Pubblico = TRUE;
-- OP6
-- Interfacce estese
SELECT TipoPadre, SpazioPadre
      FROM Ereditarietà
      WHERE SpazioFiglio = ? AND TipoFiglio = ?;
-- OP7
-- Interfacce pubbliche derivate
SELECT TipoFiglio, SpazioFiglio
      FROM Ereditarietà, Tipi
      WHERE SpazioPadre = ? AND TipoPadre = ?
      AND Spazio = SpazioFiglio AND Nome = TipoFiglio
      AND Pubblico = TRUE;
-- OP8
-- Elenco membri pubblici
SELECT Nome, Categoria, Tipo, Spazio
      FROM Membri
      WHERE SpazioPadre = ? AND TipoPadre = ?
      AND Pubblico = TRUE;
-- OP10
-- Elenco parametri (per ogni membro)
SELECT Nome, Spazio, Tipo
      FROM Parametri
      WHERE SpazioFunzione = ? AND TipoFunzione = ? AND MembroFunzione = ?
      ORDER BY Indice;
-- OP9
-- Elenco metodi ereditati
SELECT Nome, TipoAvo, SpazioAvo, Tipo, Spazio
      FROM MetodiEreditati
      WHERE SpazioFiglio = ? AND TipoFiglio = ?;
-- OP10
-- Elenco parametri (per ogni metodo ereditato)
SELECT Nome, Spazio, Tipo
      FROM Parametri
      WHERE SpazioFunzione = ? AND TipoFunzione = ? AND MembroFunzione = ?
      ORDER BY Indice;
-- DOC * (1 + n_membri_publ_nonf + n_param * (n_membri_publ_f + n_metodi_ereditati))

---------- INTERNAL ----------

-- OP1
-- Visualizzazione documentazione
SELECT Codice, Testo, UltimaModifica
      FROM Blocchi
      WHERE Spazio = ? AND Tipo = ? AND Membro = ? AND Parametro = ?;
SELECT Inizio, Lunghezza, Spazio, Tipo, Membro, Parametro
      FROM Riferimenti
      WHERE Blocco = ?;
-- OP11
SELECT Sviluppatore
      FROM Autori
      WHERE Blocco = ?;
-- OP12
-- Elenco sviluppatori che possono essere inseriti come autori (solo admin)
SELECT Email
      FROM Utenti
      WHERE Ruolo = 'Sviluppatore'
      AND Email NOT IN (SELECT Sviluppatore FROM Autori WHERE Blocco = ?);
-- OP17
-- Elenco riferimenti a questo elemento
SELECT B.Testo, R.Inizio, R.Lunghezza, B.Spazio, B.Tipo, B.Membro, B.Parametro
      FROM Riferimenti AS R, Blocchi AS B
      WHERE B.Codice = R.Blocco
      AND R.Spazio = ? AND R.Tipo = ? AND R.Membro = ? AND R.Parametro = ?;

-- OP15
-- Aggiunta blocco vuoto
INSERT INTO Blocchi(Spazio, Tipo, Membro, Parametro) VALUES (?, ?, ?, ?);

-- OP16
-- Modifica documentazione
-- Modifica testo e data
UPDATE Blocchi
      SET Testo = ?, UltimaModifica = CURRENT_DATE
      WHERE Codice = ?;
-- Rimozione vecchi riferimenti
DELETE FROM Riferimenti
      WHERE Blocco = ?;
-- Inserimento nuovo riferimento (per ogni riferimento)
INSERT INTO Riferimenti VALUES (?, ?, ?, ?, ?, ?, ?);

-- OP13
-- Aggiunta autore
INSERT INTO Autori VALUES (?, ?);

-- OP14
-- Rimozione autore
DELETE FROM Autori
      WHERE Blocco = ? AND Sviluppatore = ?;

-- OP20
-- Verifica permessi (per approssimazione considero che venga eseguita sempre)
SELECT * FROM Assegnazioni
      WHERE Spazio = ? AND Sviluppatore = ?;

-- OP24
-- Dettagli tipo (eseguita anche in visuale membro)
SELECT Categoria, Pubblico
      FROM Tipi
      WHERE Spazio = ? AND Nome = ?;

-- OP35
-- Dettagli parametro
SELECT * FROM Parametri
      WHERE SpazioFunzione = ? AND TipoFunzione = ? AND MembroFunzione = ? AND Nome = ?;

-- OP32
-- Dettagli membro
SELECT Categoria, Pubblico
      FROM Membri
      WHERE SpazioPadre = ? AND TipoPadre = ? AND Nome = ?;

-- OP10
-- Elenco parametri
SELECT Nome, Tipo, Spazio
      FROM Parametri
      WHERE SpazioFunzione = ? AND TipoFunzione = ? AND MembroFunzione = ?
      ORDER BY Indice;

-- OP33
-- Inserimento parametro
SELECT Pubblico
      FROM Tipi
      WHERE Spazio = ? AND Nome = ?;
SELECT MAX(Indice) AS Massimo
      FROM Parametri
      WHERE SpazioFunzione = ? AND TipoFunzione = ?;
INSERT INTO Parametri VALUES (?, ?, ?, ?, ?, ?, ?);

-- OP34
-- Rimozione parametro
DELETE FROM Parametri
      WHERE SpazioFunzione = ? AND TipoFunzione = ? AND MembroFunzione = ? AND Nome = ?;

-- OP28
-- Elenco membri in ordine alfabetico
SELECT Nome, Categoria, Pubblico, Tipo, Spazio
      FROM Membri
      WHERE SpazioPadre = ? AND TipoPadre = ?
      ORDER BY Nome;

-- OP29
-- Elenco membri ordinati per numero di riferimenti
SELECT M.Nome, M.Categoria, M.Pubblico, M.Tipo, M.Spazio, COUNT(R.Blocco) AS Numero
      FROM Membri AS M LEFT JOIN Riferimenti AS R
      ON R.Spazio = M.SpazioPadre
      AND R.Tipo = M.TipoPadre
      AND R.Membro = M.Nome
      AND R.Parametro IS NULL
      WHERE M.SpazioPadre = ?
      AND M.TipoPadre = ?
      GROUP BY M.Nome
      ORDER BY Numero DESC, M.Nome;

-- OP30
-- Inserimento membro
-- Verifico non ci siano collisioni con metodi ereditati
SELECT * FROM MetodiEreditati
      WHERE SpazioFiglio = ? AND TipoFiglio = ? AND Nome = ?;
-- Verifico che il tipo esista e non ci siano problemi di visibilità
SELECT Pubblico
      FROM Tipi
      WHERE Spazio = ? AND Nome = ?;
-- Inserisco
INSERT INTO Membri VALUES (?, ?, ?, ?, ?, ?, ?);

-- OP31
-- Rimozione membro
DELETE FROM Membri
      WHERE SpazioPadre = ? AND TipoPadre = ? AND Nome = ?;

-- OP6
-- Elenco interfacce estese/implementate
SELECT TipoPadre, SpazioPadre
      FROM Ereditarietà
      WHERE SpazioFiglio = ? AND TipoFiglio = ?;

-- OP25
-- Elenco tipi derivati (solo per interfacce)
SELECT TipoFiglio, SpazioFiglio
      FROM Ereditarietà WHERE SpazioPadre = ? AND TipoPadre = ?;

-- OP26
-- Aggiunta ereditarietà
-- Verifico che sia un'interfaccia visibile
SELECT Categoria, Pubblico
      FROM Tipi
      WHERE Spazio = ? AND Nome = ?;
-- Verifico che non ci siano cicli
SELECT * FROM Avi
      WHERE SpazioAvo = ? AND TipoAvo = ? AND SpazioFiglio = ? AND TipoFiglio = ?;
-- (BKP) Verifico che non provochi collisioni (BKP)
SELECT * FROM Membri
      WHERE SpazioPadre = ? AND TipoPadre = ?
      AND Nome IN (
            SELECT Nome FROM MetodiEreditati WHERE SpazioFiglio = ? AND TipoFiglio = ?
            UNION ALL
            SELECT Nome FROM Membri WHERE SpazioPadre = ? AND TipoPadre = ? AND Categoria = 'MetodoAstratto'
      );
-- Verifico che non provochi collisioni
WITH TuttiMembri(Nome, Tipo, Spazio) AS (
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
-- Inserisco
INSERT INTO Ereditarietà VALUES (?, ?, ?, ?);

-- OP27
-- Rimozione ereditarietà
DELETE FROM Ereditarietà
      WHERE SpazioFiglio = ? AND TipoFiglio = ? AND SpazioPadre = ? AND TipoPadre = ?;

-- OP3
-- Verifica esistenza spazio
SELECT * FROM Spazi
      WHERE Nome = ?;

-- OP21
-- Elenco tipi
SELECT Nome, Categoria, Pubblico
      FROM Tipi
      WHERE Spazio = ?;

-- OP22
-- Inserimento tipo
INSERT INTO Tipi VALUES (?, ?, ?, ?);

-- OP23
-- Rimozione tipo
DELETE FROM Tipi WHERE Spazio = ? AND Nome = ?;

-- OP2
-- Elenco spazi
SELECT Nome FROM Spazi;

-- OP18
-- Inserimento spazio
INSERT INTO Spazi VALUES (?);

-- OP19
-- Rimozione spazio
DELETE FROM Spazi WHERE Nome = ?;

---------- LOGIN/PASSWORD ----------

-- OP44
-- Accesso
SELECT Nome, Cognome, Password, Ruolo
      FROM Utenti WHERE Email = ?;

-- OP45
-- Modifica password
UPDATE Utenti SET Password = ?
      WHERE Email = ?;

---------- USERS ----------

-- OP36
-- Elenco utenti
SELECT Email, NumeroBadge, Nome, Cognome, Ruolo
      FROM Utenti;

-- OP37
-- Inserimento utente
INSERT INTO Utenti VALUES (?, ?, ?, ?, ?, ?);

-- OP38
-- Rimozione utente
DELETE FROM Utenti WHERE Email = ?;
