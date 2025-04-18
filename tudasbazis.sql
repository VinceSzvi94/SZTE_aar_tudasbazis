-- 1. FELHASZNÁLÓ
-- Felhasználó (séma) törlése (ha létezik)
BEGIN
    EXECUTE IMMEDIATE 'DROP USER C##tudasbazis CASCADE';
EXCEPTION
    WHEN OTHERS THEN
        -- ORA-01918: user does not exist ERROR, elvileg ez az error ignorálva lesz, mintha DROP .. IF EXISTS lenne mySQL-ben
        IF SQLCODE != -01918 THEN  
            RAISE;
        END IF;
END;
/

-- Felhasználó létrehozása (ha még nem létezik)
BEGIN
    EXECUTE IMMEDIATE 'CREATE USER C##tudasbazis IDENTIFIED BY "pass123456"'; -- problémás lehet a jelszó forrásba téve, de egyenlőre így egyszerűbb
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -1920 THEN -- "user already exists" hibakód
            RAISE;
        END IF;
END;
/

-- Jogosultságok megadása
BEGIN
    EXECUTE IMMEDIATE 'GRANT CONNECT, RESOURCE, CREATE VIEW, CREATE SEQUENCE TO C##tudasbazis';
    EXECUTE IMMEDIATE 'ALTER USER C##tudasbazis QUOTA UNLIMITED ON USERS'; -- korlátlan tárhely a felhasználónak
EXCEPTION
    WHEN OTHERS THEN
        NULL; -- Kezelhetnénk a hibát, de most csak figyelmen kívül hagyjuk
END;
/

-- Jelszó az adatbázishoz (ez kell a csatlakozáshoz php-ban)
ALTER USER C##tudasbazis IDENTIFIED BY pass123456;

-- Fontos: csatlakoztass a megfelelő sémához
ALTER SESSION SET CURRENT_SCHEMA = C##tudasbazis;

-- Felhasználó tábla
CREATE TABLE Felhasznalo (
    ID NUMBER(6) PRIMARY KEY, -- max 6 jegyű egész szám
    Nev VARCHAR2(100) NOT NULL, -- max 100 karakter
    Email VARCHAR2(80) UNIQUE NOT NULL, -- max 80 karakter, egyedi email cím
    Jelszo VARCHAR2(255) NOT NULL, -- hogy lehessen hash-elni
    Varos VARCHAR2(25),
    Kozterulet_nev VARCHAR2(50),
    Kozterulet_tipus VARCHAR2(15), -- pl. utca, tér, stb.
    Hazszam VARCHAR2(6) -- varchar mert lehet pl 5B vagy más hasonló nem szám
);

-- Lektor tábla
CREATE TABLE Lektor (
    Felhasznalo_ID NUMBER(6) PRIMARY KEY,
    Tudomanyos_fokozat VARCHAR2(30),
    Intezet VARCHAR2(50),
    Szakterulet VARCHAR2(50),
    CONSTRAINT fk_lektor_felhasznalo FOREIGN KEY (Felhasznalo_ID) REFERENCES Felhasznalo(ID) ON DELETE CASCADE 
    -- ha a felhasználó törlésre kerül, akkor a lektor is törlődik (mivel csak plusz információ a törölt felhasználóról)
);
/* NOTE FONOTS
ON UPDATE - fontos különbség
Az Oracle SQL nem támogatja az ON UPDATE klauzulát. Az Oracle adatbázisokban a külső kulcsok automatikusan frissülnek, ha az elsődleges kulcs frissül. Ez azonban ritkán fordul elő, mivel az Oracle általában nem ajánlja az elsődleges kulcsok értékeinek módosítását.
*/

-- Admin tábla
-- KÉRDÉS: ő ugye teljesen külön van kezelve a felhasználóktól?
CREATE TABLE Admin (
    ID NUMBER(3) PRIMARY KEY, -- max 3 jegyű egész szám (nincs sok admin)
    Nev VARCHAR2(100) NOT NULL,
    Email VARCHAR2(80) UNIQUE NOT NULL,
    Jelszo VARCHAR2(255) NOT NULL -- hogy lehessen hash-elni
);

-- Kategória tábla
CREATE TABLE Kategoria (
    ID NUMBER(5) PRIMARY KEY, -- max 5 jegyű egész szám (max 99999 kategória)
    Nev VARCHAR2(100) UNIQUE NOT NULL
);

