<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="screen-reader-text" for="search-field"><?php esc_html_e('Otsi', 'tondi'); ?></label>

    <input
        id="search-field"
        type="search"
        class="search-field"
        name="s"
        value="<?php echo esc_attr(get_search_query()); ?>"
        placeholder="<?php esc_attr_e('Otsi…', 'tondi'); ?>"
        autocomplete="off" />

    <button type="submit" class="search-submit">
        <?php esc_html_e('Otsi', 'tondi'); ?>
    </button>
</form>
