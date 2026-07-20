# Repository Guidelines

## Project Structure & Module Organization

This repository is a server-rendered PHP application for Heppenheim heat-map slotting. Top-level PHP files are user-facing pages such as `dashboard.php`, `itemquery.php`, and `heatmap.php`. Shared navigation, session, and page setup live in files such as `headerincludes.php`, `sessioninclude.php`, and `verticalnav.php`.

- `globaldata/`: AJAX endpoints, modal content, and page-specific data queries.
- `globalfunctions/`: reusable PHP business and database helpers.
- `formpost/`: POST handlers for edits and workflow actions.
- `MySQLUpdates/`: scheduled or manual database refresh scripts.
- `js/` and `osscss/`: JavaScript and CSS assets.
- `connection/`: local database connection configuration; treat credentials as sensitive.

## Build, Test, and Development Commands

There is no compilation or dependency-install step. Run the application through XAMPP Apache and MySQL, then open `http://localhost/hep/login.php`.

```powershell
php -l dashboard.php
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Use the first command for a focused syntax check and the second before submitting broad PHP changes. `MySQLUpdates\update_hep.bat` runs the refresh sequence, but it contains environment-specific absolute paths and mutates database tables; verify paths, credentials, backups, and the target database before running it.

## Coding Style & Naming Conventions

Match the surrounding legacy style: four-space indentation, braces on the same line for control structures, and PHP embedded directly in HTML where the page already follows that pattern. Prefer `include_once` for shared dependencies. Keep endpoint filenames descriptive and consistent with existing lowercase conventions, for example `globaldata/itemhistorydata.php` or `formpost/postadditemcomment.php`. Avoid unrelated formatting changes in older files.

## Testing Guidelines

No automated test framework or coverage threshold is configured. Every change should pass PHP linting and a browser smoke test of the affected page. Exercise both successful and invalid form/AJAX paths, confirm session and warehouse behavior, and inspect PHP/Apache logs plus the browser console. Database-query changes should be tested against non-production data and checked for unintended writes.

## Commit & Pull Request Guidelines

Recent history uses short, topic-based commit subjects (for example, `slotmaster` and `count error`). Use a concise imperative subject with more context, such as `Fix slotmaster count calculation`. Keep commits focused. Pull requests should summarize user impact, list changed pages/endpoints and database effects, document manual verification, link the relevant issue, and include screenshots for visible UI changes.
