<?php

namespace HereYouGo\Exception;

use \Exception;
use HereYouGo\UI\Locale;

/** @var Base|Detailed|Exception $exception */

$message = $exception->getMessage();
$id = '';
$details = [];

if($exception instanceof Base) {
    $message = Locale::translate($message);
    $id = $exception->getId();

    if($exception instanceof Detailed)
        $details = $exception->getDetails();
}

?>

<section class="error">
    <h1><?php echo $message ?></h1>

    <?php if($details) { ?>
    <ul class="details">
        <?php foreach($details as $k => $v) { ?>
        <li></li>
        <?php } ?>
    </ul>
    <?php } ?>

    <?php if($id) echo Locale::translate('report_error_id')->replace(['id' => $id]) ?>
</section>