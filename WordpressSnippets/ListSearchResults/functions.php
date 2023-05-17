<?php

namespace Flynt\Components\ListSearchResults;

use Flynt\Acf\SiteBreadcrumbsClass;
use function Flynt\Components\PersonListFilter\is_person_search;

add_filter('Flynt/addComponentData?name=ListSearchResults', function ($data) {

    // Получаем переменные поискового запроса
    $data['post_type'] = (isset($_GET['s']) && isset($_GET['post_type']) && !empty($_GET['post_type'])) ? $_GET['post_type'] : 'any';
    $search_phrase = (isset($_GET['s']) && !empty($_GET['s'])) ? $_GET['s'] : false;


    if ($search_phrase) {
        $posts = $data['posts']->get_posts();
        $highlighted = [];
        $footer_breadcrumbs = [];
        foreach ($posts as $post) {
            $highlighted[$post->ID]['ID'] = $post->ID;
            foreach ($post->custom as $custom_field) {
                if (is_array($custom_field)) {
                    $custom_field = array_pop($custom_field);
                }
                $custom_field = strip_tags($custom_field);
                $search_phrase_position = strpos($custom_field, $search_phrase);

                if ($search_phrase_position !== false) {
                    $highlighted[$post->ID]['string'] = prepare_string($custom_field, $search_phrase, $search_phrase_position);
                    break;
                }
            }
            $footer_breadcrumbs[$post->ID] = get_footer_breadcrumbs($post);
            $highlighted[$post->ID]['title'] = preg_replace("/(" . $search_phrase . ")/iu", '<span class="highlighted">$1</span>', $post->post_title);
        }
        $data['highlighted'] = $highlighted;
        $data['footer_breadcrumbs'] = $footer_breadcrumbs;
    }
    return $data;
});
/**
 * Функция подготовки строки для вывода на странице результатов поиска
 * @param string $string
 * @param string $search_phrase
 * @param int    $search_phrase_position
 *
 * @return string
 */
function prepare_string(string $string, string $search_phrase, int $search_phrase_position): string {
    $before_string_num_symbols = 20;
    $string_length = 600;
    $start_end_symbols = '...';

    $prepared_string = substr($string, $search_phrase_position - $before_string_num_symbols, $string_length);
    $prepared_string = trim($prepared_string);
    $prepared_string = str_replace($search_phrase, '<span class="highlighted">' . $search_phrase . '</span>', $prepared_string);
    $re = '/^(\s)/m'; // выделяем поисковый запрос

    $prepared_string = preg_replace('[\s]', " ", $prepared_string); // Обрезаем лишние символы в т.ч. переносы строк
    $prepared_string = mb_substr($prepared_string, 0, -1); // Обрезаем первый символ во избежание ошибок
    $prepared_string = mb_substr($prepared_string, 1); // Обрезаем последний символ во избежание ошибок
	return $start_end_symbols . $prepared_string . $start_end_symbols;
}

/**
 * Функция возвращает HTML строку из категорий внизу каждого результата поиска
 *
 * @param \Timber\Post $post
 *
 * @return string
 */
function get_footer_breadcrumbs(\Timber\Post $post): string {
    $breadcrumbs = new SiteBreadcrumbsClass($post->ID);

    $breadcrumbs = $breadcrumbs->getBreadcrumbs();
	$breadcrumbs_html = [];

    foreach ($breadcrumbs as $breadcrumb) {
        $breadcrumbs_html[] = '<span class="ksma-search-result-breadcrumbs__link">' . $breadcrumb['anchor'] . '</span>';
    }

    return '<div class="ksma-search-result-breadcrumbs">' . implode('<span class="ksma-search-result-breadcrumbs__delimiter">/</span>', $breadcrumbs_html) . '</div>';
}

/**
 * Фильтр позволяет добавлять в условия поиска типы записей,
 * запрос передется при помощи GET-запроса с переменной post_type
 */
add_filter('pre_get_posts', function ($query) {
    if (! is_admin() && $query->is_search() && $query->is_main_query()) {
        $post_types = (isset($_GET['s']) && isset($_GET['post_type']) && !empty($_GET['post_type'])) ? $_GET['post_type'] : 'any';
        $post_types = explode('_', $post_types);
        $query->set('post_type', $post_types);
    }
    return $query;
}, 1);

/**
 * Фильтр добавляет поиск по всем мета-полям записей
 */
add_filter('posts_clauses', function ($clauses) {
    global $wpdb;

    if ((! is_search() || ! is_main_query()) && !is_person_search()) {
        return $clauses;
    }

        $clauses['join'] .= " LEFT JOIN $wpdb->postmeta kmpm ON (ID = kmpm.post_id)";

        $clauses['where'] = preg_replace(
            "/OR +\( *$wpdb->posts.post_content +LIKE +('[^']+')/",
            "OR (kmpm.meta_value LIKE $1) $0",
            $clauses['where']
        );

    // если нужно искать в указанном метаполе
    //$clauses['where'] .= $wpdb->prepare(' AND kmpm.meta_key = %s', 'my_meta_key' );

    $clauses['distinct'] = 'DISTINCT';

    // дебаг итогового запроса
    0 && add_filter('posts_request', function ($sql) {
        die($sql);
    });

    return $clauses;
});