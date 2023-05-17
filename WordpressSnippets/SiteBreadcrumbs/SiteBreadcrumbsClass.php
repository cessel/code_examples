<?php

/**
 * Date: 11.01.2022
 * Time: 21:48
 */

namespace Flynt\Acf;

use WP_Post;
use WP_Term;

/**
 * Класс для создания хлебных крошек сайта
 * Class SiteBreadcrumbsClass
 * @package Flynt\Acf
 *
 */

class SiteBreadcrumbsClass
{
    /**
     * Массив для хранятся данных генерации хлебных крошках
     * $hierarhy['type'] - тип записи для создания ХК:
     *  - 'text': просто вывести все что распологается в 'value'
     *  - 'post': все отдельные записи любого типа
     *  - 'term': термин таксономии
     * $hierarhy['value'] - данные для обработки либо id записи/термина таксономии либо текст для вывода
     *
     * @var array
     *
     */
    protected array $hierarchy = [];

    /**
     * Массив для хранения данных для вывода хлебных крошек в шаблоне
     * Элементы массива:
     * $breadcrumbs['link'] - адрес сслыки c элемента хлебных крошек. Если пусто то ссылка не выводиться.
     * $breadcrumbs['anchor'] - Текст ссылки который отоборажается в хлебных крошках
     * @var array
     */
    protected array $breadcrumbs = [];

    /**
     * В переменной содержится объект текущей страницы полученный при помощи функции get_queried_object()
     *
     * @var WP_Post|\WP_Post_Type|WP_Term|\WP_User|null
     */
    protected $current_object;

    /**
     * Массив для хранения данных о сопоставлении Заголовка типа записи
     * и Заголовка страницы с архивами этого типа записи
     *
     * @var array
     */
    protected array $post_archive_matching = [
        'Photos'        => 'Фото',
        'Videos'        => 'Видео',
        'Вопросы'       => 'Ответы на частые вопросы',
        'FAQ'           => 'Ответы на частые вопросы',
        'Сотрудники'    => 'Все сотрудники',
    ];

    /**
     * Массив для хранения данных о сопоставлении ярлыка таксономии
     * и Заголовка страницы с архивами этой таксономии
     *
     * @var array
     */
    protected array $taxonomy_archive_matching = [
        'faq' => 'Ответы на частые вопросы',
    ];

    /**
     *
     * Массив для хранения данных о типах записи и таксономиях у которых используются архивы wordpress,
     * используется на страницах архива таксономии
     *
     * @var array
     */
    protected array $post_types_with_archive = [
        'faq','question'
    ];

    /**
     *
     * Массив для хранения данных о сопоставлении типа записи и ее таксономии.
     *
     * Так как у типазаписи может быть разные таксономии,
     * необходимо прописать явно, ту таксонимию, архив которой выводится на соответствуюей странице
     *
     * @var array
     */
    protected array $post_types_with_archive_matching = [
        'question' => 'faq'
    ];

    /**
     *
     * Массив для хранения данных о типах записи и таксономиях у которых используются архивы wordpress,
     * используется на страницах отдельной записи
     *
     * @var array
     */
    protected array $singlepost_types_with_archive = [
        'question'
    ];

    /**
     * Создание экземпляра класса. Возможно задание ID страницы, для которой нужно получить хлебные крошки.
     * Ограничение - задание хлебных крошек вручную, на данный момент, работает только для постов ( всех типов )
     *
     * @param int $current_post_id
     */
    public function __construct(int $current_post_id = 0)
    {
        $this->current_object = ($current_post_id === 0) ? get_queried_object() : get_post($current_post_id);
        $this->setMainPage();
        $this->setStaticPages();
    }

    /**
     * Функция добавляет главную странциу, в данные для вывода хлебных крошек, по тому, как она задана в настройках
     */
    private function setMainPage(): void {
        $this->addBreadcrumb('post', get_option('page_on_front'));
    }

