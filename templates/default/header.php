<?php

namespace HereYouGo;

use HereYouGo\UI\Locale;use HereYouGo\UI\Page;use HereYouGo\UI\Resource;

/** @var Page $page */

$title = Config::get('application_name');
if(Locale::isTranslatable($page->id.'_page'))
    $title .= ($title ? ' - ' : '').Locale::translate($page->id.'.page');

$nav_active = function($id) use($page) {
    return ($id === $page->id) ? 'active' : '';
};

$user = Auth::getUser();

?><!DOCTYPE html>
<html>
    <head>
        <title><?php echo Sanitizer::sanitizeOutput($title) ?></title>

        <?php foreach(Resource::gather('styles') as $file) { ?>
            <link rel="stylesheet" href="<?php echo $file ?>" type="text/css" media="all" />
        <?php } ?>

        <?php foreach(Resource::gather('scripts') as $file) { ?>
            <script src="<?php echo $file ?>" type="text/javascript"></script>
        <?php } ?>
    </head>

    <body>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <a class="navbar-brand" href="{url:/}"><?php echo Sanitizer::sanitizeOutput(Config::get('application_name')) ?></a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <ul class="navbar-nav mr-auto">
                <li class="nav-item"><a class="nav-link" href="#"><span class="fas fa-folder-open"></span> Load</a></li>
            </ul>

            <?php if(Auth::getSP()) { ?>
            <ul class="navbar-nav">
                <li class="nav-item <?php echo $nav_active($user ? 'log-out' : 'log-in') ?>">
                    <?php if($user) { ?>
                        <a class="nav-link" href="{url:/log-out}">{tr:auth.log-out}</a>
                    <?php } else { ?>
                        <a class="nav-link" href="{url:/log-in}">{tr:auth.log-in}</a>
                    <?php } ?>
                </li>
            </ul>
            <?php } ?>
        </nav>

        <main data-page="<?php echo $page->id ?>">
