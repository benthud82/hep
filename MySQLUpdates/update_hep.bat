
D:\xampp\php\php.exe "D:\xampp\htdocs\hep\MySQLUpdates\tableupdate_slotmaster.php"
IF ERRORLEVEL 1 EXIT /B %ERRORLEVEL%

D:\xampp\php\php.exe "D:\xampp\htdocs\hep\MySQLUpdates\tableupdate_item_location.php"
IF ERRORLEVEL 1 EXIT /B %ERRORLEVEL%

D:\xampp\php\php.exe "D:\xampp\htdocs\hep\MySQLUpdates\tableupdate_npfcpcsettings.php"
IF ERRORLEVEL 1 EXIT /B %ERRORLEVEL%

D:\xampp\php\php.exe "D:\xampp\htdocs\hep\MySQLUpdates\tableupdate_picking.php"
IF ERRORLEVEL 1 EXIT /B %ERRORLEVEL%

D:\xampp\php\php.exe "D:\xampp\htdocs\hep\MySQLUpdates\DemandGrouping.php"
IF ERRORLEVEL 1 EXIT /B %ERRORLEVEL%

D:\xampp\php\php.exe "D:\xampp\htdocs\hep\MySQLUpdates\pkguqtypercentupdate.php"
IF ERRORLEVEL 1 EXIT /B %ERRORLEVEL%

REM D:\xampp\php\php.exe "D:\xampp\htdocs\hep\MySQLUpdates\NPFMVC_Update_volume.php"
D:\xampp\php\php.exe "D:\xampp\htdocs\hep\MySQLUpdates\NPFMVC_Update_currentgrids.php"
IF ERRORLEVEL 1 EXIT /B %ERRORLEVEL%

REM D:\xampp\php\php.exe "D:\xampp\htdocs\hep\MySQLUpdates\optimalbayloose_volume.php"
D:\xampp\php\php.exe "D:\xampp\htdocs\hep\MySQLUpdates\optimalbayloose_currentgrids.php"
IF ERRORLEVEL 1 EXIT /B %ERRORLEVEL%

D:\xampp\php\php.exe "D:\xampp\htdocs\hep\MySQLUpdates\itemscore.php"

IF ERRORLEVEL 1 EXIT /B %ERRORLEVEL%
D:\xampp\php\php.exe "D:\xampp\htdocs\hep\MySQLUpdates\replen_reslot_model_status.php"
