<?php


foreach (glob(app_path('Modules/*/routes/api.php')) as $routeFile) {
    require $routeFile;
}
