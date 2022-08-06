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
            <h3 class="text-danger text-center mb-5">Our Services</h3>
        </div>
        <?php
            if ($action == "detail") { // if parameter exists (this variable created by the controller file)
                echo "
                <div class='col-12 text-center'>
                    <img src='".$result[0]['image']."' class='img-responsive img-thumbnail' style='height:400px'>
                    <div class='card-body text-center'>
                        <h5 class='card-title text-danger mt-3 mb-4'><b>".$result[0]['title']."</b></h5>
                        <p class='card-text mb-5'>".$result[0]['description']."</p>
                        <p class='mt-5'><a href='services' class='btn btn-secondary btn-sm text-light'><i class='bi bi-arrow-bar-left me-2'></i> Go Back</a></p>
                    </div>
                </div>
                ";
            } else { // if parameter does not exist
                // contents which come from the model file
                foreach ($result as $row) {
                    echo "
                    <div class='col-md-6 col-lg-4'>
                        <div class='card mb-5'>
                            <img src='".$row['image']."' class='card-img-top'>
                            <div class='card-body text-center'>
                                <h5 class='card-title'>".$row['title']."</h5>
                                <p class='card-text mb-4'>".substr(strip_tags($row['description']), 0, 250)."...</p>
                                <a href='services/detail/".$row['id']."' class='btn btn-sm btn-secondary text-light'><i class='bi bi-book-half me-2'></i> Show More</a>
                            </div>
                        </div>
                    </div>
                    ";
                }
            }
            
        ?>
    </div>
</div>

<?php

include_once (__DIR__ . '/footer.php'); // include template file (optional)

?>