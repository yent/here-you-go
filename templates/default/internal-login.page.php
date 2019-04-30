<?php

use HereYouGo\UI\Locale;

/** @var string $state */
/** @var string $target */

$form = new \HereYouGo\Auth\SP\Internal\Form\LogIn($target, $state);

echo $form->getHtml();

/*
<form class="col-md-2 offset-md-5" method="post" action="">
    <input type="hidden" value="<?php echo $target ?>" />

    <?php if($state) { ?>
        <div class="alert alert-danger"><?php echo Locale::translate("internal-login.$state") ?></div>
    <?php } ?>

    <div class="form-group">
        <label for="login">{tr:internal-login.login}</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text fa fa-user" id="login-prepend"></span>
            </div>
            <input type="text" name="login" class="form-control" aria-describedby="login-prepend" placeholder="{tr:internal-login.login}">
        </div>
    </div>

    <div class="form-group">
        <label for="password">{tr:internal-login.password}</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text fa fa-lock" id="password-prepend"></span>
            </div>
            <input type="password" name="password" class="form-control" aria-describedby="password-prepend" placeholder="{tr:internal-login.password}">
        </div>
    </div>

    <div class="form-row justify-content-center">
        <button type="submit" class="btn btn-primary">{tr:auth.log-in}</button>
    </div>
</form>

*/