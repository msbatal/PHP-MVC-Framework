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
    <title>Sunhill PHP Framework</title>
    <link rel="stylesheet" href="Public/css/style.css">
</head>
<body>
    <header>
        <a href="home"><img src="Public/img/logo.svg"></a>
    </header>
    <main>
        <div class="home">
            <h3 class="title">Welcome to SunHill PHP App Development Framework!</h3>
            <p>This is a sample home page of the framework.</p>
            <p>Click <a href="error">here</a> to see Error page.</p>
        </div>
    </main>
    <footer>
        &copy; <?php echo date("Y");?>, Coded by <a href="https://www.sunhillint.com" target="_blank">Sunhill Technology</a>.
    </footer>
</body>
</html>