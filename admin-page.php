<div class="wrap">
<h2>Zotero Test Settings</h2>
<p>Add your library type, ID and slug here.</p>

<form action="options.php" method="post" name="zotero-test-acct" id="zotero-test-acct">
    
    <?php settings_fields( 'zotero_test_settings' ); ?>
    <?php do_settings_sections( 'zotero_test_settings'); ?>
        
    <p><input type="submit" value="Submit &rarr;"></p>
</form>

</div>