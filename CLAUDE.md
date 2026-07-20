# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

"hep" (Heppenheim Heat Map Slotting) is a warehouse slotting/heat-map dashboard: a legacy procedural PHP application with no framework, no Composer, no build step, and no test suite. It runs under XAMPP (Apache + MySQL) with this directory served at `http://localhost/hep/`.

## Running and checking

- Serve via XAMPP Apache; data lives in the local MySQL database named `hep` (PDO connection configured in `connection/connection_details.php` — alternate environments are switched by commenting/uncommenting blocks in that file).
- Syntax-check a file with `php -l <file>` — this is the only automated check available.
- Nightly ETL: `MySQLUpdates/update_hep.bat` runs the scripts in `MySQLUpdates/` via `php.exe` (note: the .bat hardcodes `D:\xampp\...` paths, which do not match this machine's `C:\Users\...\xampp` layout — don't assume it runs as-is locally).

## Architecture

Three kinds of PHP files, distinguished by directory:

- **Top-level `*.php`** — full pages. Standard skeleton: `include 'sessioninclude.php'` (session auth guard; redirects to `signin.php` unless `$_SESSION["Login"] == "YES"`), `connection/connection_details.php`, then `headerincludes.php` inside `<head>`, and `horizontalnav.php` / `verticalnav.php` at the top of `<body>`. Pages often run their own PDO queries inline before rendering.
- **`globaldata/`** — AJAX read endpoints. Called from page JS via jQuery `$.post`, they read `$_POST` params, query MySQL, and echo HTML fragments or JSON (for DataTables tables, Highcharts graphs, SVG heat maps, dropdowns, badges).
- **`formpost/`** — AJAX write endpoints (INSERT/UPDATE). They echo a Bootstrap success/error modal plus a `<script>` that toggles it.

Endpoints in `globaldata/` and `formpost/` include the connection with a `../` prefix (`include_once '../connection/connection_details.php'`) since they're one level down.

Shared helpers: `globalfunctions.php` (root) and `globalfunctions/` (`custdbfunctions.php`, `slottingfunctions.php`, `newitem.php`).

`MySQLUpdates/` scripts are batch table-rebuild/scoring jobs (slotmaster, item_location, picking, item scores, optimal bay calculations) — they are CLI-run, not web endpoints.

## Cross-repo dependencies

Many files reference assets **outside this repository**, one level up in `htdocs` (sibling projects/files): `../DataTables/`, `../highcharts.js`, `../jquery-ui.js`, `../svg-pan-zoom.js`, `../BootstrapXL.css`, `../favicon.ico`, `../globalfunctions/slottingfunctions.php`, `../CustomerAudit/js/offsys_dash.js`. These are required at runtime but not versioned here — don't "fix" the paths, and expect them to be missing in a bare checkout.

## Conventions

- Frontend stack: jQuery 1.x/2.x, Bootstrap 3, DataTables, Highcharts, Font Awesome 4 — mostly from CDNs (see `headerincludes.php`), plus local `js/offsys_dash.js` and `osscss/` styles.
- SQL is written as raw strings with PHP variables interpolated directly (prepared statements are used but without bound parameters). This is the prevailing pattern; when writing new queries, prefer real PDO bound parameters — it stays compatible with the existing `$conn1` PDO connection.
- Heat-map pages render warehouse bays as SVG rects positioned from the `vectormap` table (XPOS/YPOS/BAYHEIGHT/BAYWIDTH), colored by pick-volume thresholds.