-- Lektorálhat kapcsolótábla
CREATE TABLE Lektoralhat (
    Lektor_ID NUMBER(6),
    Kategoria_ID NUMBER(5),
    PRIMARY KEY (Lektor_ID, Kategoria_ID),
    CONSTRAINT fk_lektoralhat_lektor FOREIGN KEY (Lektor_ID) REFERENCES Lektor(Felhasznalo_ID) ON DELETE CASCADE,
    CONSTRAINT fk_lektoralhat_kategoria FOREIGN KEY (Kategoria_ID) REFERENCES Kategoria(ID) ON DELETE CASCADE
    -- ha a lektor vagy a kategória törlésre kerül, akkor a kapcsoló is törlődik
);

-- Cikk tábla
CREATE TABLE Cikk (
    ID NUMBER(6) PRIMARY KEY,
    Cim VARCHAR2(80) NOT NULL,
    Tartalom CLOB NOT NULL, -- nagy karakteres objektum
    Letrehozta_ID NUMBER(6),
    Kategoria_ID NUMBER(5),
    Van_e_lektoralva NUMBER(1) DEFAULT 0 CHECK (Van_e_lektoralva IN (0, 1)), -- 0 = nem, 1 = igen (nincs BOOLEAN típus)
    Letrehozas_Datum TIMESTAMP DEFAULT SYSDATE, -- másodperc pontosság
    Modositas_Datum TIMESTAMP DEFAULT SYSDATE, -- másodperc pontosság
    CONSTRAINT fk_cikk_felhasznalo FOREIGN KEY (Letrehozta_ID) REFERENCES Felhasznalo(ID) ON DELETE SET NULL,
    CONSTRAINT fk_cikk_kategoria FOREIGN KEY (Kategoria_ID) REFERENCES Kategoria(ID) ON DELETE SET NULL
    -- ha a felhasználó vagy a kategória törlésre kerül, akkor a cikk megmarad üres létrehozóval vagy kategória nélkül
);

-- Hibák tábla
CREATE TABLE Hibak (
    ID NUMBER(8) PRIMARY KEY,
    Cikk_ID NUMBER(6),
    Bejelento_ID NUMBER(6),
    Hibalerias VARCHAR2(2000),
    Datum TIMESTAMP DEFAULT SYSDATE, -- másodperc pontosság
    CONSTRAINT fk_hibak_cikk FOREIGN KEY (Cikk_ID) REFERENCES Cikk(ID),
    CONSTRAINT fk_hibak_bejelento FOREIGN KEY (Bejelento_ID) REFERENCES Felhasznalo(ID)
);

-- Módosítások tábla
CREATE TABLE Modositasok (
    ID NUMBER(8) PRIMARY KEY,
    ID_cikk NUMBER(6),
    ID_modosito NUMBER(6),
    Datum TIMESTAMP DEFAULT SYSDATE, -- másodperc pontosság
    -- PRIMARY KEY (ID_cikk, ID_modosito, Datum), KÜLÖN PRIMARY KEY hogy felhasználó törlésekor megmaradhasson NULL értékekkel !!
    CONSTRAINT fk_modositasok_cikk FOREIGN KEY (ID_cikk) REFERENCES Cikk(ID) ON DELETE CASCADE,
    CONSTRAINT fk_modositasok_modosito FOREIGN KEY (ID_modosito) REFERENCES Felhasznalo(ID) ON DELETE SET NULL
    -- a cikk törlésekor törlődnek a módosítások is a felhasználó törlésekor viszont a bejegyzés megmarad módosító nélkül
);

-- Lektorálások tábla
CREATE TABLE Lektoralasok (
    ID NUMBER(8) PRIMARY KEY,
    ID_cikk NUMBER(6),
    ID_lektoralo NUMBER(6),
    Datum TIMESTAMP DEFAULT SYSDATE, -- másodperc pontosság
    -- PRIMARY KEY (ID_cikk, ID_lektoralo, Datum), KÜLÖN PRIMARY KEY hogy felhasználó törlésekor megmaradhasson NULL értékekkel !!
    CONSTRAINT fk_lektoralasok_cikk FOREIGN KEY (ID_cikk) REFERENCES Cikk(ID) ON DELETE CASCADE,
    CONSTRAINT fk_lektoralasok_lektor FOREIGN KEY (ID_lektoralo) REFERENCES Lektor(Felhasznalo_ID) ON DELETE SET NULL
    -- a cikk törlésekor törlődnek a lektorálások is a lektor törlésekor viszont a bejegyzés megmarad lektoráló nélkül
);

