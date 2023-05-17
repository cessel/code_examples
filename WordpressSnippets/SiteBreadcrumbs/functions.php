<?php

namespace Flynt\Components\SiteBreadcrumbs;

use Flynt\Acf\SiteBreadcrumbsClass;

add_filter('Flynt/addComponentData?name=SiteBreadcrumbs', function ($data) {
    $breadcrumbs = new SiteBreadcrumbsClass();
    $data['breadcrumbs'] = $breadcrumbs->getBreadcrumbs();
    $data['show'] = ! ( ( is_home() || is_front_page() ) );
    return $data;
});
