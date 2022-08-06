<?php

/**
 * Sample View File for Sunhill Framework
 *
 * (c) Sunhill Technology <www.sunhillint.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include_once (__DIR__ . '/header.php'); // include template file (optional)

?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <h3 class="text-danger text-center mb-5">About Us</h3>
            <?php
                // content which comes from the model file
                echo $result[0]['content'];

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