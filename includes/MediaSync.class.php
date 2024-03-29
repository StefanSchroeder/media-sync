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
                wp_die(__('You do not have sufficient permissions to access this page.', 'media-sync'));
            }

            $scan_files = isset($_GET['scan_files']) && $_GET['scan_files'] == true;
            $associated_filter = isset($_GET['associated-filter']) && !empty($_GET['associated-filter']) ? explode(':', urldecode($_GET['associated-filter'])) : null;
            $missing_from_ml = $associated_filter && $associated_filter[0] == 'missing_from' && $associated_filter[1] == 'media_library';

            $here = esc_url(get_admin_url(null, 'upload.php?page=media-sync-page'));

            $upload_dir = wp_upload_dir();
            $uploads_dir = str_replace(get_home_path(), DIRECTORY_SEPARATOR, $upload_dir['basedir']);
            ?>

            <div class="wrap main-media-sync-page" xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">
                <h1><?= __('Media Sync', 'media-sync') ?></h1>

                <?php if ($scan_files) : ?>
                    <div class="notice notice-error">
                        <p><?= __('Please backup your database! This plugin makes database changes.', 'media-sync') ?></p>
                    </div>
                    <div class="notice notice-success notice-files-imported">
                        <p><?= sprintf(__('Done! Highlighted files were successfully imported. %s to see changes.', 'media-sync'),
                                '<a href="'.add_query_arg('scan_files', 1, $here).'">'.__('Re-scan', 'media-sync').'</a>') ?></p>
                    </div>
                <?php endif; ?>

                <div class="media-sync-list-files">
                    <form action="<?= $here ?>" method="GET">
                        <input type="hidden" name="page" value="media-sync-page"/>
                        <input type="hidden" name="scan_files" value="<?= $scan_files ?>"/>
                        <div class="media-sync-buttons-holder">
                            <?php if (!$scan_files) : ?>
                                <div class="card">
                                    <h2 class="title"><?= __('Sync - uploads directory', 'media-sync') ?></h2>
                                    
                                    <a class="button button-primary" href="<?= add_query_arg('scan_files', 1, $here) ?>">
                                        <?= __('Scan Files', 'media-sync') ?>
                                    </a>

                                    <p class="media-sync-scan-files-message">
                                        <?= sprintf(__('Use this to see content of upload dir: %s and import files to Media Library.', 'media-sync'),
                                            '<code title="'.$upload_dir['basedir'].'">'.$uploads_dir.'</code>') ?>
                                    </p>
                                </div>
                                <div class="card">
                                    <h2 class="title"><?= __('Sync - Media Library', 'media-sync') ?></h2>
                                    <a class="button button-primary" href="<?= add_query_arg('media_sync_missing_files', 'yes', get_admin_url(null, 'upload.php')) ?>">
                                        <?= __('Filter Media Library', 'media-sync') ?>
                                    </a>

                                    <p class="media-sync-scan-files-message">
                                        <?= __('Use this to see Media Library items that are missing actual files. This takes you to Media Library but with custom filter.', 'media-sync') ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <?php if ($scan_files) : ?>
                                <div class="media-sync-button-holder">
                                    <button class="button button-primary js-import-selected"><?= __('Import Selected', 'media-sync') ?></button>
                                    <span class="spinner import-spinner"></span>

                                    <span class="media-sync-dry-run-holder">
                                        <input type="checkbox" id="dry-run" name="dry_run" checked="checked" />
                                        <label for="dry-run"><?= __('Dry Run (test without making database changes)', 'media-sync') ?></label>
                                    </span>
                                    
                                    <span class="media-sync-dateinname-holder">
                                        <input type="checkbox" id="dateinname" name="dateinname" checked="checked" />
                                        <label for="dateinname"><?= __('Use date in name (Looks for YEAR-MONTH- in filename and uses it as post-date)', 'media-sync') ?></label>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($scan_files) : ?>
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
                                    <?= __('Files already in Media Library will be skipped during import', 'media-sync') ?>
                                </span>
                            </p>

                            <div class="wp-filter">
                                <div class="filter-items">
                                    <label for="associated-filter" class="screen-reader-text"><?= __('Filter by type', 'media-sync') ?></label>
                                    <select class="associated-filters" name="associated-filter" id="associated-filter">
                                        <option value=""><?= __('All files', 'media-sync') ?></option>
                                        <option value="missing_from:media_library"<?= $missing_from_ml ? 'selected="selected"' : '' ?>>
                                            <?= __('Only files missing from Media Library', 'media-sync') ?>
                                        </option>
                                    </select>

                                    <div class="actions">
                                        <input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?= __('Filter', 'media-sync') ?>">
                                    </div>
                                </div>
                            </div>

                            <?php $tree = self::media_sync_get_list_of_uploads(); ?>
                            <?php if (!empty($tree)) : ?>
                                <div class="media-sync-table-holder">
                                    <table class="wp-list-table widefat fixed media">
                                        <?php self::media_sync_render_thead_tfoot_row('thead') ?>
                                        <tbody id="the-list">
                                        <?php foreach ($tree as $item) : ?>
                                            <?php self::media_sync_render_row($item) ?>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <?php self::media_sync_render_thead_tfoot_row('tfoot') ?>
                                    </table>
                                    <span class="spinner is-active table-spinner"></span>
                                </div>
                            <?php else : ?>
                                <p class="media-sync-no-results">
                                    <?php if ($missing_from_ml) : ?>
                                        <?= __('Everything seems fine here, there are no files that are not already in your Media Library.', 'media-sync') ?>
                                    <?php else : ?>
                                        <?= __('No Results', 'media-sync') ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
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
            <?php
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
            $url = $has_file_id ? esc_url(add_query_arg(array('post' => $item['file_id'], 'action' => 'edit'), get_admin_url(null, 'post.php'))) : $item['url'];
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

            $is_trash = isset($item['file_status']) && $item['file_status'] === 'trash';

            $row_id = "media-sync-item-" . $item['alias'];
            ?>

            <tr class="<?= $cls ?>" id="<?= $row_id ?>" data-parent-id="media-sync-item-<?= $item['parent_alias'] ?>">
                <th scope="row" class="check-column">
                    <label class="screen-reader-text" for="cb-select-<?= $item['alias'] ?>"></label>
                    <input type="checkbox" class="js-checkbox" id="cb-select-<?= $item['alias'] ?>"
                           value="<?= $item['absolute_path'] ?>" data-row-id="<?= $row_id ?>">
                </th>
                <td class="title column-title has-row-actions column-primary" data-colname="<?= __('File', 'media-sync') ?>">
                    <?php if (!empty($item['parents'])) : ?>
                        <span class="media-sync-parents">
                            <?php foreach ($item['parents'] as $parent_key => $parent) : ?>
                                <?php
                                $parent_cls = 'media-sync-parent';
                                $parent_cls .= ' is-first-' . ($parent_key == 0 ? 'yes' : 'no');
                                $parent_cls .= ' is-last-' . ($parent_key + 1 == count($item['parents']) ? 'yes' : 'no');
                                ?>
                                <span class="<?= $parent_cls ?>"><i></i></span>
                            <?php endforeach; ?>
                            <span class="clearfix"></span>
                        </span>
                    <?php endif; ?>

                    <?php if ($toggle_arrows && $item['is_dir'] === true) : ?>
                        <span class="js-toggle-row media-sync-toggle-row dashicons"></span>
                    <?php endif; ?>

                    <?= $is_link ? '<a href="' . $url . '"' . $url_attr . '>' : '' ?>
                    <?php if ($item['is_dir'] === true) : ?>
                        <span class="dashicons dashicons-category"></span>
                    <?php endif; ?>
                        <span class="media-sync-file-name">
                            <?= $item['name'] ?>
                        </span>
                    <?= $is_link ? '</a>' : '' ?>

                    <?php if ($item['is_dir'] === true) : ?>
                        <span class="media-sync-num-items"><?= sprintf('(%u %s)', $count_children, $count_children == 1 ? __('item', 'media-sync') : __('items', 'media-sync')) ?></span>
                    <?php endif; ?>

                    <?php if ($has_file_id) : ?>
                        <span class="media-sync-already-in-db"> - <?= __('Already in', 'media-sync') ?>
                            <a href="<?= $url ?>" class="dashicons dashicons-admin-media" target="_blank"></a>
                            <?= $is_trash ? ' (' . __('In Trash', 'media-sync') . ')' : '' ?>
                        </span>
                    <?php endif; ?>
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
                wp_die(__('You do not have sufficient permissions to access this page.', 'media-sync'));
            }

            check_ajax_referer( 'media_sync_import_files', 'security' );

            // Get database stuff
            global $wpdb;

            $result = array();

            if(isset($_POST['media_items']) && !empty($_POST['media_items'])) {

                // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
                require_once( ABSPATH . 'wp-admin/includes/image.php' );


                $files_in_db = self::media_sync_get_files_in_db();


                $dry_run = isset($_POST['dry_run']) && json_decode($_POST['dry_run']) === true;
                $date_in_name = isset($_POST['dateinname']) && json_decode($_POST['dateinname']) === true;

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

                                // Get the file date.
                                $post_date = date( 'Y-m-d H:i:s', filemtime( $absolute_path ) );
                                
                                if($date_in_name) {
                                    // Does the name contain something that looks like YEAR-MONTH?
                                    if ( preg_match('/(\d\d\d\d)-(\d\d)-/', basename( $absolute_path ), $matches, PREG_OFFSET_CAPTURE) )
                                    {
                                       $yy = $matches[1][0]; // extract Year
                                       $mm = $matches[2][0]; // extract Month

                                       // Rewrite date using the new date.
                                       // Since we are not interested in DAY, HOUR:MINUTE:SECOND, use some defaults.
                                       $post_date = date("Y-m-d H:i:s", mktime(3, 2, 1, $mm, 1, $yy));
                                    }
                                }

                                // If for whatever reason not found, use current date.
                                if(!$post_date) {
                                    $post_date = date( 'Y-m-d H:i:s' );
                                }

                                // Prepare an array of post data for the attachment.
                                $attachment = array(
                                    'guid'           => get_site_url() . $relative_path,
                                    'post_mime_type' => $filetype['type'],
                                    'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $relative_path ) ),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit',
                                    'post_date'      => $post_date
                                );

                                // Insert the attachment.
                                $attach_id = wp_insert_attachment( $attachment, $absolute_path );

                                // Generate the metadata for the attachment, and update the database record.
                                $attach_data = wp_generate_attachment_metadata( $attach_id, $absolute_path );
                                wp_update_attachment_metadata( $attach_id, $attach_data );

                                $result[] = array(
                                    'row_id' => $media_item['row_id'],
                                    'inserted' => !!$attach_id
                                );
                            } else {
                                $result[] = array(
                                    'row_id' => $media_item['row_id'],
                                    'inserted' => true
                                );
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
                return array();
            }

            $associated_filter = isset($_GET['associated-filter']) && !empty($_GET['associated-filter']) ? explode(':', urldecode($_GET['associated-filter'])) : null;

            return self::media_sync_get_list_of_files($upload_dir['basedir'], $upload_dir['basedir'], self::media_sync_get_files_in_db(), $associated_filter);
        }


        /**
         * Scan directory (passed as first value) and return recursive list of files and directories
         *
         * @since 0.1.0
         * @param $current_dir_path string  Changing recursively for each directory that gets iterated
         * @param $uploads_dir_path string  Main "uploads" directory
         * @param $files_in_db array        List of files that are already in database
         * @param $associated_filter string Filter by "association", for now it can only be "file missing from media library" (solved by "Import Selected")
         * @return $tree array
         */
        static private function media_sync_get_list_of_files($current_dir_path, $uploads_dir_path, $files_in_db, $associated_filter)
        {
            $obj_rdi = new RecursiveDirectoryIterator($current_dir_path);
            $tree = array();
            $i = 0;

            foreach ($obj_rdi as $full_path => $file) {
                // Only file name
                $file_name = $file->getFilename();
                // If it contains image size at the end (i.e. -100x100.jpg)
                $is_thumb = preg_match('/[_-]\d+x\d+(?=\.[a-z]{3,4}$)/im', $file_name) == true;

                if ($obj_rdi->isDot() || $file_name == ".DS_Store" || $file_name == ".htaccess" || $file_name == "index.php" || $is_thumb) {
                    continue;
                }

                $children = $file->isDir() ? self::media_sync_get_list_of_files($file->getPathname(), $uploads_dir_path, $files_in_db, $associated_filter) : array();

                if ($file->isDir() && empty($children)) {
                    continue;
                }

                $uid_backup = uniqid('', true);

                $parents_path = ltrim(str_replace($uploads_dir_path, '', $current_dir_path), DIRECTORY_SEPARATOR);
                $parents = !empty($parents_path) ? explode(DIRECTORY_SEPARATOR, $parents_path) : array();
                $parent_alias = !empty($parents_path) ? sanitize_title(str_replace(DIRECTORY_SEPARATOR, '_', $parents_path), $uid_backup) : '';
                $alias = sanitize_title($file_name, $uid_backup);


                $item = array(
                    'alias' => (!empty($parent_alias) ? $parent_alias . '_' : '') . $alias,
                    'name' => $file_name,
                    'is_dir' => !!$file->isDir(),
                    'level' => count($parents) + 1,
                    'parent_alias' => $parent_alias,
                    'parents' => $parents,
                    'children' => $children,
                    'absolute_path' => $full_path
                );

                if ($file->isDir()) {
                    $item['relative_path'] = '';
                    $item['url'] = 'javascript:;';
                } else {

                    $relative_path = str_replace(get_home_path(), DIRECTORY_SEPARATOR, $full_path);
                    $file_in_db = isset($files_in_db[$relative_path]) && !empty($files_in_db[$relative_path]) ?
                        $files_in_db[$relative_path] : false;
                    $file_id = $file_in_db && !empty($file_in_db['id']) ? $file_in_db['id'] : false;
                    $file_status = $file_in_db && !empty($file_in_db['status']) ? $file_in_db['status'] : false;

                    if ($associated_filter && $associated_filter[0] == 'missing_from' && $associated_filter[1] == 'media_library' && $file_id !== false) {
                        continue;
                    }

                    $item['relative_path'] = $relative_path;
                    $item['url'] = get_site_url() . $relative_path;
                    $item['file_id'] = $file_id;
                    $item['file_status'] = $file_status;
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

                $media_query = new WP_Query(array(
                    'post_type' => 'attachment',
                    'post_status' => array('inherit', 'trash'),
                    'posts_per_page' => -1
                ));

                $files = array();
                foreach ($media_query->posts as $post) {
                    $files[str_replace(get_site_url(), "", wp_get_attachment_url($post->ID))] = array(
                        'id' => $post->ID,
                        'name' => $post->post_title,
                        'status' => $post->post_status
                    );
                }

                $files_in_db = $files;
                wp_cache_set('media_sync_get_files_in_db', $files_in_db, '', 600);
            }

            return $files_in_db;
        }
    }
endif; // End if class_exists check.
?>
