# IPSymconServerInfo

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-6.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Abrufen von Betriebssystem-Informationen.

## 2. Voraussetzungen

 - IP-Symcon ab Version 6.0

## 3. Installation

### a. Betriebssystem vorbereiten

#### Raspbian

#### Ubuntu

`sudo apt-get install hddtemp`<br>
`sudo chmod u+s /usr/sbin/hddtemp`

### b. Laden des Moduls

Die Konsole von IP-Symcon öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconServerInfo.git`

und mit _OK_ bestätigen.

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_

### c. Einrichtung in IPS

In IP-Symcon nun _Instanz hinzufügen_ (_CTRL+1_) auswählen unter der Kategorie, unter der man die Instanz hinzufügen will, und Hersteller _(sonstiges)_ und als Gerät _ServerInfo_ auswählen.

## 4. Funktionsreferenz

### zentrale Funktion

## 5. Konfiguration:

### Variablen

| Eigenschaft                  | Typ     | Standardwert | Beschreibung |
| :------------------------    | :------ | :----------- | :----------- |
| Instanz deaktivieren         | boolean | false        | Instanz temporär deaktivieren |
|                              |         |              | |
| Aktualisiere Daten ...       | integer | 5            | Aktualisierungsintervall, Angabe in Minuten |
|                              |         |              | |
| Gerät für Partition ...      | string  | s.u.         | nur Patitionen mit Dateisystem |
| ... Einheit                  | integer | s.u.         | Einheit der Größenangabe (GB/MB) |
| Gerät für Datenträger ...    | string  | s.u.         | nur Datenträger mit S.M.A.R.T |
|                              |         |              | |
| Auslagerungsbereich anzeigen | boolean | true         | Auslagerungsbereich anzeigen |
| CPU-Temperatur anzeigen      | boolean | true         | CPU-Temperatur anzeigen |
| Symcon-Prozess ...           | boolean | true         | |Informationen des symcon-Prozesses zusammenstellen |

| Betriebssystem | Partition 1     | Datenträger 1 |
| :------------- | :-------------- | :------------ |
| Ubuntu         | /dev/sda1       | /dev/sda      |
| Rasbian        | /dev/root       | nur unterstützt für HDD, keine SD-Karten (/dev/mmcblk0) oder USB-Sticks |

Bei einer Symbos ist _/dev/mmcblk0p2_ = _/mnt/system_ und _/dev/mmcblk0p3_ = _/mnt/data_; Datenträger werden hier nicht unterstützt.

## 6. Anhang

GUIDs

- Modul: `{20AACCAE-F43C-40C2-BECF-DFCCB70558D0}`
- Instanzen:
  - ServerInfo: `{99B3B506-0808-433A-9745-32CDD63BC307}`

## 7. Versions-Historie

- 1.25 @ 08.04.2025 10:33 
  - Änderung: Möglichkeit, das Auslesen von Festplatten-Temperaturen zu deaktivieren
    Grund dafür ist die Tatsache, das das dafür verwendete Hilfsprogramm "hddtemp" in neuen Linux-Versionen nicht mehr unterstützt wird

- 1.24 @ 02.01.2025 14:28
  - interne Änderung
  - update submodule CommonStubs

- 1.23 @ 24.02.2024 14:17
  - Fix: Korrekturen für Docker
    - Information zum Symcon-Prozess
	- Auswertung der Betriebssystem-Version
  - update submodule CommonStubs

- 1.22 @ 06.02.2024 09:46
  - Verbesserung: Angleichung interner Bibliotheken anlässlich IPS 7
  - update submodule CommonStubs

- 1.21 @ 03.11.2023 11:06
  - Fix: Information zum Symcon-Prozess für Symbox adaptiert
  - update submodule CommonStubs

- 1.20 @ 03.10.2023 14:36
  - Neu: Ermittlung von Speicherbedarf und Laufzeit (aktuell und für 31 Tage) und Anzeige im Panel "Information"
  - update submodule CommonStubs

- 1.19 @ 30.08.2023 11:00
  - Neu: Informationen zum Symcon-Prozess (Laufzeit, Memory- und CPU-Nutzung)
  - Fix: Korrektur der Berechnung der lesbaren Darstellung der Uptime
  - update submodule CommonStubs

- 1.18 @ 04.07.2023 14:36
  - Vorbereitung auf IPS 7 / PHP 8.2
  - update submodule CommonStubs
    - Absicherung bei Zugriff auf Objekte und Inhalte

- 1.17.1 @ 01.01.2023 15:13
  - Fix: es wurde versucht, die Bezeichnung des Variablenprofils zu übersetzen

- 1.17 @ 16.12.2022 10:40
  - Neu: Angabe der Einheit (GB/MB) der Partitionsgrößen
  - update submodule CommonStubs

- 1.16.1 @ 07.10.2022 13:59
  - update submodule CommonStubs
    Fix: Update-Prüfung wieder funktionsfähig

- 1.16 @ 05.07.2022 15:44
  - Verbesserung: IPS-Status wird nur noch gesetzt, wenn er sich ändert

- 1.15 @ 22.06.2022 09:57
  - Fix: der Update-Timer wurde initial nicht mehr gesetzt
  - Fix: Angabe der Kompatibilität auf 6.2 korrigiert
  - update submodule CommonStubs
    Fix: Ausgabe des nächsten Timer-Zeitpunkts
  - interne Funktionen sind nun private und ggfs nur noch via IPS_RequestAction() erreichbar
  - Fix: Angabe der Kompatibilität auf 6.2 korrigiert

- 1.14.1 @ 17.05.2022 15:38
  - update submodule CommonStubs
    Fix: Absicherung gegen fehlende Objekte

- 1.14 @ 06.05.2022 15:19
  - IPS-Version ist nun minimal 6.0
  - Anzeige der Modul/Bibliotheks-Informationen, Referenzen und Timer
  - Implememtierung einer Update-Logik
  - Überlagerung von Translate und Aufteilung von locale.json in 3 translation.json (Modul, libs und CommonStubs)
  - diverse interne Änderungen

- 1.13 @ 21.10.2021 16:35
  - es gibt nun auch "Raspberry Pi (Docker)"

- 1.12 @ 16.07.2021 14:47
  - Anpassungen an IPS 6.0
    - "Docker" heisst nun "Ubuntu (Docker)"

- 1.11 @ 15.07.2021 10:15
  - PHP_CS_FIXER_IGNORE_ENV=1 in github/workflows/style.yml eingefügt
  - Ermittlung von "CPU-Name" verbessert
  - Schalter "Instanz ist deaktiviert" umbenannt in "Instanz deaktivieren"
  - Ausgabe der unterstützten Betriebssysteme

- 1.10 @ 30.08.2020 12:40
  - LICENSE.md hinzugefügt
  - lokale Funktionen aus common.php in locale.php verlagert
  - Traits des Moduls haben nun Postfix "Lib"
  - GetConfigurationForm() überarbeitet
  - define's durch statische Klassen-Variablen ersetzt
  - Fix: fehlerhafte Variablenbezeichnung verwendet bei 2. Partition
  - Fix für Raspberry: Erkennung CPU-Modell und akt. CPU-Frequenz korrigiert
  - Temperaturüberwachung der Festplatten nur durchführen, wenn Festplatten eingetragen sind; damit ist das Programm "hddtemp" in dem Fall auch keine Voraussetzung mehr.
  - Ergänzung um die Platformen "SymBox" und "Docker"
  - Anzahl der Festplatten und Partitionen von 2 auf 4 erhöht
  - optionale Information zu dem Auslagerungsbereich (SwapTotal, SwapFree) hinzugefügt

- 1.9 @ 30.12.2019 10:56
  - Anpassungen an IPS 5.3
    - Formular-Elemente: 'label' in 'caption' geändert

- 1.8 @ 10.10.2019 17:27
  - Anpassungen an IPS 5.2
    - IPS_SetVariableProfileValues(), IPS_SetVariableProfileDigits() nur bei INTEGER, FLOAT
    - Dokumentation-URL in module.json
  - Umstellung auf strict_types=1
  - Umstellung von StyleCI auf php-cs-fixer

- 1.7 @ 09.08.2019 14:32
  - Schreibfehler korrigiert

- 1.6 @ 06.05.2019 16:03
  - Prüfung der Systemvoraussetzungen im Konfigurationsdialog
  - Konfigurationsdialog ist reduziert, wenn Systemvoraussetzungen fehlen

- 1.5 @ 29.03.2019 16:19
  - SetValue() abgesichert

- 1.4 @ 21.03.2019 17:04
  - Anpassungen IPS 5, Abspaltung von Branch _ips_4.4_
  - Schalter, um ein Modul (temporär) zu deaktivieren
  - Konfigurations-Element IntervalBox -> NumberSpinner

- 1.3 @ 21.12.2018 13:10
  - Standard-Konstanten verwenden

- 1.2 @ 19.08.2018 12:01
  - define's der Variablentypen
  - Schaltfläche mit Link zu README.md im Konfigurationsdialog

- 1.1 @ 15.08.2018 16:03
  - CPU-Temperatur/Ubuntu: da die Ermittlung der Temperatur aus _/sys/class/thermal/thermal_zone_ Hardware-abhängig ist, wird nun die höchste der dort ausgelesenen Temperaturen verwendet.<br>
  Das ist zwar nicht unbedingt "die" CPU-Temperatur, aber besser etwas zu hoch angegeben als zu niedirg.

- 1.0 @ 14.08.2018 17:21
  - Initiale Version
