<?php

/**
 * Media Sync
 *
 * This class is used for generating main content and also to import files to database
 *
 * @package     MediaSync
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1.0
 * @author      Erol Živina
 */
if ( !class_exists( 'MediaSync' ) ) :

class MediaSync
{
    /**
     * Render main plugin content
     *
     * @since 0.1.0
     * @return void
     */
    static public function media_sync_main_page()
    {

        if (!current_user_can('update_plugins')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $scan_files = isset($_GET['scan_files']) && $_GET['scan_files'] == true;

        $here = esc_url(get_admin_url(null, 'upload.php?page=media-sync-page'));

        $upload_dir = wp_upload_dir();
        $uploads_dir = str_replace(get_home_path(), DIRECTORY_SEPARATOR, $upload_dir['basedir']);
        ?>

        <div class="wrap main-media-sync-page" xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">
            <h1><?= __('Media Sync', 'media-sync') ?></h1>

            <? if ($scan_files) : ?>
                <div class="notice notice-error">
                    <p><?= __('Please backup your database! This plugin makes database changes.', 'media-sync') ?></p>
                </div>
                <div class="notice notice-success notice-files-imported">
                    <p><?= sprintf(__('Done! Highlighted files were successfully imported. %s to see changes.', 'media-sync'),
                            '<a href="'.add_query_arg('scan_files', 1, $here).'">'.__('Re-scan').'</a>') ?></p>
                </div>
            <? endif; ?>

            <div class="media-sync-list-files">
                <form action="<?= $here ?>" method="POST">
                    <input type="hidden" name="page" value="media-sync-page"/>
                    <div class="media-sync-buttons-holder">
                        <p class="media-sync-button-holder">
                            <? if (!$scan_files) : ?>
                                <a class="button button-primary"
                                   href="<?= add_query_arg('scan_files', 1, $here) ?>"><?= __('Scan Files', 'media-sync') ?></a>
                            <? endif; ?>
                            <? if ($scan_files) : ?>
                                <button class="button button-primary js-import-selected"><?= __('Import Selected', 'media-sync') ?></button>
                                <span class="spinner import-spinner"></span>

                                <span class="media-sync-dry-run-holder">
                                    <input type="checkbox" id="dry-run" name="dry_run" checked="checked" />
                                    <label for="dry-run"><?= __('Dry Run (to test without making database changes)', 'media-sync') ?></label>
                                </span>
                            <? endif; ?>
                        </p>
                        <? if (!$scan_files) : ?>
                            <p class="media-sync-scan-files-message">
                                <?= sprintf(__('It might take some time to scan all files if there are too many in upload dir: %s', 'media-sync'),
                                    '<code title="'.$upload_dir['basedir'].'">'.$uploads_dir.'</code>') ?>
                            </p>
                            <p class="media-sync-scan-files-message">
                                <?= sprintf(__('If you get timeout error, you can try to increase %s in php.ini', 'media-sync'), '<code>max_execution_time</code>') ?>
                            </p>
                        <? endif; ?>
                    </div>

                    <? if ($scan_files) : ?>
                        <p class="media-sync-state-holder">
                            <span class="media-sync-progress-holder">
                                <span class="media-sync-progress"></span>
                            </span>
                            <span class="media-sync-state">
                                <span class="media-sync-state-text">
                                    <?= __('Imported', 'media-sync') ?>
                                </span>
                                <span class="media-sync-state-number media-sync-imported-count js-media-sync-imported-count">0</span>
                                <span class="media-sync-state-text">
                                    <?= __('out of', 'media-sync') ?>
                                </span>
                                <span class="media-sync-state-number media-sync-selected-count js-media-sync-selected-count">0</span>
                                <span class="media-sync-state-text">
                                    <?= __('selected items', 'media-sync') ?>
                                </span>
                            </span>
                            <span class="media-sync-state media-sync-state-note">
                                <?= __('Those that are already in Media Library are skipped', 'media-sync') ?>
                            </span>
                        </p>

                        <? $tree = self::media_sync_get_list_of_uploads(); ?>
                        <? if (!empty($tree)) : ?>
                            <div class="media-sync-table-holder">
                                <table class="wp-list-table widefat fixed media">
                                    <? self::media_sync_render_thead_tfoot_row('thead') ?>
                                    <tbody id="the-list">
                                    <? foreach ($tree as $item) : ?>
                                        <? self::media_sync_render_row($item) ?>
                                    <? endforeach; ?>
                                    </tbody>
                                    <? self::media_sync_render_thead_tfoot_row('tfoot') ?>
                                </table>
                                <span class="spinner is-active table-spinner"></span>
                            </div>
                        <? else : ?>
                            <p class="media-sync-no-results">
                                <?= __('Everything seems fine here, there are no files that are not already in your Media Library.', 'media-sync') ?>
                            </p>
                        <? endif; ?>
                    <? endif; ?>
                </form>
            </div>
        </div>

        <?php
    }


    /**
     * Render table header and footer
     *
     * @since 0.1.0
     * @param $tag string (thead|tfoot)
     * @return void
     */
    static public function media_sync_render_thead_tfoot_row($tag)
    {
        $cb_id = 'cb-select-all-' . ($tag == 'thead' ? '1' : '2');
        ?>
        <<?= $tag ?>>
            <tr>
                <td class="manage-column check-column check-column-all"<?= $tag == 'thead' ? ' id="cb"':''?>>
                    <label class="screen-reader-text" for="<?= $cb_id ?>"><?= __('Select All', 'media-sync') ?></label>
                    <input id="<?= $cb_id ?>" type="checkbox">
                </td>
                <th scope="col" class="manage-column column-title column-primary"<?= $tag == 'thead' ? ' id="title"':''?>>
                    <span><?= __('File', 'media-sync') ?></span>
                </th>
            </tr>
        </<?= $tag ?>>
        <?
    }

    /**
     * Render table row for each file or directory
     *
     * @since 0.1.0
     * @param $item array
     * @return void
     */
    static public function media_sync_render_row($item)
    {
        $has_file_id = isset($item['file_id']) && $item['file_id'] !== false;
        $url = $has_file_id ? esc_url(add_query_arg(['post' => $item['file_id'], 'action' => 'edit'], get_admin_url(null, 'post.php'))) : $item['url'];
        $url_attr = $item['is_dir'] !== true ? ' target="_blank"' : '';
        $count_children = count($item['children']);
        $cls = 'media-sync-list-file';
        $cls .= ' is-' . ($item['is_dir'] === true ? 'dir' : 'file');
        $cls .= ' level-' . $item['level'];
        $cls .= ' is-first-level-' . ($item['level'] === 1 ? 'yes' : 'no');
        if ($item['is_dir'] !== true) {
            $cls .= ' is-in-db-' . ($has_file_id ? 'yes' : 'no');
        } else {
            $cls .= ' is-empty-' . ($count_children <= 0 ? 'yes' : 'no');
        }

        $toggle_arrows = true; // This can be made optional
        if ($toggle_arrows) {
            $is_link = $item['is_dir'] !== true;
            $cls .= ' toggle-arrows-yes';
        } else {
            $is_link = $item['is_dir'] !== true || $count_children > 0;
            $url_attr .= ' class="js-toggle-row"';
        }

        $row_id = "media-sync-item-" . $item['alias'];
        ?>

        <tr class="<?= $cls ?>" id="<?= $row_id ?>" data-parent-id="media-sync-item-<?= $item['parent_alias'] ?>">
            <th scope="row" class="check-column">
                <label class="screen-reader-text" for="cb-select-<?= $item['alias'] ?>"></label>
                <input type="checkbox" class="js-checkbox" id="cb-select-<?= $item['alias'] ?>"
                       value="<?= $item['absolute_path'] ?>" data-row-id="<?= $row_id ?>">
            </th>
            <td class="title column-title has-row-actions column-primary" data-colname="<?= __('File', 'media-sync') ?>">
                <? if (!empty($item['parents'])) : ?>
                    <span class="media-sync-parents">
                        <? foreach ($item['parents'] as $parent_key => $parent) : ?>
                            <?
                            $parent_cls = 'media-sync-parent';
                            $parent_cls .= ' is-first-' . ($parent_key == 0 ? 'yes' : 'no');
                            $parent_cls .= ' is-last-' . ($parent_key + 1 == count($item['parents']) ? 'yes' : 'no');
                            ?>
                            <span class="<?= $parent_cls ?>"><i></i></span>
                        <? endforeach; ?>
                        <span class="clearfix"></span>
                    </span>
                <? endif; ?>

                <? if ($toggle_arrows && $item['is_dir'] === true) : ?>
                    <span class="js-toggle-row media-sync-toggle-row dashicons"></span>
                <? endif; ?>

                <?= $is_link ? '<a href="'.$url.'"'.$url_attr.'>' : '' ?>
                <? if ($item['is_dir'] === true) : ?>
                    <span class="dashicons dashicons-category"></span>
                <? elseif(isset($item['src']) && file_exists($item['absolute_path'])) : ?>
                    <span class="media-sync-image media-icon image-icon">
                        <img width="60" height="60" class="attachment-60x60 size-60x60" alt="" src="<?= $item['src'] ?>" srcset="<?= $item['src'] ?> 100w, <?= $item['src'] ?> 150w" sizes="100vw" />
                    </span>
                <? endif; ?>
                <span class="media-sync-file-name"><?= $item['name'] ?></span>
                <?= $is_link ? '</a>' : '' ?>

                <? if ($item['is_dir'] === true) : ?>
                    <span class="media-sync-num-items"><?= sprintf('(%u %s)', $count_children, $count_children == 1 ? __('item', 'media-sync') : __('items', 'media-sync')) ?></span>
                <? endif; ?>

                <? if ($has_file_id) : ?>
                    <span class="media-sync-already-in-db"> - <?= __('Already in', 'media-sync') ?> <a
                            href="<?= $url ?>" class="dashicons dashicons-admin-media" target="_blank"></a></span>
                <? endif; ?>
            </td>
        </tr>

        <?php
        if (!empty($item['children'])) :
            foreach ($item['children'] as $child_item) :
                self::media_sync_render_row($child_item);
            endforeach;
        endif;
    }


    /**
     * Ajax action to import selected files
     *
     * @since 0.1.0
     * @return void
     */
    static public function media_sync_import_files()
    {
        if (!current_user_can('update_plugins')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_ajax_referer( 'media_sync_import_files', 'security' );

        // Get database stuff
        global $wpdb;

        $result = [];

        if(isset($_POST['media_items']) && !empty($_POST['media_items'])) {

            // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
            require_once( ABSPATH . 'wp-admin/includes/image.php' );


            $files_in_db = self::media_sync_get_files_in_db();


            $dry_run = isset($_POST['dry_run']) && json_decode($_POST['dry_run']) === true;

            foreach ($_POST['media_items'] as $media_item) {

                if(isset($media_item['file']) && !empty($media_item['file'])) {

                    $absolute_path = $media_item['file'];
                    $relative_path = str_replace(get_home_path(), DIRECTORY_SEPARATOR, $absolute_path);

                    // It's quicker to get all files already in db and check that array, than to do this query for each file
                    // $query = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE guid LIKE '%{$relative_path}'";
                    // $is_in_db = intval($wpdb->get_var($query)) > 0;

                    $is_in_db = isset($files_in_db[$relative_path]) && !empty($files_in_db[$relative_path]);

                    // Check if file is already in database
                    if(!$is_in_db) {

                        if(!$dry_run) {

                            // Import file to database (`wp_posts` and `wp_postmeta`)

                            // Check the type of file. We'll use this as the 'post_mime_type'.
                            $filetype = wp_check_filetype( basename( $absolute_path ), null );

                            // Prepare an array of post data for the attachment.
                            $attachment = array(
                                'guid'           => get_site_url() . $relative_path,
                                'post_mime_type' => $filetype['type'],
                                'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $relative_path ) ),
                                'post_content'   => '',
                                'post_status'    => 'inherit'
                            );

                            // Insert the attachment.
                            $attach_id = wp_insert_attachment( $attachment, $absolute_path );

                            // Generate the metadata for the attachment, and update the database record.
                            $attach_data = wp_generate_attachment_metadata( $attach_id, $absolute_path );
                            wp_update_attachment_metadata( $attach_id, $attach_data );

                            $result[] = [
                                'row_id' => $media_item['row_id'],
                                'inserted' => !!$attach_id
                            ];
                        } else {
                            $result[] = [
                                'row_id' => $media_item['row_id'],
                                'inserted' => true
                            ];
                        }
                    }
                }
            }
        }

        echo json_encode($result);

        wp_die(); // Must have for Ajax calls
    }


    /**
     * Scan "uploads" directory and return recursive list of files and directories
     *
     * @since 0.1.0
     * @return $tree array
     */
    static private function media_sync_get_list_of_uploads()
    {
        $upload_dir = wp_upload_dir();

        if(!($upload_dir && $upload_dir['basedir'])) {
            return [];
        }

        return self::media_sync_get_list_of_files($upload_dir['basedir'], $upload_dir['basedir'], self::media_sync_get_files_in_db());
    }


    /**
     * Scan directory (passed as first value) and return recursive list of files and directories
     *
     * @since 0.1.0
     * @param $current_dir_path string  Changing recursively for each directory that gets iterated
     * @param $uploads_dir_path string  Main "uploads" directory
     * @param $files_in_db array        List of files that are already in database
     * @return $tree array
     */
    static private function media_sync_get_list_of_files($current_dir_path, $uploads_dir_path, $files_in_db)
    {
        $obj_rdi = new RecursiveDirectoryIterator($current_dir_path);
        $tree = [];
        $i = 0;

        foreach ($obj_rdi as $full_path => $file) {
            // Only file name
            $file_name = $file->getFilename();
            // If it contains image size at the end (i.e. -100x100.jpg)
            $is_thumb = preg_match('/[_-]\d+x\d+(?=\.[a-z]{3,4}$)/im', $file_name) == true;

            if ($obj_rdi->isDot() || $file_name == ".DS_Store" || $file_name == ".htaccess" || $file_name == "index.php" || $is_thumb) {
                continue;
            }

            $children = $file->isDir() ? self::media_sync_get_list_of_files($file->getPathname(), $uploads_dir_path, $files_in_db) : [];

            if ($file->isDir() && empty($children)) {
                continue;
            }

            $uid_backup = uniqid('', true);

            $parents_path = ltrim(str_replace($uploads_dir_path, '', $current_dir_path), DIRECTORY_SEPARATOR);
            $parents = !empty($parents_path) ? explode(DIRECTORY_SEPARATOR, $parents_path) : [];
            $parent_alias = !empty($parents_path) ? sanitize_title(str_replace(DIRECTORY_SEPARATOR, '_', $parents_path), $uid_backup) : '';
            $alias = sanitize_title($file_name, $uid_backup);


            $item = [
                'alias' => (!empty($parent_alias) ? $parent_alias . '_' : '') . $alias,
                'name' => $file_name,
                'is_dir' => !!$file->isDir(),
                'level' => count($parents) + 1,
                'parent_alias' => $parent_alias,
                'parents' => $parents,
                'children' => $children,
                'absolute_path' => $full_path
            ];

            if ($file->isDir()) {
                $item['relative_path'] = '';
                $item['url'] = 'javascript:;';
            } else {

                $relative_path = str_replace(get_home_path(), DIRECTORY_SEPARATOR, $full_path);
                $file_id = isset($files_in_db[$relative_path]) && !empty($files_in_db[$relative_path]) &&
                        !empty($files_in_db[$relative_path]['id']) ? $files_in_db[$relative_path]['id'] : false;

                $item['relative_path'] = $relative_path;
                $item['url'] = get_site_url() . $relative_path;
                $item['file_id'] = $file_id;
            }

            // Add with this "key" for sorting
            $tree[$alias . '__' . $i] = $item;

            $i++;
        }

        // Sort items by key
        ksort($tree, SORT_NATURAL);

        return $tree;
    }


    /**
     * Get list of files that are already in database
     *
     * Caching does not seem to work
     *
     * @since 0.1.0
     * @param $cache bool Could be used to skip cache and get new values (only for first import batch for example)
     * @return $files_in_db array
     */
    static private function media_sync_get_files_in_db($cache = true)
    {
        $files_in_db = wp_cache_get('media_sync_get_files_in_db', '', true);

        if ($files_in_db === false || $cache === false) {

            $media_query = new WP_Query([
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'posts_per_page' => -1
            ]);

            $files = [];
            foreach ($media_query->posts as $post) {
                $files[str_replace(get_site_url(), "", wp_get_attachment_url($post->ID))] = [
                    'id' => $post->ID,
                    'name' => $post->post_title
                ];
            }

            $files_in_db = $files;
            wp_cache_set('media_sync_get_files_in_db', $files_in_db, '', 600);
        }

        return $files_in_db;
    }
}
endif; // End if class_exists check.
?>