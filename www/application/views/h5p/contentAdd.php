<?php
/** @var string $settings */
/** @var string $title */
/** @var string $library */
/** @var string $parameters */
/** @var array|null $content */
/** @var bool $upload */
?>

    <div class="wrap">
        <h2>
            <?php if ($content === null): ?>
                <?php print __('Add new'); ?>
            <?php else: ?>
                <?php echo __('Edit'); ?>
                <em><?php echo $title; ?></em>
                <a href="<?php print admin_url('admin.php?page=h5p&task=show&id=' . $content['id']); ?>"
                   class="add-new-h2"><?php echo __('View'); ?></a>
                <a href="<?php print admin_url('admin.php?page=h5p&task=results&id=' . $content['id']); ?>"
                   class="add-new-h2"><?php echo __('Results'); ?></a>
            <?php endif; ?>
        </h2>
        <?php H5P_Plugin_Admin::print_messages(); ?>
        <form method="post" enctype="multipart/form-data" id="h5p-content-form" action="/h5p/addContentSubmit">
            <div id="post-body-content">
                <div id="titlediv">
                    <label class="" id="title-prompt-text" for="title">
                        <?php echo __('Enter title here'); ?>
                    </label>
                    <input id="title" type="text" name="title" value="<?php echo esc_html($title); ?>" required/>
                </div>
                <div class="h5p-upload">
                    <input type="file" name="h5p_file" id="h5p-file"/>
                    <div class="h5p-disable-file-check">
                        <label><input type="checkbox" name="h5p_disable_file_check"
                                      id="h5p-disable-file-check"/> <?php echo __('Disable file extension check'); ?>
                        </label>
                        <div
                            class="h5p-warning"><?php echo __("Warning! This may have security implications as it allows for uploading php files. That in turn could make it possible for attackers to execute malicious code on your site. Please make sure you know exactly what you're uploading."); ?></div>
                    </div>
                </div>
                <div class="h5p-create">
                    <div class="h5p-editor"><?php echo __('Waiting for javascript...'); ?></div>
                </div>
            </div>
            <hr>
            <div class="postbox h5p-sidebar">
                <h2><?php echo __('Actions'); ?></h2>
                <div id="minor-publishing">
                    <label><input type="radio" name="action"
                                  value="upload"<?php if ($upload): print ' checked="checked"'; endif; ?>/><?php echo __('Upload'); ?>
                    </label>
                    <label><input type="radio" name="action" value="create"/><?php echo __('Create'); ?></label>
                    <input type="hidden" name="library" value="<?php print esc_html($library); ?>"/>
                    <input type="hidden" name="parameters" value="<?php print $parameters; ?>"/>
                </div>
                <div id="major-publishing-actions" class="submitbox">
                    <?php if ($content !== null): ?>
                        <a class="submitdelete deletion"
                           href="/h5p/contentDelete/<?php echo $content['id'] ?>">Delete</a>
                    <?php endif; ?>
                    <input type="submit" name="submit"
                           value="<?php echo __($content === null ? 'Create' : 'Update') ?>"
                           class="button button-primary button-large"/>
                </div>
            </div>
            <hr>
            <?php if (get_option('h5p_frame', true)): ?>
                <div class="postbox h5p-sidebar">
                    <div role="button" class="h5p-toggle" tabindex="0" aria-expanded="true"
                         aria-label="<?php echo __('Toggle panel'); ?>"></div>
                    <h2><?php echo __('Display Options'); ?></h2>
                    <div class="h5p-action-bar-settings h5p-panel">
                        <label>
                            <input name="frame" type="checkbox"
                                   value="true"<?php if (!($content['disable'] & H5PCore::DISABLE_FRAME)): ?> checked="checked"<?php endif; ?>/>
                            <?php echo __("Display action bar and frame"); ?>
                        </label>
                        <?php if (get_option('h5p_export', true)): ?>
                            <label>
                                <input name="download" type="checkbox"
                                       value="true"<?php if (!($content['disable'] & H5PCore::DISABLE_DOWNLOAD)): ?> checked="checked"<?php endif; ?>/>
                                <?php echo __("Download button"); ?>
                            </label>
                        <?php endif; ?>
                        <?php if (get_option('h5p_embed', true)): ?>
                            <label>
                                <input name="embed" type="checkbox"
                                       value="true"<?php if (!($content['disable'] & H5PCore::DISABLE_EMBED)): ?> checked="checked"<?php endif; ?>/>
                                <?php echo __("Embed button"); ?>
                            </label>
                        <?php endif; ?>
                        <?php if (get_option('h5p_copyright', true)): ?>
                            <label>
                                <input name="copyright" type="checkbox"
                                       value="true"<?php if (!($content['disable'] & H5PCore::DISABLE_COPYRIGHT)): ?> checked="checked"<?php endif; ?>/>
                                <?php echo __("Copyright button"); ?>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <hr>
            <div class="postbox h5p-sidebar">
                <div role="button" class="h5p-toggle" tabindex="0" aria-expanded="true"
                     aria-label="<?php echo __('Toggle panel'); ?>"></div>
                <h2><?php echo __('Tags'); ?></h2>
                <div class="h5p-panel">
                <textarea rows="2" name="tags"
                          class="h5p-tags"><?php print esc_html($content['tags']); ?></textarea>
                    <p class="howto">Separate tags with commas</p>
                </div>
            </div>
        </form>
    </div>

<?php echo $settings ?>