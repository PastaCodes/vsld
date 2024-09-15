-- MySQL dump 10.13  Distrib 8.0.38, for Win64 (x86_64)
--
-- Host: localhost    Database: vsld
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

DROP DATABASE IF EXISTS vsld;

CREATE DATABASE IF NOT EXISTS vsld;

USE vsld;

--
-- Table structure for table `assegnazioni`
--

DROP TABLE IF EXISTS `assegnazioni`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assegnazioni` (
  `Spazio` varchar(60) NOT NULL,
  `Sviluppatore` varchar(255) NOT NULL,
  PRIMARY KEY (`Spazio`,`Sviluppatore`),
  KEY `Sviluppatore` (`Sviluppatore`),
  CONSTRAINT `assegnazioni_ibfk_1` FOREIGN KEY (`Spazio`) REFERENCES `spazi` (`Nome`) ON DELETE CASCADE,
  CONSTRAINT `assegnazioni_ibfk_2` FOREIGN KEY (`Sviluppatore`) REFERENCES `utenti` (`Email`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assegnazioni`
--

LOCK TABLES `assegnazioni` WRITE;
/*!40000 ALTER TABLE `assegnazioni` DISABLE KEYS */;
INSERT INTO `assegnazioni` VALUES ('vava.collections','mario.rossi@sus.com');
/*!40000 ALTER TABLE `assegnazioni` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `autori`
--

DROP TABLE IF EXISTS `autori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `autori` (
  `Blocco` int(11) NOT NULL,
  `Sviluppatore` varchar(255) NOT NULL,
  PRIMARY KEY (`Blocco`,`Sviluppatore`),
  KEY `Sviluppatore` (`Sviluppatore`),
  CONSTRAINT `autori_ibfk_1` FOREIGN KEY (`Blocco`) REFERENCES `blocchi` (`Codice`) ON DELETE CASCADE,
  CONSTRAINT `autori_ibfk_2` FOREIGN KEY (`Sviluppatore`) REFERENCES `utenti` (`Email`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `autori`
--

LOCK TABLES `autori` WRITE;
/*!40000 ALTER TABLE `autori` DISABLE KEYS */;
/*!40000 ALTER TABLE `autori` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `avi`
--

DROP TABLE IF EXISTS `avi`;
/*!50001 DROP VIEW IF EXISTS `avi`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `avi` AS SELECT
 1 AS `TipoFiglio`,
 1 AS `SpazioFiglio`,
 1 AS `TipoAvo`,
 1 AS `SpazioAvo`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `blocchi`
--

DROP TABLE IF EXISTS `blocchi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blocchi` (
  `Codice` int(11) NOT NULL AUTO_INCREMENT,
  `Testo` varchar(1000) NOT NULL DEFAULT '',
  `Spazio` varchar(60) DEFAULT NULL,
  `Tipo` varchar(60) DEFAULT NULL,
  `Membro` varchar(60) DEFAULT NULL,
  `Parametro` varchar(60) DEFAULT NULL,
  `UltimaModifica` date NOT NULL DEFAULT curdate(),
  PRIMARY KEY (`Codice`),
  UNIQUE KEY `Spazio` (`Spazio`,`Tipo`,`Membro`,`Parametro`),
  CONSTRAINT `blocchi_ibfk_1` FOREIGN KEY (`Spazio`) REFERENCES `spazi` (`Nome`) ON DELETE CASCADE,
  CONSTRAINT `blocchi_ibfk_2` FOREIGN KEY (`Spazio`, `Tipo`) REFERENCES `tipi` (`Spazio`, `Nome`) ON DELETE CASCADE,
  CONSTRAINT `blocchi_ibfk_3` FOREIGN KEY (`Spazio`, `Tipo`, `Membro`) REFERENCES `membri` (`SpazioPadre`, `TipoPadre`, `Nome`) ON DELETE CASCADE,
  CONSTRAINT `blocchi_ibfk_4` FOREIGN KEY (`Spazio`, `Tipo`, `Membro`, `Parametro`) REFERENCES `parametri` (`SpazioFunzione`, `TipoFunzione`, `MembroFunzione`, `Nome`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocchi`
--

LOCK TABLES `blocchi` WRITE;
/*!40000 ALTER TABLE `blocchi` DISABLE KEYS */;
INSERT INTO `blocchi` VALUES (41,'Rappresenta una generica collezione di elementi.\r\n\r\nCollezioni non ordinate che non ammettono elementi ripetuti sono insiemi.\r\nCollezioni ordinate che non ammettono elementi ripetuti sono insiemi ordinati.\r\nCollezioni non ordinate che ammettono elementi ripetuti sono multi-insiemi.\r\nCollezioni ordinate che ammettono elementi ripetuti sono liste.','vava.collections','Collection',NULL,NULL,'2024-09-11'),(44,'Le strutture dati avanzate offerte dal linguaggio.','vava.collections',NULL,NULL,NULL,'2024-09-11'),(45,'I tipi implementati nativamente dal linguaggio.','vava.native',NULL,NULL,NULL,'2024-09-11'),(46,'Rappresenta qualsiasi tipo di valore. Ogni tipo eredita implicitamente da questo tipo.','vava.native','Any',NULL,NULL,'2024-09-11'),(48,'Rappresenta un valore booleano, ossia può assumere i valori TRUE e FALSE.','vava.native','Boolean',NULL,NULL,'2024-09-11'),(50,'Rappresenta un numero intero con segno.','vava.native','Integer',NULL,NULL,'2024-09-11'),(51,'Utilizzato dalle funzioni che non restituiscono un valore.','vava.native','Void',NULL,NULL,'2024-09-11'),(52,'Rappresenta una collezione ordinata che ammette elementi ripetuti.','vava.collections','List',NULL,NULL,'2024-09-11'),(53,'Restituisce il numero di elementi nella collezione.','vava.collections','Collection','size',NULL,'2024-09-11'),(54,'Determina se l\'elemento specificato è presente o meno nella collezione.','vava.collections','Collection','contains',NULL,'2024-09-11'),(55,'L\'elemento da cercare.','vava.collections','Collection','contains','element','2024-09-11'),(56,'Inserisce l\'elemento specificato nella collezione.\r\nSe la collezione è ordinata, l\'elemento verrà inserito in coda.\r\nIl valore restituito determina se la collezione è effettivamente cambiata.','vava.collections','Collection','add',NULL,'2024-09-11'),(58,'Rimuove tutte le occorrenze dell\'elemento specificato dalla collezione.\r\nRestituisce il numero di elementi rimossi.','vava.collections','Collection','remove',NULL,'2024-09-11'),(59,'L\'elemento da inserire.','vava.collections','Collection','add','element','2024-09-11'),(60,'L\'elemento da rimuovere.','vava.collections','Collection','remove','element','2024-09-11'),(61,'Restituisce l\'elemento alla posizione specificata.\r\nSe la posizione non è valida, viene lanciato un errore.','vava.collections','List','get',NULL,'2024-09-11'),(62,'La posizione dell\'elemento da ottenere.','vava.collections','List','get','index','2024-09-11'),(63,'Restituisce la posizione della prima occorrenza dell\'elemento specificato.\r\nSe l\'elemento non è presente nella lista, viene lanciato un errore.','vava.collections','List','index_of',NULL,'2024-09-11'),(64,'L\'elemento da cercare.','vava.collections','List','index_of','element','2024-09-11'),(65,'Sostituisce l\'elemento alla posizione specificata con l\'elemento specificato.\r\nPermette l\'inserimento in coda, ma in tutti gli altri casi, se nessun elemento è presente alla posizione specificata, viene lanciato un errore.','vava.collections','List','set',NULL,'2024-09-11'),(66,'La posizione in cui inserire l\'elemento.','vava.collections','List','set','index','2024-09-11'),(67,'L\'elemento da inserire.','vava.collections','List','set','element','2024-09-11'),(68,'Inserisce l\'elemento specificato alla posizione specificata, spostando verso la coda gli elementi successivi.\r\nPermette l\'inserimento in coda, ma in tutti gli altri casi, una posizione non valida causerà un errore.','vava.collections','List','insert',NULL,'2024-09-11'),(69,'La posizione in cui inserire l\'elemento.','vava.collections','List','insert','index','2024-09-11'),(70,'L\'elemento da inserire.','vava.collections','List','insert','element','2024-09-11'),(71,'Rimuove e resistuisce l\'elemento alla posizione selezionata, spostando verso la testa gli elementi successivi.\r\nSe nessun elemento si trova alla posizione selezionata, viene lanciato un errore.','vava.collections','List','remove_at',NULL,'2024-09-11'),(72,'Restituisce una lista vuota, basata sull\'utilizzo di un vettore.','vava.collections','List','array_list',NULL,'2024-09-11'),(73,'Rappresenta una sequenza di elementi.\r\nUna volta istanziato, il numero di elementi non può essere modificato.','vava.native','Array',NULL,NULL,'2024-09-11'),(74,'Funzione di utility per scambiare di posto i due elementi nella lista.','vava.collections','List','swap',NULL,'2024-09-11'),(75,'Restituisce la lista invertita, senza alterare quella di partenza.','vava.collections','List','reverse',NULL,'2024-09-11'),(76,'Rappresenta una lista implementata con l\'utilizzo di un vettore.','vava.collections','ArrayList',NULL,NULL,'2024-09-11'),(77,'Il vettore utilizzato per contenere gli elementi della lista.\r\nVerrà nuovamente istanziato quando si necessita di aumentare la capienza.','vava.collections','ArrayList','elements',NULL,'2024-09-11'),(78,'Memorizza il numero di elementi all\'interno del vettore.','vava.collections','ArrayList','count',NULL,'2024-09-11'),(79,'Permette di modificare l\'intera lista, scartando gli elementi precedenti e inserendo quelli specificati.','vava.collections','ArrayList','set_elements',NULL,'2024-09-11'),(80,'I nuovi elementi della lista.','vava.collections','ArrayList','set_elements','elements','2024-09-11'),(81,'Crea e restituisce una nuova lista, basata su un vettore, che contenga gli elementi specificati.','vava.collections','List','array_list_of',NULL,'2024-09-11'),(82,'Gli elementi da inserire nella nuova lista.','vava.collections','List','array_list_of','elements','2024-09-11'),(83,'Accresce il vettore di supporto in modo che possa contenere il numero di elementi specificato.','vava.collections','ArrayList','grow',NULL,'2024-09-11'),(84,'Capacità minima che dovrà avere il vettore di supporto.','vava.collections','ArrayList','grow','min_capacity','2024-09-11'),(85,'Crea e restituisce una lista vuota.','vava.collections','ArrayList','empty',NULL,'2024-09-11'),(86,'Il valore massimo rappresentabile da un intero.','vava.native','Integer','MAX_VALUE',NULL,'2024-09-11'),(87,'Il valore minimo (avente segno negativo e modulo massimo) rappresentabile da un intero.','vava.native','Integer','MIN_VALUE',NULL,'2024-09-11');
/*!40000 ALTER TABLE `blocchi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ereditarietà`
--

DROP TABLE IF EXISTS `ereditarietà`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ereditarietà` (
  `TipoPadre` varchar(60) NOT NULL,
  `SpazioPadre` varchar(60) NOT NULL,
  `TipoFiglio` varchar(60) NOT NULL,
  `SpazioFiglio` varchar(60) NOT NULL,
  PRIMARY KEY (`SpazioPadre`,`TipoPadre`,`SpazioFiglio`,`TipoFiglio`),
  KEY `SpazioFiglio` (`SpazioFiglio`,`TipoFiglio`),
  CONSTRAINT `ereditarietà_ibfk_1` FOREIGN KEY (`SpazioPadre`, `TipoPadre`) REFERENCES `tipi` (`Spazio`, `Nome`) ON DELETE CASCADE,
  CONSTRAINT `ereditarietà_ibfk_2` FOREIGN KEY (`SpazioFiglio`, `TipoFiglio`) REFERENCES `tipi` (`Spazio`, `Nome`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ereditarietà`
--

LOCK TABLES `ereditarietà` WRITE;
/*!40000 ALTER TABLE `ereditarietà` DISABLE KEYS */;
INSERT INTO `ereditarietà` VALUES ('Collection','vava.collections','List','vava.collections'),('List','vava.collections','ArrayList','vava.collections');
/*!40000 ALTER TABLE `ereditarietà` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membri`
--

DROP TABLE IF EXISTS `membri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `membri` (
  `Nome` varchar(60) NOT NULL,
  `TipoPadre` varchar(60) NOT NULL,
  `SpazioPadre` varchar(60) NOT NULL,
  `Categoria` enum('Campo','Metodo','MetodoAstratto','MetodoStatico','Costante','Valore') NOT NULL,
  `Pubblico` tinyint(1) NOT NULL,
  `Tipo` varchar(60) DEFAULT NULL,
  `Spazio` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`SpazioPadre`,`TipoPadre`,`Nome`),
  KEY `Spazio` (`Spazio`,`Tipo`),
  CONSTRAINT `membri_ibfk_1` FOREIGN KEY (`SpazioPadre`, `TipoPadre`) REFERENCES `tipi` (`Spazio`, `Nome`) ON DELETE CASCADE,
  CONSTRAINT `membri_ibfk_2` FOREIGN KEY (`Spazio`, `Tipo`) REFERENCES `tipi` (`Spazio`, `Nome`) ON DELETE CASCADE,
  CONSTRAINT `CONSTRAINT_1` CHECK (`Categoria` <> 'Campo' or `Pubblico` = 0),
  CONSTRAINT `CONSTRAINT_2` CHECK (`Categoria` <> 'Valore' or `Pubblico` = 1),
  CONSTRAINT `CONSTRAINT_3` CHECK (`Categoria` = 'Valore' and `Tipo` is null and `Spazio` is null or `Categoria` <> 'Valore' and `Tipo` is not null and `Spazio` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membri`
--

LOCK TABLES `membri` WRITE;
/*!40000 ALTER TABLE `membri` DISABLE KEYS */;
INSERT INTO `membri` VALUES ('count','ArrayList','vava.collections','Campo',0,'Integer','vava.native'),('elements','ArrayList','vava.collections','Campo',0,'Array','vava.native'),('empty','ArrayList','vava.collections','MetodoStatico',1,'ArrayList','vava.collections'),('grow','ArrayList','vava.collections','Metodo',0,'Void','vava.native'),('set_elements','ArrayList','vava.collections','Metodo',1,'Void','vava.native'),('add','Collection','vava.collections','MetodoAstratto',1,'Boolean','vava.native'),('contains','Collection','vava.collections','MetodoAstratto',1,'Boolean','vava.native'),('remove','Collection','vava.collections','MetodoAstratto',1,'Integer','vava.native'),('size','Collection','vava.collections','MetodoAstratto',1,'Integer','vava.native'),('array_list','List','vava.collections','MetodoStatico',1,'List','vava.collections'),('array_list_of','List','vava.collections','MetodoStatico',1,'List','vava.collections'),('get','List','vava.collections','MetodoAstratto',1,'Any','vava.native'),('index_of','List','vava.collections','MetodoAstratto',1,'Integer','vava.native'),('insert','List','vava.collections','MetodoAstratto',1,'Void','vava.native'),('remove_at','List','vava.collections','MetodoAstratto',1,'Any','vava.native'),('reverse','List','vava.collections','MetodoAstratto',1,'List','vava.collections'),('set','List','vava.collections','MetodoAstratto',1,'Void','vava.native'),('swap','List','vava.collections','MetodoStatico',0,'Void','vava.native'),('FALSE','Boolean','vava.native','Valore',1,NULL,NULL),('TRUE','Boolean','vava.native','Valore',1,NULL,NULL),('MAX_VALUE','Integer','vava.native','Costante',1,'Integer','vava.native'),('MIN_VALUE','Integer','vava.native','Costante',1,'Integer','vava.native');
/*!40000 ALTER TABLE `membri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `metodiereditati`
--

DROP TABLE IF EXISTS `metodiereditati`;
/*!50001 DROP VIEW IF EXISTS `metodiereditati`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `metodiereditati` AS SELECT
 1 AS `TipoFiglio`,
 1 AS `SpazioFiglio`,
 1 AS `TipoAvo`,
 1 AS `SpazioAvo`,
 1 AS `Nome`,
 1 AS `TipoPadre`,
 1 AS `SpazioPadre`,
 1 AS `Categoria`,
 1 AS `Pubblico`,
 1 AS `Tipo`,
 1 AS `Spazio`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `parametri`
--

DROP TABLE IF EXISTS `parametri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parametri` (
  `Nome` varchar(60) NOT NULL,
  `MembroFunzione` varchar(60) NOT NULL,
  `TipoFunzione` varchar(60) NOT NULL,
  `SpazioFunzione` varchar(60) NOT NULL,
  `Indice` int(11) NOT NULL,
  `Tipo` varchar(60) NOT NULL,
  `Spazio` varchar(60) NOT NULL,
  PRIMARY KEY (`SpazioFunzione`,`TipoFunzione`,`MembroFunzione`,`Nome`),
  UNIQUE KEY `SpazioFunzione` (`SpazioFunzione`,`TipoFunzione`,`MembroFunzione`,`Indice`),
  KEY `Spazio` (`Spazio`,`Tipo`),
  CONSTRAINT `parametri_ibfk_1` FOREIGN KEY (`SpazioFunzione`, `TipoFunzione`, `MembroFunzione`) REFERENCES `membri` (`SpazioPadre`, `TipoPadre`, `Nome`) ON DELETE CASCADE,
  CONSTRAINT `parametri_ibfk_2` FOREIGN KEY (`Spazio`, `Tipo`) REFERENCES `tipi` (`Spazio`, `Nome`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parametri`
--

LOCK TABLES `parametri` WRITE;
/*!40000 ALTER TABLE `parametri` DISABLE KEYS */;
INSERT INTO `parametri` VALUES ('min_capacity','grow','ArrayList','vava.collections',2,'Integer','vava.native'),('elements','set_elements','ArrayList','vava.collections',1,'Array','vava.native'),('element','add','Collection','vava.collections',2,'Any','vava.native'),('element','contains','Collection','vava.collections',1,'Any','vava.native'),('element','remove','Collection','vava.collections',3,'Any','vava.native'),('elements','array_list_of','List','vava.collections',11,'Array','vava.native'),('index','get','List','vava.collections',1,'Integer','vava.native'),('element','index_of','List','vava.collections',2,'Any','vava.native'),('element','insert','List','vava.collections',6,'Any','vava.native'),('index','insert','List','vava.collections',5,'Integer','vava.native'),('index','remove_at','List','vava.collections',7,'Integer','vava.native'),('element','set','List','vava.collections',4,'Any','vava.native'),('index','set','List','vava.collections',3,'Integer','vava.native'),('i','swap','List','vava.collections',9,'Integer','vava.native'),('j','swap','List','vava.collections',10,'Integer','vava.native'),('list','swap','List','vava.collections',8,'List','vava.collections');
/*!40000 ALTER TABLE `parametri` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `riferimenti`
--

DROP TABLE IF EXISTS `riferimenti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `riferimenti` (
  `Blocco` int(11) NOT NULL,
  `Inizio` int(11) NOT NULL,
  `Lunghezza` int(11) DEFAULT NULL,
  `Spazio` varchar(60) DEFAULT NULL,
  `Tipo` varchar(60) DEFAULT NULL,
  `Membro` varchar(60) DEFAULT NULL,
  `Parametro` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`Blocco`,`Inizio`),
  KEY `Spazio` (`Spazio`,`Tipo`,`Membro`,`Parametro`),
  CONSTRAINT `riferimenti_ibfk_1` FOREIGN KEY (`Blocco`) REFERENCES `blocchi` (`Codice`) ON DELETE CASCADE,
  CONSTRAINT `riferimenti_ibfk_2` FOREIGN KEY (`Spazio`) REFERENCES `spazi` (`Nome`) ON DELETE CASCADE,
  CONSTRAINT `riferimenti_ibfk_3` FOREIGN KEY (`Spazio`, `Tipo`) REFERENCES `tipi` (`Spazio`, `Nome`) ON DELETE CASCADE,
  CONSTRAINT `riferimenti_ibfk_4` FOREIGN KEY (`Spazio`, `Tipo`, `Membro`) REFERENCES `membri` (`SpazioPadre`, `TipoPadre`, `Nome`) ON DELETE CASCADE,
  CONSTRAINT `riferimenti_ibfk_5` FOREIGN KEY (`Spazio`, `Tipo`, `Membro`, `Parametro`) REFERENCES `parametri` (`SpazioFunzione`, `TipoFunzione`, `MembroFunzione`, `Nome`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `riferimenti`
--

LOCK TABLES `riferimenti` WRITE;
/*!40000 ALTER TABLE `riferimenti` DISABLE KEYS */;
INSERT INTO `riferimenti` VALUES (41,336,5,'vava.collections','List',NULL,NULL),(48,60,4,'vava.native','Boolean','TRUE',NULL),(48,67,5,'vava.native','Boolean','FALSE',NULL),(52,16,10,'vava.collections','Collection',NULL,NULL),(54,15,20,'vava.collections','Collection','contains','element'),(56,12,20,'vava.collections','Collection','add','element'),(58,33,20,'vava.collections','Collection','remove','element'),(61,28,21,'vava.collections','List','get','index'),(63,53,20,'vava.collections','List','index_of','element'),(65,28,21,'vava.collections','List','set','index'),(65,56,20,'vava.collections','List','set','element'),(68,12,20,'vava.collections','List','insert','element'),(68,38,21,'vava.collections','List','insert','index'),(71,38,21,'vava.collections','List','remove_at','index'),(72,56,7,'vava.native','Array',NULL,NULL),(76,16,5,'vava.collections','List',NULL,NULL),(76,56,7,'vava.native','Array',NULL,NULL),(78,48,7,'vava.collections','ArrayList','elements',NULL),(79,85,18,'vava.collections','ArrayList','set_elements','elements'),(81,49,7,'vava.native','Array',NULL,NULL),(81,75,20,'vava.collections','List','array_list_of','elements'),(83,12,19,'vava.collections','ArrayList','elements',NULL),(83,63,30,'vava.collections','ArrayList','grow','min_capacity'),(86,40,6,'vava.native','Integer',NULL,NULL),(87,80,6,'vava.native','Integer',NULL,NULL);
/*!40000 ALTER TABLE `riferimenti` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `spazi`
--

DROP TABLE IF EXISTS `spazi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `spazi` (
  `Nome` varchar(60) NOT NULL,
  PRIMARY KEY (`Nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spazi`
--

LOCK TABLES `spazi` WRITE;
/*!40000 ALTER TABLE `spazi` DISABLE KEYS */;
INSERT INTO `spazi` VALUES ('vava.collections'),('vava.native');
/*!40000 ALTER TABLE `spazi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipi`
--

DROP TABLE IF EXISTS `tipi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipi` (
  `Nome` varchar(60) NOT NULL,
  `Spazio` varchar(60) NOT NULL,
  `Categoria` enum('Interfaccia','Implementazione','Enumerazione') NOT NULL,
  `Pubblico` tinyint(1) NOT NULL,
  PRIMARY KEY (`Spazio`,`Nome`),
  CONSTRAINT `tipi_ibfk_1` FOREIGN KEY (`Spazio`) REFERENCES `spazi` (`Nome`) ON DELETE CASCADE,
  CONSTRAINT `CONSTRAINT_1` CHECK (`Categoria` <> 'Implementazione' or `Pubblico` = 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipi`
--

LOCK TABLES `tipi` WRITE;
/*!40000 ALTER TABLE `tipi` DISABLE KEYS */;
INSERT INTO `tipi` VALUES ('ArrayList','vava.collections','Implementazione',0),('Collection','vava.collections','Interfaccia',1),('List','vava.collections','Interfaccia',1),('Any','vava.native','Interfaccia',1),('Array','vava.native','Interfaccia',1),('Boolean','vava.native','Enumerazione',1),('Integer','vava.native','Interfaccia',1),('Void','vava.native','Interfaccia',1);
/*!40000 ALTER TABLE `tipi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utenti`
--

DROP TABLE IF EXISTS `utenti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `utenti` (
  `Email` varchar(255) NOT NULL,
  `NumeroBadge` char(5) NOT NULL,
  `Nome` varchar(30) NOT NULL,
  `Cognome` varchar(60) NOT NULL,
  `Password` varchar(60) NOT NULL,
  `Ruolo` enum('Visualizzatore','Sviluppatore','Amministratore') NOT NULL,
  PRIMARY KEY (`Email`),
  UNIQUE KEY `NumeroBadge` (`NumeroBadge`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utenti`
--

LOCK TABLES `utenti` WRITE;
/*!40000 ALTER TABLE `utenti` DISABLE KEYS */;
INSERT INTO `utenti` VALUES ('marco.buda@sus.com','00000','Marco','Buda','$2y$10$GVwf5qaZC/yDeAGr/oNFC.I0b4qB2xhbXzPwPmRbKz1zm3X6gECoG','Amministratore'),('mario.rossi@sus.com','00001','Mario','Rossi','$2y$10$CBWVXqTD55/BouPQI5XWX.1VYvStFX21l7IYpB.4qos0SgCpxfcEa','Sviluppatore');
/*!40000 ALTER TABLE `utenti` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `avi`
--

/*!50001 DROP VIEW IF EXISTS `avi`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `avi` AS with recursive AviTmp(TipoFiglio,SpazioFiglio,TipoAvo,SpazioAvo) as (select `ereditarietà`.`TipoFiglio` AS `TipoFiglio`,`ereditarietà`.`SpazioFiglio` AS `SpazioFiglio`,`ereditarietà`.`TipoPadre` AS `TipoAvo`,`ereditarietà`.`SpazioPadre` AS `SpazioAvo` from `ereditarietà` union all select `e`.`TipoFiglio` AS `TipoFiglio`,`e`.`SpazioFiglio` AS `SpazioFiglio`,`a`.`TipoAvo` AS `TipoAvo`,`a`.`SpazioAvo` AS `SpazioAvo` from (`ereditarietà` `e` join `avitmp` `a`) where `e`.`SpazioPadre` = `a`.`SpazioFiglio` and `e`.`TipoPadre` = `a`.`TipoFiglio`)select `avitmp`.`TipoFiglio` AS `TipoFiglio`,`avitmp`.`SpazioFiglio` AS `SpazioFiglio`,`avitmp`.`TipoAvo` AS `TipoAvo`,`avitmp`.`SpazioAvo` AS `SpazioAvo` from `avitmp` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `metodiereditati`
--

/*!50001 DROP VIEW IF EXISTS `metodiereditati`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `metodiereditati` AS select `avi`.`TipoFiglio` AS `TipoFiglio`,`avi`.`SpazioFiglio` AS `SpazioFiglio`,`avi`.`TipoAvo` AS `TipoAvo`,`avi`.`SpazioAvo` AS `SpazioAvo`,`membri`.`Nome` AS `Nome`,`membri`.`TipoPadre` AS `TipoPadre`,`membri`.`SpazioPadre` AS `SpazioPadre`,`membri`.`Categoria` AS `Categoria`,`membri`.`Pubblico` AS `Pubblico`,`membri`.`Tipo` AS `Tipo`,`membri`.`Spazio` AS `Spazio` from (`avi` join `membri`) where `membri`.`SpazioPadre` = `avi`.`SpazioAvo` and `membri`.`TipoPadre` = `avi`.`TipoAvo` and `membri`.`Categoria` = 'MetodoAstratto' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-09-12 15:32:46