    /**
     * Функция добавляет все страницы, в данные для вывода хлебных крошек
     */
    private function setStaticPages(): void {
        if ($this->current_object instanceof WP_Post) {
            if ($this->current_object->post_type == 'page') {
                $this->setCurrentPostHierarchy();
            } else if (in_array($this->current_object->post_type, $this->singlepost_types_with_archive)) {
                $this->setSingleArchivePostHierarchy();
            } else {
                $this->setArchivePostHierarchy();
            }
        } else {
            if (is_search()) {
                $this->addBreadcrumb('text', __('Search Results', 'flynt'));
            } else if (is_archive()) {
                $post_type_archive_page = $this->getPageByTitle($this->taxonomy_archive_matching[$this->current_object->taxonomy]);
                $this->setPostHierarchy($post_type_archive_page);
                if (isset($post_type_archive_page->ID)) {
                    $this->addBreadcrumb('post', $post_type_archive_page->ID);
                }

                $this->setTermHierarchy($this->current_object);
                $this->addBreadcrumb('term', $this->current_object->term_id);
            }
        }
    }

    /**
     * Функция добавляет текущую страницу, и ее иерархию, в данные для вывода хлебных крошек,
     * используется для отдельных записей у которых НЕ используются обычные архивы вордпресс
     */
    public function setCurrentPostHierarchy(): void {
        $this->setPostHierarchy($this->current_object->ID);
        $this->addBreadcrumb('post', $this->current_object->ID);
    }

	/**
	 * Функция добавляет текущую страницу, и ее иерархию, в данные для вывода хлебных крошек,
	 * используется для таксономий
	 *
	 * @param int $archive_page_id
	 */
    public function setArchivePostHierarchy(int $archive_page_id = 0): void {
        if ($archive_page_id === 0) {
            $post_type = get_post_type_object($this->current_object->post_type);
            $post_type_label = (isset($this->post_archive_matching[$post_type->label]))
                ? $this->post_archive_matching[$post_type->label]
                : $post_type->label;
            $post_type_archive_page = $this->getPageByTitle($post_type_label);
            $this->setPostHierarchy($post_type_archive_page);

            if (isset($post_type_archive_page->ID)) {
                $this->addBreadcrumb('post', $post_type_archive_page->ID);
            }

            if (in_array($this->current_object->post_type, $this->post_types_with_archive)) {
                $terms = get_the_terms($this->current_object, $this->current_object->post_type);
                foreach ($terms as $term) {
                    $this->addBreadcrumb('term', $term->term_id);
                }
            }
            $this->addBreadcrumb('post', $this->current_object->ID);
        } else {
            $this->setPostHierarchy(get_post($archive_page_id));
        }
    }

    /**
     * Функция добавляет текущую страницу, и ее иерархию, в данные для вывода хлебных крошек,
     * используется для типов записей у которых используются обычные архивы вордпресс
     */
    public function setSingleArchivePostHierarchy(): void {
        $terms = get_the_terms($this->current_object, $this->post_types_with_archive_matching[$this->current_object->post_type]);
        $base_post = $this->getPageByTitle($this->taxonomy_archive_matching[$this->post_types_with_archive_matching[$this->current_object->post_type]]);

        $this->setArchivePostHierarchy($base_post->ID);
        $this->addBreadcrumb('post', $base_post->ID);
        $this->setTermHierarchy($terms[0]);
        $this->addBreadcrumb('term', $terms[0]->term_id);
        $this->addBreadcrumb('post', $this->current_object->ID);
    }

    /**
     * Рекурсивная функция по поиску и установке всей иерархии страницы в данные для вывода хлебных крошек.
     * Используется для хлебных крошек отдельных страниц
     *
     * @param $id int|WP_Post - Объект поста или его id
     */
    public function setPostHierarchy( $id ): void {
        $parent = wp_get_post_parent_id($id);
        if ($parent != 0) {
            $this->setPostHierarchy($parent);
            $this->addBreadcrumb('post', $parent);
        }
    }

