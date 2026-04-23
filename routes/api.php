<?php


foreach (glob(app_path('Modules/*/Routes/api.php')) as $routeFile) {
    require $routeFile;
}