-- Szekvenciák a táblák ID-jához
CREATE SEQUENCE felhasznalo_seq START WITH 100000 INCREMENT BY 1; -- 100 000-től indul, hogy 6 jegyű legyen
CREATE SEQUENCE admin_seq START WITH 100 INCREMENT BY 1; -- 100-tól indul, hogy 3 jegyű legyen
CREATE SEQUENCE kategoria_seq START WITH 10000 INCREMENT BY 1; -- 10 000-től indul, hogy 5 jegyű legyen
CREATE SEQUENCE cikk_seq START WITH 100000 INCREMENT BY 1; -- 100 000-től indul, hogy 6 jegyű legyen
CREATE SEQUENCE hibak_seq START WITH 10000000 INCREMENT BY 1; -- 10 000 000-tól indul, hogy 8 jegyű legyen
CREATE SEQUENCE modositasok_seq START WITH 10000000 INCREMENT BY 1; -- 10 000 000-tól indul, hogy 8 jegyű legyen
CREATE SEQUENCE lektoralasok_seq START WITH 10000000 INCREMENT BY 1; -- 10 000 000-tól indul, hogy 8 jegyű legyen

-- 5-5 rekord minden táblába
-- Admin tábla
INSERT INTO Admin (ID, Nev, Email, Jelszo)
VALUES (admin_seq.NEXTVAL, 'Rendszergazda', 'admin@example.com', 'adminpass');

-- Felhasználó tábla
INSERT INTO Felhasznalo (ID, Nev, Email, Jelszo, Varos, Kozterulet_nev, Kozterulet_tipus, Hazszam)
VALUES (felhasznalo_seq.NEXTVAL, 'Kiss Lajos', 'user1@example.com', 'pass1', 'Budapest', 'Bocskai', 'utca', '1');

INSERT INTO Felhasznalo (ID, Nev, Email, Jelszo, Varos, Kozterulet_nev, Kozterulet_tipus, Hazszam)
VALUES (felhasznalo_seq.NEXTVAL, 'Nagy Mária', 'user2@example.com', 'pass2', 'Budapest', 'Péter', 'utca', '2');

INSERT INTO Felhasznalo (ID, Nev, Email, Jelszo, Varos, Kozterulet_nev, Kozterulet_tipus, Hazszam)
VALUES (felhasznalo_seq.NEXTVAL, 'Gondos Tódor', 'user3@example.com', 'pass3', 'Budapest', 'Petőfi', 'utca', '3');

INSERT INTO Felhasznalo (ID, Nev, Email, Jelszo, Varos, Kozterulet_nev, Kozterulet_tipus, Hazszam)
VALUES (felhasznalo_seq.NEXTVAL, 'Remek Elek', 'user4@example.com', 'pass4', 'Budapest', 'Erdő', 'utca', '4');

INSERT INTO Felhasznalo (ID, Nev, Email, Jelszo, Varos, Kozterulet_nev, Kozterulet_tipus, Hazszam)
VALUES (felhasznalo_seq.NEXTVAL, 'Pesti Kornél', 'user5@example.com', 'pass5', 'Budapest', 'Fő', 'utca', '5');

INSERT INTO Felhasznalo (ID, Nev, Email, Jelszo, Varos, Kozterulet_nev, Kozterulet_tipus, Hazszam)
VALUES (felhasznalo_seq.NEXTVAL, 'Antal Alajos', 'user6@example.com', 'pass6', 'Budapest', 'Fő', 'utca', '6');

INSERT INTO Felhasznalo (ID, Nev, Email, Jelszo, Varos, Kozterulet_nev, Kozterulet_tipus, Hazszam)
VALUES (felhasznalo_seq.NEXTVAL, 'Péterfi Pál', 'user7@example.com', 'pass7', 'Budapest', 'Károly', 'körút', '7');

INSERT INTO Felhasznalo (ID, Nev, Email, Jelszo, Varos, Kozterulet_nev, Kozterulet_tipus, Hazszam)
VALUES (felhasznalo_seq.NEXTVAL, 'Újházi Lajos', 'user8@example.com', 'pass8', 'Budapest', 'Erzsébet', 'utca', '8');

