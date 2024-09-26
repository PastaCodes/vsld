DROP DATABASE IF EXISTS VSLD;

CREATE DATABASE VSLD;

USE VSLD;

CREATE TABLE Utenti (
    Email VARCHAR(255) PRIMARY KEY,
    NumeroBadge CHAR(5) NOT NULL UNIQUE,
    Nome VARCHAR(30) NOT NULL,
    Cognome VARCHAR(60) NOT NULL,
    Password VARCHAR(60) NOT NULL,
    Ruolo ENUM('Visualizzatore', 'Sviluppatore', 'Amministratore') NOT NULL
);

CREATE TABLE Spazi (
    Nome VARCHAR(60) PRIMARY KEY
);

CREATE TABLE Assegnazioni (
    Spazio VARCHAR(60),
    CONSTRAINT FOREIGN KEY (Spazio) REFERENCES Spazi(Nome) ON DELETE CASCADE,
    Sviluppatore VARCHAR(255),
    CONSTRAINT FOREIGN KEY (Sviluppatore) REFERENCES Utenti(Email) ON DELETE CASCADE,
    PRIMARY KEY (Spazio, Sviluppatore)
);

CREATE TABLE Tipi (
    Nome VARCHAR(60),
    Spazio VARCHAR(60),
    CONSTRAINT FOREIGN KEY (Spazio) REFERENCES Spazi(Nome) ON DELETE CASCADE,
    PRIMARY KEY (Spazio, Nome),
    Categoria ENUM('Interfaccia', 'Implementazione', 'Enumerazione') NOT NULL,
    Pubblico BOOLEAN NOT NULL,
    -- Le implementazioni sono private
    CHECK (Categoria != 'Implementazione' OR Pubblico = FALSE)
);

CREATE TABLE Ereditarietà (
    TipoPadre VARCHAR(60),
    SpazioPadre VARCHAR(60),
    CONSTRAINT FOREIGN KEY (SpazioPadre, TipoPadre) REFERENCES Tipi(Spazio, Nome) ON DELETE CASCADE,
    TipoFiglio VARCHAR(60),
    SpazioFiglio VARCHAR(60),
    CONSTRAINT FOREIGN KEY (SpazioFiglio, TipoFiglio) REFERENCES Tipi(Spazio, Nome) ON DELETE CASCADE,
    PRIMARY KEY (SpazioPadre, TipoPadre, SpazioFiglio, TipoFiglio)
);

CREATE TABLE Membri (
    Nome VARCHAR(60),
    TipoPadre VARCHAR(60),
    SpazioPadre VARCHAR(60),
    CONSTRAINT FOREIGN KEY (SpazioPadre, TipoPadre) REFERENCES Tipi(Spazio, Nome) ON DELETE CASCADE,
    PRIMARY KEY (SpazioPadre, TipoPadre, Nome),
    Categoria ENUM('Campo', 'Metodo', 'MetodoAstratto', 'MetodoStatico', 'Costante', 'Valore') NOT NULL,
    Pubblico BOOLEAN NOT NULL,
    -- I campi sono privati
    CHECK (Categoria != 'Campo' OR Pubblico = FALSE),
    -- I valori sono pubblici
    CHECK (Categoria != 'Valore' OR Pubblico = TRUE),
    Tipo VARCHAR(60),
    Spazio VARCHAR(60),
    CONSTRAINT FOREIGN KEY (Spazio, Tipo) REFERENCES Tipi(Spazio, Nome) ON DELETE CASCADE,
    -- I valori non hanno tipo, mentre tutti le altre categorie di membro sì
    CHECK ((Categoria = 'Valore' AND Tipo IS NULL AND Spazio IS NULL) OR (Categoria != 'Valore' AND Tipo IS NOT NULL AND Spazio IS NOT NULL))
);

CREATE VIEW Avi AS
WITH RECURSIVE AviTmp(TipoFiglio, SpazioFiglio, TipoAvo, SpazioAvo) AS (
    SELECT TipoFiglio, SpazioFiglio, TipoPadre, SpazioPadre
        FROM Ereditarietà
	UNION ALL
    SELECT E.TipoFiglio, E.SpazioFiglio, A.TipoAvo, A.SpazioAvo
        FROM Ereditarietà AS E, AviTmp AS A
        WHERE E.SpazioPadre = A.SpazioFiglio
        AND E.TipoPadre = A.TipoFiglio
)
SELECT *
    FROM AviTmp;

