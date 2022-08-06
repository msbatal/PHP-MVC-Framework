<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Mehmet Selcuk Batal, Sunhill Technology <batalms@gmail.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0
 * Redistributions of files must retain the above copyright notice.
 */

?>
<!doctype html>
<html>
<head>
    <base href="<?php echo (SYS_BASEURL); ?>/"> 
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="Public/img/favicon.png">
    <title>Error - Sunhill PHP Framework</title>
    <link rel="stylesheet" href="Public/css/style.css">
</head>
<body>
    <header>
        <a href="home"><img src="Public/img/logo.svg"></a>
    </header>
    <main>
        <div class="error">
            <img src="Public/img/error.png">
            <h3 class="title">Sorry!</h3>
            <h3>The Page You're Looking For Was Not Found.</h3>
        </div>
    </main>
    <footer>
        &copy; <?php echo date("Y");?>, Coded by <a href="https://www.sunhillint.com" target="_blank">Sunhill Technology</a>.
    </footer>
</body>
</html>