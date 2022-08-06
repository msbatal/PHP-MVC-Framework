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
            <h3 class="text-danger text-center mb-5">Public Comments</h3>
        </div>
        <form action="comments/send" method="post" onsubmit="return validate();">
            <div class="row">
                <div class="col-sm-5 col-md-4" style="margin-bottom:15px;">
                    <input type="text" name="name" id="name" class="form-control" placeholder="Your Name">
                </div>
                <div class="col-sm-7 col-md-6" style="margin-bottom:15px;">
                    <input type="text" name="comment" id="comment" class="form-control" placeholder="Your Comment">
                </div>
                <div class="col-sm-12 col-md-2 text-end" style="margin-bottom:15px;">
                    <button type="submit" class="btn btn-success"><i class="bi bi-send-fill me-2"></i> Save</button>
                </div>
            </div>
        </form>
        <div class="col-12">
            <hr class="hr mt-4">
        </div>
        <?php
            // result session which created by the model file
            if (!empty($_SESSION["sunalert"])) {
                echo '<div class="col-12 mt-3">'.$_SESSION["sunalert"].'</div>';
                unset($_SESSION["sunalert"]);
            }

            // contents which come from the model file
            foreach ($result as $row) {
                echo "
                <div class='col-12 mt-3 mb-4'>
                    <div class='card'>
                        <div class='card-body bg-light'>
                            <h6 class='card-title'>".$row['name']."</h6>
                            <p class='card-text mb-4'>".$row['comment']."</p>
                            <div class='text-muted text-end'><small>".$row['datetime']."</small></div>
                        </div>
                    </div>
                </div>
                ";
            }
        ?>
        </div>
    </div>
</div>

<?php

include_once (__DIR__ . '/footer.php'); // include template file (optional)

?>