<?php
/**
 * Class MallCockpitDeviceCenter
 */
class MallCockpitDeviceCenter
{
    /**
     * @return void
     */
    public function init()
    {
        // Change center posts columns
        add_filter('manage_center_posts_columns', [$this, 'postsColumns']);
        add_action('manage_center_posts_custom_column', [$this, 'postsCustomColumn'], 10, 2);

        // Add search query filter
        add_action('pre_get_posts', [$this, 'preGetPosts']);

        // Change label of acf field
        add_filter('acf/fields/post_object/result', [$this, 'changeAcfFieldResult'], 10, 4);
    }

    /**
     * @param $title
     * @param $post
     * @param $field
     * @param $postId
     * @return mixed
     */
    public function changeAcfFieldResult($title, $post, $field, $postId)
    {
        if ($post->post_type === 'center') {
            return get_field('center_name', $post) . ' (' . get_field('center_shortname', $post) . ')';
        }
        return $title;
    }

    /**
     * @param $query
     * @return void
     */
    public function preGetPosts($query)
    {
        if (!is_admin() || empty($query->get('s'))) {
            return;
        }

        $screen = get_current_screen();
        $postType = $query->get('post_type');
        if ((isset($screen->post_type) && 'center' != $screen->post_type) || 'center' != $postType) {
            return;
        }

        $query->set('meta_query', [
            'relation' => 'OR',
            [
                'key' => 'center_name',
                'value' => $query->get('s'),
                'compare' => 'LIKE'
            ],
            [
                'key' => 'center_shortname',
                'value' => $query->get('s'),
                'compare' => 'LIKE'
            ]
        ]);
        $query->set('s', '');
    }

    /**
     * @param $column
     * @param $postId
     * @return void
     */
    public function postsCustomColumn($column, $postId)
    {
        if ($column === 'center_name') {
            echo '<a class="row-title" href="' . get_edit_post_link($postId) . '">' . get_field($column, $postId) . '</a>';
        } elseif ($column === 'center_shortname') {
            echo get_field($column, $postId);
        }
    }

    /**
     * @param array $columns
     * @return array
     */
    public function postsColumns($columns)
    {
        return [
            'cb' => '<input type="checkbox" />',
            'center_name' => 'Bezeichnung',
            'center_shortname' => 'Kürzel'
        ];
    }
}