CREATE VIEW MetodiEreditati AS
SELECT *
    FROM Avi, Membri
    WHERE SpazioPadre = SpazioAvo
    AND TipoPadre = TipoAvo
    AND Categoria = 'MetodoAstratto';

CREATE TABLE Parametri (
    Nome VARCHAR(60),
    MembroFunzione VARCHAR(60),
    TipoFunzione VARCHAR(60),
    SpazioFunzione VARCHAR(60),
    CONSTRAINT FOREIGN KEY (SpazioFunzione, TipoFunzione, MembroFunzione) REFERENCES Membri(SpazioPadre, TipoPadre, Nome) ON DELETE CASCADE,
    PRIMARY KEY (SpazioFunzione, TipoFunzione, MembroFunzione, Nome),
    Indice INT NOT NULL,
    UNIQUE (SpazioFunzione, TipoFunzione, MembroFunzione, Indice),
    Tipo VARCHAR(60) NOT NULL,
    Spazio VARCHAR(60) NOT NULL,
    CONSTRAINT FOREIGN KEY (Spazio, Tipo) REFERENCES Tipi(Spazio, Nome) ON DELETE CASCADE
);

CREATE TABLE Blocchi (
    Codice INT AUTO_INCREMENT PRIMARY KEY,
    Testo VARCHAR(1000) NOT NULL DEFAULT '',
    Spazio VARCHAR(60),
    CONSTRAINT FOREIGN KEY (Spazio) REFERENCES Spazi(Nome) ON DELETE CASCADE,
    Tipo VARCHAR(60),
    CONSTRAINT FOREIGN KEY (Spazio, Tipo) REFERENCES Tipi(Spazio, Nome) ON DELETE CASCADE,
    Membro VARCHAR(60),
    CONSTRAINT FOREIGN KEY (Spazio, Tipo, Membro) REFERENCES Membri(SpazioPadre, TipoPadre, Nome) ON DELETE CASCADE,
    Parametro VARCHAR(60),
    CONSTRAINT FOREIGN KEY (Spazio, Tipo, Membro, Parametro) REFERENCES Parametri(SpazioFunzione, TipoFunzione, MembroFunzione, Nome) ON DELETE CASCADE,
    UNIQUE KEY (Spazio, Tipo, Membro, Parametro),
    UltimaModifica DATE NOT NULL DEFAULT (CURRENT_DATE)
);

CREATE TABLE Autori (
    Blocco INT,
    CONSTRAINT FOREIGN KEY (Blocco) REFERENCES Blocchi(Codice) ON DELETE CASCADE,
    Sviluppatore VARCHAR(255),
    CONSTRAINT FOREIGN KEY (Sviluppatore) REFERENCES Utenti(Email) ON DELETE CASCADE,
    PRIMARY KEY (Blocco, Sviluppatore)
);

CREATE TABLE Riferimenti (
    Blocco INT,
    CONSTRAINT FOREIGN KEY (Blocco) REFERENCES Blocchi(Codice) ON DELETE CASCADE,
    Inizio INT,
    Lunghezza INT,
    PRIMARY KEY (Blocco, Inizio),
    Spazio VARCHAR(60),
    CONSTRAINT FOREIGN KEY (Spazio) REFERENCES Spazi(Nome) ON DELETE CASCADE,
    Tipo VARCHAR(60),
    CONSTRAINT FOREIGN KEY (Spazio, Tipo) REFERENCES Tipi(Spazio, Nome) ON DELETE CASCADE,
    Membro VARCHAR(60),
    CONSTRAINT FOREIGN KEY (Spazio, Tipo, Membro) REFERENCES Membri(SpazioPadre, TipoPadre, Nome) ON DELETE CASCADE,
    Parametro VARCHAR(60),
    CONSTRAINT FOREIGN KEY (Spazio, Tipo, Membro, Parametro) REFERENCES Parametri(SpazioFunzione, TipoFunzione, MembroFunzione, Nome) ON DELETE CASCADE
);

