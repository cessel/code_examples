<?php

namespace Flynt\Components\BlockMap;

use Flynt\FieldVariables;

add_filter('Flynt/addComponentData?name=BlockMap', function ($data) {

    $data['nonce'] = wp_create_nonce('set-map-preloader-nonce');
    $data['admin_url'] = admin_url('admin-ajax.php');

    if (isset($data['address']) && !empty($data['address'])) {
        $map_data = get_cached_map($data['address']);
        if ($map_data) {
            $data['map'] = $map_data;
            $data['preloader_img'] = get_map_preloader($data['map']['lng'], $data['map']['lat']);
        }
    }

    return $data;
});

function getACFLayout()
{
    return [
        'name' => 'BlockMap',
        'label' => 'Block Map',
        'sub_fields' => [
            FieldVariables\getBackground(),
            [
                'label' => __('Heading', 'flynt'),
                'name' => 'heading',
                'type' => 'text',
                'required' => 1,
            ],
            [
                'label' => 'Address',
                'name' => 'address',
                'type' => 'text',
            ],
        ]
    ];
}

if (wp_doing_ajax()) {
    add_action('wp_ajax_set_map_preloader', 'ajax_set_map_preloader');
    add_action('wp_ajax_nopriv_set_map_preloader', 'ajax_set_map_preloader');
}

/**
 * Проверка того, что запрос пришел откуда ожидается
 * @param $nonce_slug
 *
 * @return array
 */
function before_ajax_handler($nonce_slug): array {
    check_ajax_referer($nonce_slug);

    $return['status'] = 0;
    $return['error'] = '';
    $return['html'] = '';
    $return['debug'] = '';

    return $return;
}

/**
 * Обработчик ajax-запроса обновления кеша карты, устанавливает параметры:
 */
function ajax_set_map_preloader()
{

    $return = before_ajax_handler('set-map-preloader-nonce');
    $data = $_POST['data'];
	$map_data = false;

	$map_preloader = get_cached_map();
    if (!$map_preloader) {
        $map_data = set_cached_map($data);
    }

    $return['debug'] = $map_data;

    wp_send_json($return, '200');
}

/**
 * Функция получает данные закешированной карты по адресу, если они есть, иначе возврает false.
 * Также эта функция удаляет просроченный кеш.
 *
 * @param string $address
 *
 * @return false|mixed
 */
function get_cached_map( string $address = '')
{
    $map_preloaders = get_option('map_preloaders');
    if (is_array($map_preloaders)) {
        foreach ($map_preloaders as $index => $map_preloader) {
            if (!empty($address) && $map_preloader['address'] == $address) {
                if ($map_preloader['timestamp'] > (time() - 60 * 60 * 24 * 30)) {
                    return $map_preloader;
                } else {
                    unset($map_preloaders[$index]);
                    update_option('map_preloaders', $map_preloaders);
                    return false;
                }
            }
        }
    }
    return false;
}

/**
 * Функция обновления данных кеша карты, устанавливает параметры:
 * lat - широта
 * lng - долгота
 * address - Адрес
 * preloader_img - статическое изображение карты
 * timestamp - метка времени для обновления кеша
 *
 * @param array $map_data
 *
 * @return false|array
 */
function set_cached_map( array $map_data = [])
{
    if (empty($map_data['lng']) || empty($map_data['lat']) || empty($map_data['address'])) {
        return false;
    }

    $map_data['preloader_img'] = get_map_preloader($map_data['lng'], $map_data['lat']);

    if (! empty($map_data)) {
        $default = [
            'lat'           => '',
            'lng'           => '',
            'address'       => '',
            'preloader_img' => '',
            'timestamp'     => time(),
        ];

        $map_data = array_merge($default, $map_data);

        $map_preloaders                         = get_option('map_preloaders');
        $map_preloaders[ $map_data['address'] ] = $map_data;

        update_option('map_preloaders', $map_preloaders);

        return $map_preloaders;
    } else {
        return false;
    }
}

/**
 * Функция возвращает изображение статической карты по переданным координатам
 * @param float $lng
 * @param float $lat
 *
 * @return string
 */
function get_map_preloader(float $lng, float $lat): string {
    return "https://static-maps.yandex.ru/1.x/?ll=" . $lng . "," . $lat . "&size=650,181&z=14&l=map";
}