    /**
     * Рекурсивная функция по поиску и установке всей иерархии термина таксономии в данные для вывода хлебных крошек.
     * Используется для хлебных крошек термином таксономий
     *
     * @param $term WP_Term|int - объект текрмина таксономии или id термина ксономии
     */
    public function setTermHierarchy( $term ): void {
        $term_obj = get_term($term);
        $parent = wp_get_term_taxonomy_parent_id($term_obj->term_id, $term_obj->taxonomy);
        if ($parent != 0) {
            $this->setTermHierarchy($parent);
            $this->addBreadcrumb('term', $parent);
        }
    }

    /**
     * Функция добавляет элемент данные для вывода хлебных крошек в заданном формате
     *
     * @param $type                 string      - тип записи в данных для генерации хлебных крошек
     * @param $value                int|string  - значение в данных для генерации хлебных крошек,
     *                              id или текст для вывода в хлебных крошках
     */
    public function addBreadcrumb( string $type, $value ): void {
        $this->hierarchy[] = [ 'type' => $type, 'value' => $value];
    }

    /**
     * Функция для получения хлебных крошек для вывода в шаблоне
     *
     * @return array
     */
    public function getBreadcrumbs(): array {
	    $this->breadcrumbs = [];
        foreach ($this->hierarchy as $item) {
            $this->breadcrumbs[] = $this->getBreadcrumbData($item);
        }
        return $this->breadcrumbs;
    }

    /**
     * Функция возвращает элемент массива хлебных крошек,
     * по переданному элементу лля генерации данных хлебных корошек
     *
     * @param $hierarchy_item
     *
     * @return array
     */
    protected function getBreadcrumbData($hierarchy_item): array {
        switch ($hierarchy_item['type']) {
            case 'post':
                $item   = get_post($hierarchy_item['value']);
                $link   = ($item && $this->isBreadcrumbHasLink($hierarchy_item) ) ? get_the_permalink($item->ID) : '';
                $anchor = ($item) ? $item->post_title : $hierarchy_item['value'];
                return ['link' => $link, 'anchor' => $anchor];
            case 'term':
                $item   = get_term($hierarchy_item['value']);
                $link   = ($item && $this->isBreadcrumbHasLink($hierarchy_item)) ? get_term_link($item->term_id) : '';
                $anchor = ($item) ? $item->name : $hierarchy_item['value'];
                return ['link' => $link, 'anchor' => $anchor];
            default:
                return ['link' => '', 'anchor' => $hierarchy_item['value']];
        }
    }

    /**
     * Функция определяет есть ли ссылка в элементе хлебных крошек
     *
     * @param $hierarchy_item
     *
     * @return bool
     */
    public function isBreadcrumbHasLink($hierarchy_item): bool {
        if ($this->current_object instanceof WP_Post) {
            return ($hierarchy_item['value'] != $this->current_object->ID);
        } else if ($this->current_object instanceof WP_Term) {
            return ! (($hierarchy_item['type'] == 'term') && ($hierarchy_item['value'] == $this->current_object->term_id));
        }
        return false;
    }

	/**
	 * Функция возвращает объект страницы по заголовку
	 *
	 * @param string $title
	 *
	 * @return WP_Post|null
	 */
    public function getPageByTitle(string $title): ?WP_Post {
        $query = new \WP_Query(
            [
                'post_type'              => 'page',
                'title'                  => $title,
                'post_status'            => 'publish',
                'posts_per_page'         => 1,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
                'orderby'                => 'post_date ID',
                'order'                  => 'ASC',
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
            ]
        );

        if ( ! empty( $query->post ) ) {
            $page_got_by_title = $query->post;
        }
        else {
            $page_got_by_title = null;
        }
        return $page_got_by_title;
    }
}
