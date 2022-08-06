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
        <div class="col-12 text-center">
            <h3 class="text-danger mb-5">Contact Us</h3>

            <iframe src="<?php echo $result[0]['map']; ?>" width="100%" height="300" style="border:1px solid #ccc;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="mb-5"></iframe>

            <p class="h5 mb-4 text-danger fw-bold">
                <?php
                    // content which comes from the model file
                    echo $result[0]['company'];
                ?>
            </p>

            <p class="h6 text-success"><b><u>Office Address</u></b></p>
            <p class="h6 mb-4">
                <?php
                    // content which comes from the model file
                    echo $result[0]['address'];
                ?>
            </p>

            <p class="h6 text-success"><b><u>Phone Number</u></b></p>
            <p class="h6 mb-4">
                <?php
                    // content which comes from the model file
                    echo $result[0]['phone'];
                ?>
            </p>

            <p class="h6 text-success"><b><u>E-mail Address</u></b></p>
            <p class="h6 mb-4">
                <?php
                    // content which comes from the model file
                    echo $result[0]['email'];
                ?>
            </p>

        </div>
    </div>
</div>

<?php

include_once (__DIR__ . '/footer.php'); // include template file (optional)

?>