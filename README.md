# IPSymconServerInfo

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Module-Version](https://img.shields.io/badge/Modul_Version-1.6-blue.svg)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![StyleCI](https://github.styleci.io/repos/126683101/shield?branch=master)](https://github.styleci.io/repos/144562617)

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

 - IP-Symcon ab Version 5<br>
   Version 4.4 mit Branch _ips_4.4_ (nur noch Fehlerkorrekturen)

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

| Eigenschaft               | Typ     | Standardwert | Beschreibung |
| :------------------------ | :------ | :----------- | :----------- |
| Instanz ist deaktiviert   | boolean | false        | Instanz temporär deaktivieren |
|                           |         |              | |
| Aktualisiere Daten ...    | integer | 5            | Aktualisierungsintervall, Angabe in Minuten |
|                           |         |              | |
| Gerät für Partition ...   | string  | s.u.         | nur Patitionen mit Dateisystem |
| Gerät für Datenträger ... | string  | s.u.         | nur Datenträger mit S.M.A.R.T |

| Betriebssystem | Partition 1 | Datenträger 1 |
| :------------- | :---------- | :------------ |
| Ubuntu         | /dev/sda1   | /dev/sda      |
| Rasbian        | /dev/root   | nur unterstützt für HDD, keine SD-Karten (/dev/mmcblk0) oder USB-Sticks |

## 6. Anhang

GUIDs

- Modul: `{20AACCAE-F43C-40C2-BECF-DFCCB70558D0}`
- Instanzen:
  - ServerInfo: `{99B3B506-0808-433A-9745-32CDD63BC307}`

## 7. Versions-Historie

- 1.6 @ 06.05.2019 16:03<br>
  - Prüfung der Systemvoraussetzungen im Konfigurationsdialog
  - Konfigurationsdialog ist reduziert, wenn Systemvoraussetzungen fehlen

- 1.5 @ 29.03.2019 16:19<br>
  - SetValue() abgesichert

- 1.4 @ 21.03.2019 17:04<br>
  - Anpassungen IPS 5, Abspaltung von Branch _ips_4.4_
  - Schalter, um ein Modul (temporär) zu deaktivieren
  - Konfigurations-Element IntervalBox -> NumberSpinner

- 1.3 @ 21.12.2018 13:10<br>
  - Standard-Konstanten verwenden

- 1.2 @ 19.08.2018 12:01<br>
  - define's der Variablentypen
  - Schaltfläche mit Link zu README.md im Konfigurationsdialog

- 1.1 @ 15.08.2018 16:03<br>
  - CPU-Temperatur/Ubuntu: da die Ermittlung der Temperatur aus _/sys/class/thermal/thermal_zone_ Hardware-abhängig ist, wird nun die höchste der dort ausgelesenen Temperaturen verwendet.<br>
  Das ist zwar nicht unbedingt "die" CPU-Temperatur, aber besser etwas zu hoch angegeben als zu niedirg.

- 1.0 @ 14.08.2018 17:21<br>
  Initiale Version