INSERT INTO Utenti VALUES ('marco.buda@sus.com','00000','Marco','Buda','$2y$10$GVwf5qaZC/yDeAGr/oNFC.I0b4qB2xhbXzPwPmRbKz1zm3X6gECoG','Amministratore'),('mario.rossi@sus.com','00001','Mario','Rossi','$2y$10$CBWVXqTD55/BouPQI5XWX.1VYvStFX21l7IYpB.4qos0SgCpxfcEa','Sviluppatore');
INSERT INTO Spazi VALUES ('vava.collections'),('vava.native');
INSERT INTO Assegnazioni VALUES ('vava.collections','mario.rossi@sus.com');
INSERT INTO Tipi VALUES ('ArrayList','vava.collections','Implementazione',0),('Collection','vava.collections','Interfaccia',1),('List','vava.collections','Interfaccia',1),('Any','vava.native','Interfaccia',1),('Array','vava.native','Interfaccia',1),('Boolean','vava.native','Enumerazione',1),('Integer','vava.native','Interfaccia',1),('Void','vava.native','Interfaccia',1);
INSERT INTO Ereditarietà VALUES ('Collection','vava.collections','List','vava.collections'),('List','vava.collections','ArrayList','vava.collections');
INSERT INTO Membri VALUES ('count','ArrayList','vava.collections','Campo',0,'Integer','vava.native'),('elements','ArrayList','vava.collections','Campo',0,'Array','vava.native'),('empty','ArrayList','vava.collections','MetodoStatico',1,'ArrayList','vava.collections'),('grow','ArrayList','vava.collections','Metodo',0,'Void','vava.native'),('set_elements','ArrayList','vava.collections','Metodo',1,'Void','vava.native'),('add','Collection','vava.collections','MetodoAstratto',1,'Boolean','vava.native'),('contains','Collection','vava.collections','MetodoAstratto',1,'Boolean','vava.native'),('remove','Collection','vava.collections','MetodoAstratto',1,'Integer','vava.native'),('size','Collection','vava.collections','MetodoAstratto',1,'Integer','vava.native'),('array_list','List','vava.collections','MetodoStatico',1,'List','vava.collections'),('array_list_of','List','vava.collections','MetodoStatico',1,'List','vava.collections'),('get','List','vava.collections','MetodoAstratto',1,'Any','vava.native'),('index_of','List','vava.collections','MetodoAstratto',1,'Integer','vava.native'),('insert','List','vava.collections','MetodoAstratto',1,'Void','vava.native'),('remove_at','List','vava.collections','MetodoAstratto',1,'Any','vava.native'),('reverse','List','vava.collections','MetodoAstratto',1,'List','vava.collections'),('set','List','vava.collections','MetodoAstratto',1,'Void','vava.native'),('swap','List','vava.collections','MetodoStatico',0,'Void','vava.native'),('FALSE','Boolean','vava.native','Valore',1,NULL,NULL),('TRUE','Boolean','vava.native','Valore',1,NULL,NULL),('MAX_VALUE','Integer','vava.native','Costante',1,'Integer','vava.native'),('MIN_VALUE','Integer','vava.native','Costante',1,'Integer','vava.native');
INSERT INTO Parametri VALUES ('min_capacity','grow','ArrayList','vava.collections',2,'Integer','vava.native'),('elements','set_elements','ArrayList','vava.collections',1,'Array','vava.native'),('element','add','Collection','vava.collections',2,'Any','vava.native'),('element','contains','Collection','vava.collections',1,'Any','vava.native'),('element','remove','Collection','vava.collections',3,'Any','vava.native'),('elements','array_list_of','List','vava.collections',11,'Array','vava.native'),('index','get','List','vava.collections',1,'Integer','vava.native'),('element','index_of','List','vava.collections',2,'Any','vava.native'),('element','insert','List','vava.collections',6,'Any','vava.native'),('index','insert','List','vava.collections',5,'Integer','vava.native'),('index','remove_at','List','vava.collections',7,'Integer','vava.native'),('element','set','List','vava.collections',4,'Any','vava.native'),('index','set','List','vava.collections',3,'Integer','vava.native'),('i','swap','List','vava.collections',9,'Integer','vava.native'),('j','swap','List','vava.collections',10,'Integer','vava.native'),('list','swap','List','vava.collections',8,'List','vava.collections');
INSERT INTO Blocchi VALUES (41,'Rappresenta una generica collezione di elementi.\r\n\r\nCollezioni non ordinate che non ammettono elementi ripetuti sono insiemi.\r\nCollezioni ordinate che non ammettono elementi ripetuti sono insiemi ordinati.\r\nCollezioni non ordinate che ammettono elementi ripetuti sono multi-insiemi.\r\nCollezioni ordinate che ammettono elementi ripetuti sono liste.','vava.collections','Collection',NULL,NULL,'2024-09-11'),(44,'Le strutture dati avanzate offerte dal linguaggio.','vava.collections',NULL,NULL,NULL,'2024-09-11'),(45,'I tipi implementati nativamente dal linguaggio.','vava.native',NULL,NULL,NULL,'2024-09-11'),(46,'Rappresenta qualsiasi tipo di valore. Ogni tipo eredita implicitamente da questo tipo.','vava.native','Any',NULL,NULL,'2024-09-11'),(48,'Rappresenta un valore booleano, ossia può assumere i valori TRUE e FALSE.','vava.native','Boolean',NULL,NULL,'2024-09-11'),(50,'Rappresenta un numero intero con segno.','vava.native','Integer',NULL,NULL,'2024-09-11'),(51,'Utilizzato dalle funzioni che non restituiscono un valore.','vava.native','Void',NULL,NULL,'2024-09-11'),(52,'Rappresenta una collezione ordinata che ammette elementi ripetuti.','vava.collections','List',NULL,NULL,'2024-09-11'),(53,'Restituisce il numero di elementi nella collezione.','vava.collections','Collection','size',NULL,'2024-09-11'),(54,'Determina se l\'elemento specificato è presente o meno nella collezione.','vava.collections','Collection','contains',NULL,'2024-09-11'),(55,'L\'elemento da cercare.','vava.collections','Collection','contains','element','2024-09-11'),(56,'Inserisce l\'elemento specificato nella collezione.\r\nSe la collezione è ordinata, l\'elemento verrà inserito in coda.\r\nIl valore restituito determina se la collezione è effettivamente cambiata.','vava.collections','Collection','add',NULL,'2024-09-11'),(58,'Rimuove tutte le occorrenze dell\'elemento specificato dalla collezione.\r\nRestituisce il numero di elementi rimossi.','vava.collections','Collection','remove',NULL,'2024-09-11'),(59,'L\'elemento da inserire.','vava.collections','Collection','add','element','2024-09-11'),(60,'L\'elemento da rimuovere.','vava.collections','Collection','remove','element','2024-09-11'),(61,'Restituisce l\'elemento alla posizione specificata.\r\nSe la posizione non è valida, viene lanciato un errore.','vava.collections','List','get',NULL,'2024-09-11'),(62,'La posizione dell\'elemento da ottenere.','vava.collections','List','get','index','2024-09-11'),(63,'Restituisce la posizione della prima occorrenza dell\'elemento specificato.\r\nSe l\'elemento non è presente nella lista, viene lanciato un errore.','vava.collections','List','index_of',NULL,'2024-09-11'),(64,'L\'elemento da cercare.','vava.collections','List','index_of','element','2024-09-11'),(65,'Sostituisce l\'elemento alla posizione specificata con l\'elemento specificato.\r\nPermette l\'inserimento in coda, ma in tutti gli altri casi, se nessun elemento è presente alla posizione specificata, viene lanciato un errore.','vava.collections','List','set',NULL,'2024-09-11'),(66,'La posizione in cui inserire l\'elemento.','vava.collections','List','set','index','2024-09-11'),(67,'L\'elemento da inserire.','vava.collections','List','set','element','2024-09-11'),(68,'Inserisce l\'elemento specificato alla posizione specificata, spostando verso la coda gli elementi successivi.\r\nPermette l\'inserimento in coda, ma in tutti gli altri casi, una posizione non valida causerà un errore.','vava.collections','List','insert',NULL,'2024-09-11'),(69,'La posizione in cui inserire l\'elemento.','vava.collections','List','insert','index','2024-09-11'),(70,'L\'elemento da inserire.','vava.collections','List','insert','element','2024-09-11'),(71,'Rimuove e resistuisce l\'elemento alla posizione selezionata, spostando verso la testa gli elementi successivi.\r\nSe nessun elemento si trova alla posizione selezionata, viene lanciato un errore.','vava.collections','List','remove_at',NULL,'2024-09-11'),(72,'Restituisce una lista vuota, basata sull\'utilizzo di un vettore.','vava.collections','List','array_list',NULL,'2024-09-11'),(73,'Rappresenta una sequenza di elementi.\r\nUna volta istanziato, il numero di elementi non può essere modificato.','vava.native','Array',NULL,NULL,'2024-09-11'),(74,'Funzione di utility per scambiare di posto i due elementi nella lista.','vava.collections','List','swap',NULL,'2024-09-11'),(75,'Restituisce la lista invertita, senza alterare quella di partenza.','vava.collections','List','reverse',NULL,'2024-09-11'),(76,'Rappresenta una lista implementata con l\'utilizzo di un vettore.','vava.collections','ArrayList',NULL,NULL,'2024-09-11'),(77,'Il vettore utilizzato per contenere gli elementi della lista.\r\nVerrà nuovamente istanziato quando si necessita di aumentare la capienza.','vava.collections','ArrayList','elements',NULL,'2024-09-11'),(78,'Memorizza il numero di elementi all\'interno del vettore.','vava.collections','ArrayList','count',NULL,'2024-09-11'),(79,'Permette di modificare l\'intera lista, scartando gli elementi precedenti e inserendo quelli specificati.','vava.collections','ArrayList','set_elements',NULL,'2024-09-11'),(80,'I nuovi elementi della lista.','vava.collections','ArrayList','set_elements','elements','2024-09-11'),(81,'Crea e restituisce una nuova lista, basata su un vettore, che contenga gli elementi specificati.','vava.collections','List','array_list_of',NULL,'2024-09-11'),(82,'Gli elementi da inserire nella nuova lista.','vava.collections','List','array_list_of','elements','2024-09-11'),(83,'Accresce il vettore di supporto in modo che possa contenere il numero di elementi specificato.','vava.collections','ArrayList','grow',NULL,'2024-09-11'),(84,'Capacità minima che dovrà avere il vettore di supporto.','vava.collections','ArrayList','grow','min_capacity','2024-09-11'),(85,'Crea e restituisce una lista vuota.','vava.collections','ArrayList','empty',NULL,'2024-09-11'),(86,'Il valore massimo rappresentabile da un intero.','vava.native','Integer','MAX_VALUE',NULL,'2024-09-11'),(87,'Il valore minimo (avente segno negativo e modulo massimo) rappresentabile da un intero.','vava.native','Integer','MIN_VALUE',NULL,'2024-09-11');
INSERT INTO Riferimenti VALUES (41,336,5,'vava.collections','List',NULL,NULL),(48,60,4,'vava.native','Boolean','TRUE',NULL),(48,67,5,'vava.native','Boolean','FALSE',NULL),(52,16,10,'vava.collections','Collection',NULL,NULL),(54,15,20,'vava.collections','Collection','contains','element'),(56,12,20,'vava.collections','Collection','add','element'),(58,33,20,'vava.collections','Collection','remove','element'),(61,28,21,'vava.collections','List','get','index'),(63,53,20,'vava.collections','List','index_of','element'),(65,28,21,'vava.collections','List','set','index'),(65,56,20,'vava.collections','List','set','element'),(68,12,20,'vava.collections','List','insert','element'),(68,38,21,'vava.collections','List','insert','index'),(71,38,21,'vava.collections','List','remove_at','index'),(72,56,7,'vava.native','Array',NULL,NULL),(76,16,5,'vava.collections','List',NULL,NULL),(76,56,7,'vava.native','Array',NULL,NULL),(78,48,7,'vava.collections','ArrayList','elements',NULL),(79,85,18,'vava.collections','ArrayList','set_elements','elements'),(81,49,7,'vava.native','Array',NULL,NULL),(81,75,20,'vava.collections','List','array_list_of','elements'),(83,12,19,'vava.collections','ArrayList','elements',NULL),(83,63,30,'vava.collections','ArrayList','grow','min_capacity'),(86,40,6,'vava.native','Integer',NULL,NULL),(87,80,6,'vava.native','Integer',NULL,NULL);