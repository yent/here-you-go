<?php

use HereYouGo\UI\Locale;

/** @var string $state */
/** @var string $target */

?>
<form method="post" action="">
    <?php if($state) { ?>
        <div class="error"><?php echo Locale::translate("auth.$state") ?></div>
    <?php } ?>

    <label for="login">
        {tr:auth.login}
        <input type="text" name="login" />
    </label>

    <label for="password">
        {tr:auth.password}
        <input type="password" name="login" />
    </label>

    <input type="hidden" value="<?php echo $target ?>" />
    <input type="submit" value="{tr:auth.log_in}" />
</form>