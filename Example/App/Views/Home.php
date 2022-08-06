<?php

/**
 * This file is part of the Sunhill Framework package.
 *
 * (c) Mehmet Selcuk Batal, Sunhill Technology <batalms@gmail.com>
 *
 * Licensed under The GNU Lesser General Public License, version 3.0
 * Redistributions of files must retain the above copyright notice.
 */

include_once (__DIR__ . '/header.php'); // include template file (optional)

?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <h4 class="text-danger text-center mb-5">Welcome to SunHill PHP App Development Framework!</h4>

            <?php
                // contents which come from the model file
                echo $result[0]['section1'];
                echo $result[0]['section2'];
                echo $result[0]['section3'];

                /*
                foreach ($result[0] as $content) {
                    echo $content;
                }
                */
            ?>

        </div>
    </div>
</div>

<?php

include_once (__DIR__ . '/footer.php'); // include template file (optional)

?>