<?php

namespace HereYouGo;

use HereYouGo\UI\Locale;use HereYouGo\UI\Page;use HereYouGo\UI\Resource;

/** @var Page $page */

$title = Config::get('application_name');
if(Locale::isTranslatable($page->id.'_page'))
    $title .= ($title ? ' - ' : '').Locale::translate($page->id.'_page');

?><!DOCTYPE html>
<html>
    <head>
        <title>Game of Life</title>

        <?php foreach(Resource::gather('styles') as $file) { ?>
            <link rel="stylesheet" href="<?php echo $file ?>" type="text/css" media="all" />
        <?php } ?>

        <?php foreach(Resource::gather('scripts') as $file) { ?>
            <script src="<?php echo $file ?>" type="text/javascript"></script>
        <?php } ?>

        <link rel="stylesheet" href="jquery-ui/jquery-ui.min.css" type="text/css" media="all" />

        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous" />

        <link rel="stylesheet" href="styles.css" type="text/css" media="all" />

        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" type="text/javascript"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" type="text/javascript"></script>
        <script src="jquery-ui/jquery-ui.min.js" type="text/javascript"></script>

        <script src="https://malsup.github.io/jquery.blockUI.js" type="text/javascript"></script>

        <script src="script.js" type="text/javascript"></script>
    </head>

    <body>
        <header><?php echo Sanitizer::sanitizeOutput($title) ?></header>

        <nav>
            <ul>
                <li><a href="#"><span class="fas fa-folder-open"></span> Load</a></li>

                <?php if(Auth::getSP()) { ?>
                    <li class="right">
                        <?php if(Auth::hasUser()) { ?>
                            <a href="{url:/log-in}">{tr:log-in}</a>
                        <?php } else { ?>
                            <a href="{url:/log-out}">{tr:log-out}</a>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>
        </nav>

        <main data-page="<?php echo $page->id ?>">