INSERT INTO Felhasznalo (ID, Nev, Email, Jelszo, Varos, Kozterulet_nev, Kozterulet_tipus, Hazszam)
VALUES (felhasznalo_seq.NEXTVAL, 'Sóbert Norbert', 'user9@example.com', 'pass9', 'Budapest', 'Király', 'utca', '9');

INSERT INTO Felhasznalo (ID, Nev, Email, Jelszo, Varos, Kozterulet_nev, Kozterulet_tipus, Hazszam)
VALUES (felhasznalo_seq.NEXTVAL, 'Fantasztikus Mr Fox', 'user10@example.com', 'pass10', 'Budapest', 'Fő', 'utca', '10');

-- Lektor tábla
INSERT INTO Lektor (Felhasznalo_ID, Tudomanyos_fokozat, Intezet, Szakterulet)
VALUES (100001, 'PhD', 'Matematika Intézet', 'Algebra');

INSERT INTO Lektor (Felhasznalo_ID, Tudomanyos_fokozat, Intezet, Szakterulet)
VALUES (100002, 'MBA', 'Gazdaságtudományi Kar', 'Pénzügy');

INSERT INTO Lektor (Felhasznalo_ID, Tudomanyos_fokozat, Intezet, Szakterulet)
VALUES (100003, 'MSc', 'Informatikai Kar', 'Programozás');

INSERT INTO Lektor (Felhasznalo_ID, Tudomanyos_fokozat, Intezet, Szakterulet)
VALUES (100004, 'PhD', 'Fizikai Intézet', 'Mechanika');

INSERT INTO Lektor (Felhasznalo_ID, Tudomanyos_fokozat, Intezet, Szakterulet)
VALUES (100005, 'PhD', 'Kémiai Intézet', 'Organikus Kémia');

-- Kategória tábla
INSERT INTO Kategoria (ID, Nev) VALUES (kategoria_seq.NEXTVAL, 'informatika');
INSERT INTO Kategoria (ID, Nev) VALUES (kategoria_seq.NEXTVAL, 'fizika');
INSERT INTO Kategoria (ID, Nev) VALUES (kategoria_seq.NEXTVAL, 'földrajz');
INSERT INTO Kategoria (ID, Nev) VALUES (kategoria_seq.NEXTVAL, 'kémia');
INSERT INTO Kategoria (ID, Nev) VALUES (kategoria_seq.NEXTVAL, 'történelem');

-- Cikk tábla (itt a cikkek létrehozásakor a létrehozó és a kategória ID-re figyelni!)
INSERT INTO Cikk (ID, Cim, Tartalom, Letrehozta_ID, Kategoria_ID, Van_e_lektoralva, Letrehozas_Datum, Modositas_Datum)
VALUES (cikk_seq.NEXTVAL, 'Cikk 1', 'Tartalom cikk 1', 100003, 10001, 0, SYSDATE, SYSDATE); 
-- léátrehozáskor az utolsó módosítás dátuma is a létrehozás dátuma

INSERT INTO Cikk (ID, Cim, Tartalom, Letrehozta_ID, Kategoria_ID, Van_e_lektoralva, Letrehozas_Datum, Modositas_Datum)
VALUES (cikk_seq.NEXTVAL, 'Cikk 2', 'Tartalom cikk 2', 100004, 10001, 1, SYSDATE, SYSDATE);

INSERT INTO Cikk (ID, Cim, Tartalom, Letrehozta_ID, Kategoria_ID, Van_e_lektoralva, Letrehozas_Datum, Modositas_Datum)
VALUES (cikk_seq.NEXTVAL, 'Cikk 3', 'Tartalom cikk 3', 100005, 10003, 0, SYSDATE, SYSDATE);

INSERT INTO Cikk (ID, Cim, Tartalom, Letrehozta_ID, Kategoria_ID, Van_e_lektoralva, Letrehozas_Datum, Modositas_Datum)
VALUES (cikk_seq.NEXTVAL, 'Cikk 4', 'Tartalom cikk 4', 100007, 10001, 1, SYSDATE, SYSDATE);

INSERT INTO Cikk (ID, Cim, Tartalom, Letrehozta_ID, Kategoria_ID, Van_e_lektoralva, Letrehozas_Datum, Modositas_Datum)
VALUES (cikk_seq.NEXTVAL, 'Cikk 5', 'Tartalom cikk 5', 100007, 10002, 0, SYSDATE, SYSDATE);
