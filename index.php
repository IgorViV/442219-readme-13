<?php
    require_once('helpers.php');
    require_once('data.php');
    require_once('init.php');

    date_default_timezone_set("Europe/Moscow");

    // 1. В сценарии главной страницы выполните подключение к MySQL.
    $link = mysqli_connect($db_host['host'], $db_host['user'], $db_host['password'], $db_host['database']);
    mysqli_set_charset($link, "utf8");

    // 2. Отправьте SQL-запрос для получения типов контента.
    $sql_types = 'SELECT title, class FROM types;';

    // 3. Отправьте SQL-запрос для получения списка постов, объединённых с пользователями и отсортированный по популярности.
    $sql_posts = 'SELECT COUNT(likes.id) likes, posts.title, posts.text_content, posts.img_url, posts.video_url, posts.site_url, user_name AS author, types.class, users.avatar_url FROM posts JOIN users ON posts.user_id = users.id JOIN types ON posts.type_id = types.id JOIN likes ON posts.id = likes.post_id GROUP BY posts.id ORDER BY COUNT(likes.id) DESC;';

    // 4. Используйте эти данные для показа списка постов и списка типов контента на главной странице.
    if (!$link) {
        $error = mysqli_connect_error();
        $page_content = include_template('error.php', ['error' => $error]);
    } else {
        $result_posts = mysqli_query($link, $sql_posts);
        $result_types = mysqli_query($link, $sql_types);
        if ($result_posts && $result_types) {
            $posts = mysqli_fetch_all($result_posts, MYSQLI_ASSOC);
            $types = mysqli_fetch_all($result_types, MYSQLI_ASSOC);

            $page_content = include_template('main.php', [
                'posts' => $posts,
                'types' => $types,
            ]);
        } else {
            $error = mesqli_error($link);
            $page_content = include_template('error.php', ['error' => $error]);
        };
    };

    /**
     * Преобразует дату по формату
     * @param string $date Исходная дата
     * @param string $format Требуемый формат записи даты
     */
    function format_date($date, $format) {

        return date($format, strtotime($date));
    };

    /**
     * Вычисляет время прошедшее после публикации поста
     * @param string $date_public Дата публикации поста
     */
    function get_diff_time_public_post($date_public) {
        $diff_date_timestamp = time() - strtotime($date_public);
        $diff_time_public_post = '';
        $remaining_time = ceil($diff_date_timestamp / MINUTE);

        switch (true) {
            case ($remaining_time < HOUR):
                $diff_time_public_post = "$remaining_time " . get_noun_plural_form($remaining_time, 'минута', 'минуты', 'минут') . ' назад';
                break;
            case ($remaining_time >= HOUR && $remaining_time < DAY):
                $remaining_time = ceil($remaining_time / HOUR);
                $diff_time_public_post = "$remaining_time " . get_noun_plural_form($remaining_time, 'час', 'часа', 'часов') . ' назад';
                break;
            case ($remaining_time >= DAY && $remaining_time < WEEK):
                $remaining_time = ceil($remaining_time / DAY);
                $diff_time_public_post = "$remaining_time " . get_noun_plural_form($remaining_time, 'день', 'дня', 'дней') . ' назад';
                break;
            case ($remaining_time >= WEEK && $remaining_time < MONTH):
                $remaining_time = ceil($remaining_time / WEEK);
                $diff_time_public_post = "$remaining_time " . get_noun_plural_form($remaining_time, 'неделя', 'недели', 'недель') . ' назад';
                break;
            default:
                $remaining_time = ceil($remaining_time / MONTH);
                $diff_time_public_post = "$remaining_time " . get_noun_plural_form($remaining_time, 'месяц', 'месяца', 'месяцев') . ' назад';
        }

        return $diff_time_public_post;
    }

    /**
     * Урезает текст поста
     * @param string $text Текст поста
     * @param int $length Максимальное число символов, отображаемых в тексте поста
     */
    function cut_text($text, $length = 300) {

        if (mb_strlen($text, 'UTF-8') <= $length) {
            return array($text, false);
        }

        $words = explode(' ', $text);
        $output_string = '';

        foreach ($words as $word) {
            $output_string .= $word;

            if (mb_strlen($output_string, 'UTF-8') > $length) {
                break;
            } else {
                $output_string .= ' ';
            }
        };

        return array($output_string, true);
    }

    $layout_content = include_template('layout.php', [
        'is_auth' => $is_auth,
        'user_name' => $user_name,
        'content' => $page_content,
        'title' => 'readme: популярное',
    ]);

    print($layout_content);

    // TODO Обернуть htmlspecialchars в функцию
